<?php

namespace App\Services;

use App\Models\AidDistribution;
use App\Models\AidDistributionImportBatch;
use App\Models\AidDistributionImportRow;
use App\Models\AidItem;
use App\Models\Family;
use App\Models\Office;
use App\Models\Project;
use App\Models\ProjectStat;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AidDistributionImportService
{
    private array $officeIdByLookup = [];
    private array $aidItemIdByLookup = [];
    private array $projectCache = [];
    private bool $officeCacheLoaded = false;
    private bool $aidItemCacheLoaded = false;

    public function parseAndValidateFile($file, string $filename): AidDistributionImportBatch
    {
        $this->loadOfficeCache();
        $this->loadAidItemCache();

        $collection = Excel::toCollection(null, $file, null, \Maatwebsite\Excel\Excel::XLSX)->first();
        
        $headers = $collection->first()->toArray();
        $rows = $collection->slice(1);
        
        $headerMap = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeaderKey($header);
            if ($normalized) {
                $headerMap[$index] = $normalized;
            }
        }

        $batch = AidDistributionImportBatch::create([
            'filename' => $filename,
            'uploaded_by' => Auth::id(),
            'status' => 'pending_review',
        ]);

        $normalizedRows = [];
        $errors = [];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            
            $rowArray = $row->toArray();
            $mappedRow = [];
            foreach ($headerMap as $index => $key) {
                $mappedRow[$key] = $rowArray[$index] ?? null;
            }

            $normalized = $this->normalizeRow($mappedRow, $rowNumber);
            
            if (!empty($normalized['errors'])) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $normalized['errors'],
                ];
                continue;
            }

            $normalizedRows[] = $normalized;
        }

        if (!empty($errors)) {
            $batch->update([
                'status' => 'failed',
                'errors' => ['validation_errors' => $errors],
                'error_rows' => count($errors),
            ]);
            return $batch;
        }

        $duplicates = $this->detectDuplicates($normalizedRows);

        $projectConstraints = $this->validateProjectConstraints($normalizedRows);
        if (!empty($projectConstraints)) {
            $batch->update([
                'status' => 'failed',
                'errors' => ['project_constraints' => $projectConstraints],
            ]);
            return $batch;
        }

        $validCount = 0;
        $duplicateCount = 0;

        foreach ($normalizedRows as $normalized) {
            $isDuplicate = isset($duplicates[$normalized['key']]);
            $duplicateDetails = $isDuplicate ? $duplicates[$normalized['key']] : null;

            AidDistributionImportRow::create([
                'batch_id' => $batch->id,
                'row_number' => $normalized['row_number'],
                'payload' => $normalized['payload'],
                'duplicate_in_file' => $duplicateDetails['in_file'] ?? false,
                'duplicate_in_db' => $duplicateDetails['in_db'] ?? false,
                'duplicate_details' => $duplicateDetails,
                'has_error' => false,
                'decision' => $isDuplicate ? 'pending' : 'approved',
            ]);

            if ($isDuplicate) {
                $duplicateCount++;
            } else {
                $validCount++;
            }
        }

        $batch->update([
            'total_rows' => count($normalizedRows),
            'valid_rows' => $validCount,
            'duplicate_rows' => $duplicateCount,
        ]);

        return $batch;
    }

    private function normalizeRow($row, int $rowNumber): array
    {
        $errors = [];

        $nationalId = $this->normalizeString($row['rkm_alhoy'] ?? null);
        if ($nationalId === null) {
            $errors[] = 'رقم الهوية مطلوب';
        } elseif (!preg_match('/^\d{9}$/', $nationalId)) {
            $errors[] = "رقم الهوية ({$nationalId}) يجب أن يكون 9 أرقام بالضبط";
        }

        $fullName = $this->normalizeString($row['alasm_rbaaay'] ?? null);
        if ($fullName === null) {
            $errors[] = 'الاسم الرباعي مطلوب';
        }

        $projectNumber = $this->normalizeString($row['rkm_almshroa'] ?? null);
        if ($projectNumber === null) {
            $errors[] = 'رقم المشروع مطلوب';
        }

        $project = null;
        $projectId = null;
        $institutionId = null;

        if ($projectNumber !== null) {
            $project = $this->resolveProject($projectNumber);
            if (!$project) {
                $errors[] = "المشروع رقم {$projectNumber} غير موجود";
            } else {
                $projectId = $project->id;
                $institutionId = $project->institution_id;
            }
        }
        
        $aidMode = $this->resolveAidMode($row['noaa_almsaaad'] ?? null);
        $aidItemId = null;
        
        if ($aidMode === 'in_kind') {
            $aidItemId = $this->resolveAidItemId($row['noaa_almsaaad_alaayny'] ?? null);
        }
        
        if ($project && $aidMode) {
            if ($project->project_type !== $aidMode) {
                $projectTypeArabic = $project->project_type === 'cash' ? 'نقدي' : 'عيني';
                $aidModeArabic = $aidMode === 'cash' ? 'نقدي' : 'عيني';
                $errors[] = "نوع المساعدة ({$aidModeArabic}) لا يتطابق مع نوع المشروع {$projectNumber} ({$projectTypeArabic})";
            }
            
            if ($project->project_type === 'in_kind' && $aidMode === 'in_kind') {
                if ($project->aid_item_id && $aidItemId && $project->aid_item_id !== $aidItemId) {
                    $projectAidItemName = $project->aidItem?->name ?? 'غير معروف';
                    $rowAidItemName = $this->getAidItemNameById($aidItemId) ?? 'غير معروف';
                    $errors[] = "صنف المساعدة العينية ({$rowAidItemName}) لا يتطابق مع صنف المشروع {$projectNumber} ({$projectAidItemName})";
                }
            }
        }

        $officeId = $this->resolveOfficeId($row['almktb'] ?? null, $row['mkan_alskn'] ?? null);
        $user = Auth::user();
        if ($user?->user_type === 'employee') {
            $officeId = $user->office_id;
        }

        if ($officeId === null) {
            $errors[] = 'المكتب غير موجود';
        }

        $maritalStatus = $this->resolveMaritalStatus($row['alhal_alzogy'] ?? null);
        
        if ($maritalStatus === null) {
            $errors[] = 'الحالة الزوجية غير صحيحة';
        }

        $spouses = $this->extractSpouses($row);
        
        foreach ($spouses as $index => $spouse) {
            $spouseNationalId = $spouse['national_id'] ?? null;
            if ($spouseNationalId !== null && !preg_match('/^\d{9}$/', $spouseNationalId)) {
                $errors[] = "رقم هوية الزوجة " . ($index + 1) . " ({$spouseNationalId}) يجب أن يكون 9 أرقام بالضبط";
            }
        }

        if (!in_array($maritalStatus, ['married', 'polygamous'], true)) {
            $spouses = [];
        }

        $phone = $this->normalizeString($row['rkm_algoal'] ?? null);
        if ($phone !== null && !preg_match('/^(05|59|56)\d{7,8}$/', $phone)) {
            $errors[] = "رقم الجوال ({$phone}) يجب أن يبدأ بـ 05 أو 59 أو 56 ويتكون من 9-10 أرقام";
        }

        $familyMembersCount = $this->toIntegerOrNull($row['aadd_afrad_alasr'] ?? null);
        if ($familyMembersCount !== null && $familyMembersCount < 1) {
            $errors[] = 'عدد أفراد الأسرة يجب أن يكون 1 على الأقل';
        }

        $distributedAt = null;
        try {
            $distributedAt = $this->parseDistributedAt($row['tarykh_alsrf'] ?? null);
            if ($distributedAt === null) {
                $errors[] = 'تاريخ الصرف مطلوب';
            }
        } catch (\Throwable $e) {
            $errors[] = 'تاريخ الصرف غير صحيح أو بصيغة خاطئة';
        }

        $payload = [
            'full_name' => $fullName,
            'national_id' => $nationalId,
            'phone' => $phone,
            'family_members_count' => $familyMembersCount,
            'address' => $this->normalizeString($row['mkan_alskn'] ?? null),
            'marital_status' => $maritalStatus,
            'spouses' => !empty($spouses) ? $spouses : null,
            'spouse_full_name' => $spouses[0]['full_name'] ?? null,
            'spouse_national_id' => $spouses[0]['national_id'] ?? null,
            'office_id' => $officeId,
            'institution_id' => $institutionId,
            'project_id' => $projectId,
            'aid_mode' => $aidMode,
            'aid_item_id' => null,
            'quantity' => null,
            'cash_amount' => null,
            'distributed_at' => $distributedAt ?? now()->startOfDay(),
            'notes' => $this->normalizeString($row['mlahthat'] ?? null),
        ];

        if ($aidMode === 'cash') {
            $cashAmount = $this->toDecimalOrNull($row['kym_almsaaad_alnkdy'] ?? null);
            if ($cashAmount === null || $cashAmount <= 0) {
                $errors[] = 'قيمة المساعدة النقدية مطلوبة ويجب أن تكون أكبر من صفر';
            }
            $payload['cash_amount'] = $cashAmount;
        } else {
            if ($aidItemId === null) {
                $errors[] = 'نوع المساعدة العينية غير موجود';
            }
            $payload['aid_item_id'] = $aidItemId;
            
            $quantity = $this->toDecimalOrNull($row['kmy_alsrf_llmsaaad'] ?? null);
            if ($quantity === null || $quantity <= 0) {
                $errors[] = 'كمية الصرف مطلوبة ويجب أن تكون أكبر من صفر';
            }
            $payload['quantity'] = $quantity;
        }

        return [
            'row_number' => $rowNumber,
            'payload' => $payload,
            'errors' => $errors,
            'key' => $nationalId . '|' . $payload['distributed_at']->format('Y-m'),
        ];
    }

    private function detectDuplicates(array $normalizedRows): array
    {
        $duplicates = [];
        $seenInFile = [];

        foreach ($normalizedRows as $normalized) {
            $key = $normalized['key'];
            $nationalId = $normalized['payload']['national_id'];
            $distributedAt = $normalized['payload']['distributed_at'];
            $month = $distributedAt->format('Y-m');

            $inFile = isset($seenInFile[$key]);
            if ($inFile) {
                if (!isset($duplicates[$key])) {
                    $duplicates[$key] = ['in_file' => false, 'in_db' => false, 'details' => []];
                }
                $duplicates[$key]['in_file'] = true;
            }
            $seenInFile[$key] = true;

            $inDb = $this->checkDuplicateInDb($nationalId, $month);
            if ($inDb) {
                if (!isset($duplicates[$key])) {
                    $duplicates[$key] = ['in_file' => false, 'in_db' => false, 'details' => []];
                }
                $duplicates[$key]['in_db'] = true;
                $duplicates[$key]['details'] = $inDb;
            }
        }

        return $duplicates;
    }

    private function checkDuplicateInDb(string $nationalId, string $month): ?array
    {
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        $existingDistribution = AidDistribution::query()
            ->whereHas('family', function ($q) use ($nationalId) {
                $q->where('national_id', $nationalId)
                    ->orWhere('wife_1_national_id_gen', $nationalId)
                    ->orWhere('wife_2_national_id_gen', $nationalId)
                    ->orWhere('wife_3_national_id_gen', $nationalId)
                    ->orWhere('wife_4_national_id_gen', $nationalId)
                    ->orWhere('spouse_national_id', $nationalId);
            })
            ->whereBetween('distributed_at', [$startOfMonth, $endOfMonth])
            ->where('status', 'active')
            ->with(['family', 'office'])
            ->first();

        if ($existingDistribution) {
            return [
                'family_name' => $existingDistribution->family?->full_name ?? '-',
                'office_name' => $existingDistribution->office?->name ?? '-',
                'distributed_at' => $existingDistribution->distributed_at?->format('Y-m-d') ?? '-',
            ];
        }

        return null;
    }

    private function validateProjectConstraints(array $normalizedRows): array
    {
        $projectAggregation = [];

        foreach ($normalizedRows as $normalized) {
            $projectId = $normalized['payload']['project_id'];
            if (!$projectId) {
                continue;
            }

            if (!isset($projectAggregation[$projectId])) {
                $projectAggregation[$projectId] = [
                    'beneficiaries' => 0,
                    'cash_amount' => 0,
                    'quantity' => 0,
                ];
            }

            $projectAggregation[$projectId]['beneficiaries'] += 1;
            if ($normalized['payload']['aid_mode'] === 'cash') {
                $projectAggregation[$projectId]['cash_amount'] += (float) ($normalized['payload']['cash_amount'] ?? 0);
            } else {
                $projectAggregation[$projectId]['quantity'] += (float) ($normalized['payload']['quantity'] ?? 0);
            }
        }

        $errors = [];

        foreach ($projectAggregation as $projectId => $requested) {
            $project = ProjectStat::query()->find($projectId);
            if (!$project) {
                continue;
            }

            if ($requested['beneficiaries'] > $project->remaining_beneficiaries) {
                $errors[] = [
                    'project_number' => $project->project_number,
                    'project_name' => $project->name,
                    'type' => 'beneficiaries',
                    'requested' => $requested['beneficiaries'],
                    'available' => $project->remaining_beneficiaries,
                ];
            }

            if ($project->project_type === 'cash' && $requested['cash_amount'] > $project->remaining_amount) {
                $errors[] = [
                    'project_number' => $project->project_number,
                    'project_name' => $project->name,
                    'type' => 'cash_amount',
                    'requested' => $requested['cash_amount'],
                    'available' => $project->remaining_amount,
                ];
            }

            if ($project->project_type === 'in_kind' && $requested['quantity'] > $project->remaining_quantity) {
                $errors[] = [
                    'project_number' => $project->project_number,
                    'project_name' => $project->name,
                    'type' => 'quantity',
                    'requested' => $requested['quantity'],
                    'available' => $project->remaining_quantity,
                ];
            }
        }

        return $errors;
    }

    public function finalizeImport(AidDistributionImportBatch $batch, ProjectConsumptionService $consumptionService): array
    {
        $approvedRows = $batch->rows()->where('decision', 'approved')->get();

        $projectConstraints = $this->validateProjectConstraintsFromRows($approvedRows);
        if (!empty($projectConstraints)) {
            return [
                'success' => false,
                'error' => 'تجاوزت القيود للمشاريع',
                'details' => $projectConstraints,
            ];
        }

        DB::beginTransaction();
        try {
            foreach ($approvedRows as $row) {
                $payload = $row->payload;

                $payloadWithPrimaryName = $payload;
                $payloadWithPrimaryName['primary_name'] = $payload['full_name'];
                $payloadWithPrimaryName['resolution_mode'] = 'create_new_family';
                $payloadWithPrimaryName['mobile'] = $payload['phone'] ?? null;
                $payloadWithPrimaryName['housing_location'] = $payload['address'] ?? null;
                $payloadWithPrimaryName['distributed_date'] = $payload['distributed_at'] ?? null;
                $payloadWithPrimaryName['distribution_notes'] = $payload['notes'] ?? null;

                $distribution = $consumptionService->createDistribution($payloadWithPrimaryName);

                $row->update([
                    'created_distribution_id' => $distribution->id,
                ]);
            }

            $batch->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            return [
                'success' => true,
                'imported' => $approvedRows->count(),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function validateProjectConstraintsFromRows($rows): array
    {
        $projectAggregation = [];

        foreach ($rows as $row) {
            $payload = $row->payload;
            $projectId = $payload['project_id'] ?? null;
            if (!$projectId) {
                continue;
            }

            if (!isset($projectAggregation[$projectId])) {
                $projectAggregation[$projectId] = [
                    'beneficiaries' => 0,
                    'cash_amount' => 0,
                    'quantity' => 0,
                ];
            }

            $projectAggregation[$projectId]['beneficiaries'] += 1;
            if ($payload['aid_mode'] === 'cash') {
                $projectAggregation[$projectId]['cash_amount'] += (float) ($payload['cash_amount'] ?? 0);
            } else {
                $projectAggregation[$projectId]['quantity'] += (float) ($payload['quantity'] ?? 0);
            }
        }

        $errors = [];

        foreach ($projectAggregation as $projectId => $requested) {
            $project = ProjectStat::query()->find($projectId);
            if (!$project) {
                continue;
            }

            if ($requested['beneficiaries'] > $project->remaining_beneficiaries) {
                $errors[] = [
                    'project_number' => $project->project_number,
                    'project_name' => $project->name,
                    'type' => 'beneficiaries',
                    'requested' => $requested['beneficiaries'],
                    'available' => $project->remaining_beneficiaries,
                ];
            }

            if ($project->project_type === 'cash' && $requested['cash_amount'] > $project->remaining_amount) {
                $errors[] = [
                    'project_number' => $project->project_number,
                    'project_name' => $project->name,
                    'type' => 'cash_amount',
                    'requested' => $requested['cash_amount'],
                    'available' => $project->remaining_amount,
                ];
            }

            if ($project->project_type === 'in_kind' && $requested['quantity'] > $project->remaining_quantity) {
                $errors[] = [
                    'project_number' => $project->project_number,
                    'project_name' => $project->name,
                    'type' => 'quantity',
                    'requested' => $requested['quantity'],
                    'available' => $project->remaining_quantity,
                ];
            }
        }

        return $errors;
    }

    private function resolveProject(string $projectNumber): ?Project
    {
        if (isset($this->projectCache[$projectNumber])) {
            return $this->projectCache[$projectNumber];
        }

        $project = Project::query()->where('project_number', $projectNumber)->first();
        $this->projectCache[$projectNumber] = $project;

        return $project;
    }

    private function resolveOfficeId($officeLabel, $locationLabel): ?int
    {
        $this->loadOfficeCache();

        $officeName = $this->normalizeString($officeLabel);
        $location = $this->normalizeString($locationLabel);
        $keys = [];

        if ($officeName !== null) {
            $keys[] = $this->normalizeLookupKey($officeName);

            if (str_starts_with($officeName, 'مكتب ')) {
                $nameWithoutPrefix = trim(mb_substr($officeName, 5));
                $keys[] = $this->normalizeLookupKey($nameWithoutPrefix);
            }
        }

        if ($location !== null) {
            $keys[] = $this->normalizeLookupKey($location);
        }

        foreach ($keys as $key) {
            if (isset($this->officeIdByLookup[$key])) {
                return $this->officeIdByLookup[$key];
            }
        }

        return null;
    }

    private function resolveAidItemId($value): ?int
    {
        $this->loadAidItemCache();

        $raw = $this->normalizeString($value);
        if ($raw === null) {
            return null;
        }

        if (is_numeric($raw)) {
            $id = (int) $raw;
            if (isset($this->aidItemIdByLookup['id:' . $id])) {
                return $this->aidItemIdByLookup['id:' . $id];
            }
        }

        $key = $this->normalizeLookupKey($raw);
        if (isset($this->aidItemIdByLookup[$key])) {
            return $this->aidItemIdByLookup[$key];
        }

        return null;
    }

    private function resolveMaritalStatus($value): ?string
    {
        $raw = $this->normalizeString($value);
        if ($raw === null) {
            return null;
        }

        $normalized = $this->normalizeArabicLabel($value);
        $map = [
            'اعزب/عزباء' => 'single',
            'اعزب' => 'single',
            'عزباء' => 'single',
            'متزوج/ه' => 'married',
            'متزوج' => 'married',
            'متزوجه' => 'married',
            'متعددالزوجات' => 'polygamous',
            'متعدد' => 'polygamous',
            'ارمل/ه' => 'widowed',
            'ارمل' => 'widowed',
            'ارملh' => 'widowed',
            'مطلق/ه' => 'divorced',
            'مطلق' => 'divorced',
            'مطلقه' => 'divorced',
        ];

        return $map[$normalized] ?? null;
    }

    private function resolveAidMode($value): string
    {
        $normalized = $this->normalizeArabicLabel($value);
        $map = [
            'نقديه' => 'cash',
            'عينيه' => 'in_kind',
        ];

        return $map[$normalized] ?? 'cash';
    }

    private function parseDistributedAt($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->startOfDay();
        }

        $raw = trim((string) $value);
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $raw)->startOfDay();
            } catch (\Throwable $e) {
            }
        }

        return Carbon::parse($raw)->startOfDay();
    }

    private function extractSpouses(array $row): array
    {
        $spouses = [];
        for ($i = 1; $i <= 4; $i++) {
            $name = $this->normalizeString($row["asm_alzog_{$i}"] ?? null);
            $nationalId = $this->normalizeString($row["rkm_hoy_alzog_{$i}"] ?? null);
            if ($name === null && $nationalId === null) {
                continue;
            }

            $spouses[] = [
                'full_name' => $name,
                'national_id' => $nationalId,
            ];
        }

        return $spouses;
    }

    private function loadOfficeCache(): void
    {
        if ($this->officeCacheLoaded) {
            return;
        }

        $this->officeCacheLoaded = true;
        $this->officeIdByLookup = [];

        $offices = Office::query()->get(['id', 'name', 'location']);
        foreach ($offices as $office) {
            $id = (int) $office->id;
            $name = $this->normalizeString($office->name);
            $location = $this->normalizeString($office->location);

            if ($name !== null) {
                $this->officeIdByLookup[$this->normalizeLookupKey($name)] = $id;
                $this->officeIdByLookup[$this->normalizeLookupKey('مكتب ' . $name)] = $id;
            }

            if ($location !== null) {
                $this->officeIdByLookup[$this->normalizeLookupKey($location)] = $id;
                $this->officeIdByLookup[$this->normalizeLookupKey('مكتب ' . $location)] = $id;
            }
        }
    }

    private function loadAidItemCache(): void
    {
        if ($this->aidItemCacheLoaded) {
            return;
        }

        $this->aidItemCacheLoaded = true;
        $this->aidItemIdByLookup = [];

        $aidItems = AidItem::query()->get(['id', 'name']);
        foreach ($aidItems as $aidItem) {
            $id = (int) $aidItem->id;
            $this->aidItemIdByLookup['id:' . $id] = $id;

            $name = $this->normalizeString($aidItem->name);
            if ($name !== null) {
                $this->aidItemIdByLookup[$this->normalizeLookupKey($name)] = $id;
            }
        }
    }

    private function toIntegerOrNull($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function toDecimalOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function normalizeString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);
        if ($string === '' || $string === '-' || $string === '---') {
            return null;
        }

        if (preg_match('/^\d+(\.0+)?$/', $string)) {
            $string = preg_replace('/\.0+$/', '', $string) ?? $string;
        }

        return $string;
    }

    private function normalizeArabicLabel($value): string
    {
        $raw = $this->normalizeString($value) ?? '';
        $normalized = str_replace(['أ', 'إ', 'آ', 'ة', 'ـ', ' '], ['ا', 'ا', 'ا', 'ه', '', ''], $raw);
        return mb_strtolower($normalized);
    }

    private function normalizeLookupKey(string $value): string
    {
        return $this->normalizeArabicLabel($value);
    }

    private function normalizeHeaderKey($value): ?string
    {
        $normalized = $this->normalizeString($value);
        if ($normalized === null) {
            return null;
        }

        $map = [
            'تاريخ الصرف' => 'tarykh_alsrf',
            'رقم الهوية' => 'rkm_alhoy',
            'الاسم رباعي' => 'alasm_rbaaay',
            'الاسم الرباعي' => 'alasm_rbaaay',
            'رقم الجوال' => 'rkm_algoal',
            'عدد افراد الأسرة' => 'aadd_afrad_alasr',
            'مكان السكن' => 'mkan_alskn',
            'الحالة الزوجية' => 'alhal_alzogy',
            'المكتب' => 'almktb',
            'نوع المساعدة' => 'noaa_almsaaad',
            'رقم المشروع' => 'rkm_almshroa',
            'ملاحظات' => 'mlahthat',
            'قيمة المساعدة النقدية' => 'kym_almsaaad_alnkdy',
            'نوع المساعدة العينية' => 'noaa_almsaaad_alaayny',
            'كمية الصرف للمساعدة' => 'kmy_alsrf_llmsaaad',
            'اسم الزوجة 1' => 'asm_alzog_1',
            'رقم هوية الزوجة 1' => 'rkm_hoy_alzog_1',
            'اسم الزوجة 2' => 'asm_alzog_2',
            'رقم هوية الزوجة 2' => 'rkm_hoy_alzog_2',
            'اسم الزوجة 3' => 'asm_alzog_3',
            'رقم هوية الزوجة 3' => 'rkm_hoy_alzog_3',
            'اسم الزوجة 4' => 'asm_alzog_4',
            'رقم هوية الزوجة 4' => 'rkm_hoy_alzog_4',
        ];

        return $map[$normalized] ?? null;
    }

    private function getAidItemNameById(?int $aidItemId): ?string
    {
        if (!$aidItemId) {
            return null;
        }

        foreach ($this->aidItemIdByLookup as $name => $id) {
            if ($id === $aidItemId) {
                return $name;
            }
        }

        $aidItem = AidItem::find($aidItemId);
        return $aidItem?->name;
    }
}
