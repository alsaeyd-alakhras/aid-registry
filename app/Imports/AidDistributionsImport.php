<?php

namespace App\Imports;

use App\Models\AidDistribution;
use App\Models\AidItem;
use App\Models\Family;
use App\Models\Institution;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AidDistributionsImport implements ToModel, WithHeadingRow, WithChunkReading
{
    private ?int $creatorId = null;
    private array $problemRows = [];
    private array $officeIdByLookup = [];
    private array $institutionIdByLookup = [];
    private array $aidItemIdByLookup = [];
    private bool $officeCacheLoaded = false;
    private bool $institutionCacheLoaded = false;
    private bool $aidItemCacheLoaded = false;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $nationalId = $this->normalizeString($row['rkm_alhoy'] ?? null);
        if ($nationalId === null) {
            return null;
        }

        $fullName = $this->normalizeString($row['alasm_rbaaay'] ?? null);
        if ($fullName === null) {
            return null;
        }

        $officeId = $this->resolveOfficeId($row['almktb'] ?? null, $row['mkan_alskn'] ?? null);
        $institutionId = $this->resolveInstitutionId($row['almoss'] ?? null);
        if ($officeId === null || $institutionId === null) {
            $this->registerProblemRow($fullName, $nationalId);
            return null;
        }

        $aidMode = $this->resolveAidMode($row['noaa_almsaaad'] ?? null);
        $maritalStatus = $this->resolveMaritalStatus($row['alhal_alzogy'] ?? null);
        $spouses = $this->extractSpouses($row);
        if (!in_array($maritalStatus, ['married', 'polygamous'], true)) {
            $spouses = [];
        }

        $familyPayload = [
            'full_name' => $fullName,
            'national_id' => $nationalId,
            'phone' => $this->normalizeString($row['rkm_algoal'] ?? null),
            'family_members_count' => $this->toIntegerOrNull($row['aadd_afrad_alasr'] ?? null),
            'address' => $this->normalizeString($row['mkan_alskn'] ?? null),
            'marital_status' => $maritalStatus,
            'spouses' => !empty($spouses) ? $spouses : null,
            'spouse_full_name' => $spouses[0]['full_name'] ?? null,
            'spouse_national_id' => $spouses[0]['national_id'] ?? null,
        ];

        $distributionPayload = $this->buildDistributionPayload($row, $aidMode, $officeId, $institutionId);
        $creatorId = $this->resolveCreatorId();
        if (!$creatorId) {
            throw new \RuntimeException('لا يمكن الاستيراد بدون مستخدم مسجّل دخول.');
        }
        $distributionPayload['created_by'] = $creatorId;

        $family = $this->resolveFamilyForImport($nationalId, $familyPayload);
        $distributionPayload['family_id'] = $family->id;

        return AidDistribution::create($distributionPayload);
    }

    private function resolveFamilyForImport(string $nationalId, array $familyPayload): Family
    {
        $matchedFamily = Family::query()
            ->where('national_id', $nationalId)
            ->orWhere('wife_1_national_id_gen', $nationalId)
            ->orWhere('wife_2_national_id_gen', $nationalId)
            ->orWhere('wife_3_national_id_gen', $nationalId)
            ->orWhere('wife_4_national_id_gen', $nationalId)
            ->orWhere('spouse_national_id', $nationalId)
            ->first();

        if ($matchedFamily) {
            if ((string) $matchedFamily->national_id === $nationalId) {
                $matchedFamily->update($familyPayload);
            }
            return $matchedFamily;
        }

        return Family::create($familyPayload);
    }

    private function buildDistributionPayload(array $row, string $aidMode, int $officeId, int $institutionId): array
    {
        $payload = [
            'office_id' => $officeId,
            'institution_id' => $institutionId,
            'aid_mode' => $aidMode,
            'aid_item_id' => null,
            'quantity' => null,
            'cash_amount' => null,
            'distributed_at' => $this->parseDistributedAt($row['tarykh_alsrf'] ?? null),
            'notes' => $this->normalizeString($row['mlahthat'] ?? null),
        ];

        if ($aidMode === 'cash') {
            $payload['cash_amount'] = $this->toDecimalOrNull($row['kym_almsaaad_alnkdy'] ?? null);
            return $payload;
        }

        $payload['aid_item_id'] = $this->resolveAidItemId($row['noaa_almsaaad_alaayny'] ?? null);
        $payload['quantity'] = $this->toDecimalOrNull($row['kmy_alsrf_llmsaaad'] ?? null);

        return $payload;
    }

    private function resolveMaritalStatus($value): string
    {
        $normalized = $this->normalizeArabicLabel($value);
        $map = [
            'اعزب/عزباء' => 'single',
            'متزوج/ه' => 'married',
            'متعددالزوجات' => 'polygamous',
            'ارمل/ه' => 'widowed',
            'مطلق/ه' => 'divorced',
        ];

        return $map[$normalized] ?? 'single';
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

    private function resolveInstitutionId($institutionLabel): ?int
    {
        $this->loadInstitutionCache();

        $institutionName = $this->normalizeString($institutionLabel);
        if ($institutionName === null) {
            return null;
        }

        $lookupKey = $this->normalizeLookupKey($institutionName);
        if (isset($this->institutionIdByLookup[$lookupKey])) {
            return $this->institutionIdByLookup[$lookupKey];
        }

        $institution = Institution::query()->create([
            'name' => $institutionName,
            'is_active' => true,
            'notes' => null,
        ]);

        $id = (int) $institution->id;
        $this->institutionIdByLookup[$lookupKey] = $id;

        return $id;
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

    private function parseDistributedAt($value): Carbon
    {
        if ($value === null || $value === '') {
            return now()->startOfDay();
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
                // Keep trying other formats.
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

    private function resolveCreatorId(): ?int
    {
        if ($this->creatorId !== null) {
            return $this->creatorId;
        }

        $id = Auth::id();
        $this->creatorId = $id ? (int) $id : null;

        return $this->creatorId;
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

    private function loadInstitutionCache(): void
    {
        if ($this->institutionCacheLoaded) {
            return;
        }

        $this->institutionCacheLoaded = true;
        $this->institutionIdByLookup = [];

        $institutions = Institution::query()->get(['id', 'name']);
        foreach ($institutions as $institution) {
            $id = (int) $institution->id;
            $name = $this->normalizeString($institution->name);
            if ($name !== null) {
                $this->institutionIdByLookup[$this->normalizeLookupKey($name)] = $id;
            }
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getProblemRows(): array
    {
        return array_values($this->problemRows);
    }

    private function registerProblemRow(string $fullName, string $nationalId): void
    {
        $key = $fullName . '|' . $nationalId;
        $this->problemRows[$key] = [
            'full_name' => $fullName,
            'national_id' => $nationalId,
        ];
    }
}
