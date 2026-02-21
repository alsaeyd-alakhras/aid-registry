<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AidDistribution;
use App\Models\AidItem;
use App\Models\Family;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class AidDistributionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeLookupForAidDistribution();

        if ($request->ajax()) {
            $year = $request->year ?? Carbon::now()->year;

            $distributions = AidDistribution::query()
                ->with(['family', 'office', 'aidItem', 'creator'])
                ->whereYear('distributed_at', $year)
                ->orderBy('distributed_at', 'desc');

            if ($request->from_date) {
                $distributions->whereDate('distributed_at', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $distributions->whereDate('distributed_at', '<=', $request->to_date);
            }

            if ($request->column_filters) {
                $this->applyColumnFilters($distributions, $request->column_filters);
            }

            $rows = $distributions->get()->map(function (AidDistribution $distribution) {
                $family = $distribution->family;

                return [
                    'id' => $distribution->id,
                    'distributed_at' => optional($distribution->distributed_at)->format('Y-m-d'),
                    'primary_name' => $family?->full_name ?? '-',
                    'national_id' => $family?->national_id ?? '-',
                    'housing_location' => $family?->address ?? '-',
                    'family_members_count' => $family?->family_members_count ?? '-',
                    'marital_status' => $this->translateMaritalStatus($family?->marital_status),
                    'office_name' => $distribution->office?->name ?? '-',
                    'aid_mode' => $distribution->aid_mode,
                    'aid_value' => $distribution->aid_mode === 'cash'
                        ? ($distribution->cash_amount ?? 0)
                        : ($distribution->aidItem?->name ?? '-'),
                    'quantity' => $distribution->aid_mode === 'in_kind'
                        ? ($distribution->quantity !== null ? number_format((float) $distribution->quantity, 2) : '-')
                        : '-',
                    'mobile' => $family?->phone ?? '-',
                    'creator_name' => $distribution->creator?->name ?? '-',
                ];
            })->values();

            return DataTables::of($rows)
                ->addIndexColumn()
                ->addColumn('edit', function ($row) {
                    return $row['id'];
                })
                ->addColumn('delete', function ($row) {
                    return $row['id'];
                })
                ->make(true);
        }

        return view('dashboard.aid_distributions.index');
    }

    public function create()
    {
        $this->authorize('create', AidDistribution::class);

        $offices = Office::query()->where('is_active', true)->orderBy('name')->get();
        $aidItems = AidItem::query()->where('is_active', true)->orderBy('name')->get();

        $distribution = new AidDistribution([
            'aid_mode' => 'cash',
            'distributed_at' => now()->format('Y-m-d\TH:i'),
            'office_id' => Auth::user()?->office_id,
        ]);
        $familyForm = null;
        $isEdit = false;

        return view('dashboard.aid_distributions.create', compact('offices', 'aidItems', 'distribution', 'familyForm', 'isEdit'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', AidDistribution::class);

        $validated = $this->validateForm($request);
        $officeId = $this->resolveOfficeIdForStore($validated);

        DB::beginTransaction();
        try {
            $family = $this->resolveFamilyForDistribution($validated);

            // إنشاء عملية الصرف
            AidDistribution::create([
                'family_id' => $family->id,
                'office_id' => $officeId,
                'aid_mode' => $validated['aid_mode'],
                'aid_item_id' => $validated['aid_mode'] === 'in_kind' ? $validated['aid_item_id'] : null,
                'quantity' => $validated['aid_mode'] === 'in_kind' ? $validated['quantity'] : null,
                'cash_amount' => $validated['aid_mode'] === 'cash' ? $validated['cash_amount'] : null,
                'distributed_at' => !empty($validated['distributed_date'])
                    ? Carbon::parse($validated['distributed_date'])->startOfDay()
                    : now()->startOfDay(),
                'created_by' => Auth::id(),
                'notes' => $validated['distribution_notes'] ?? null,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('dashboard.aid-distributions.index')->with('success', 'تم حفظ عملية المساعدة بنجاح');
    }

    /**
     * تحديد الأسرة المناسبة بناءً على منطق البحث والـ resolution_mode
     */
    private function resolveFamilyForDistribution(array $validated): Family
    {
        $nationalId = $validated['national_id'];
        $resolutionMode = $validated['resolution_mode'] ?? null;

        // البحث عن تطابق
        $primaryMatch = Family::query()->where('national_id', $nationalId)->first();
        $spouseMatch = $this->findFamilyBySpouseNationalId($nationalId);

        // الحالة 1: primary_match
        if ($primaryMatch) {
            // تحديث طبيعي
            $primaryMatch->update($this->extractFamilyData($validated));
            return $primaryMatch;
        }

        // الحالة 2: spouse_match
        if ($spouseMatch) {
            // يجب أن يكون resolution_mode موجود
            if (!$resolutionMode) {
                throw new \Exception('يجب اختيار طريقة التعامل مع الأسرة الموجودة');
            }

            if ($resolutionMode === 'attach_to_existing') {
                // إضافة المساعدة للأسرة القديمة فقط (بدون تحديث)
                return $spouseMatch;
            }

            if ($resolutionMode === 'create_new_family') {
                // إنشاء أسرة جديدة
                return Family::create($this->extractFamilyData($validated));
            }
        }

        // الحالة 3: no_match
        // إنشاء أسرة جديدة
        return Family::create($this->extractFamilyData($validated));
    }

    public function edit(AidDistribution $aidDistribution)
    {
        $this->authorize('update', AidDistribution::class);

        $offices = Office::query()->where('is_active', true)->orderBy('name')->get();
        $aidItems = AidItem::query()->where('is_active', true)->orderBy('name')->get();

        $family = $aidDistribution->family;
        $familyForm = $this->mapFamilyToForm($family);
        $distribution = $aidDistribution;
        $isEdit = true;

        return view('dashboard.aid_distributions.edit', compact('offices', 'aidItems', 'distribution', 'familyForm', 'isEdit'));
    }

    public function show(AidDistribution $aidDistribution)
    {
        $this->authorize('update', AidDistribution::class);

        return redirect()->route('dashboard.aid-distributions.edit', $aidDistribution->id);
    }

    public function update(Request $request, AidDistribution $aidDistribution)
    {
        $this->authorize('update', AidDistribution::class);

        $validated = $this->validateForm($request);
        $officeId = $this->resolveOfficeIdForUpdate($validated, $aidDistribution);

        DB::beginTransaction();
        try {
            $aidDistribution->family->update($this->extractFamilyData($validated));

            $aidDistribution->update([
                'office_id' => $officeId,
                'aid_mode' => $validated['aid_mode'],
                'aid_item_id' => $validated['aid_mode'] === 'in_kind' ? $validated['aid_item_id'] : null,
                'quantity' => $validated['aid_mode'] === 'in_kind' ? $validated['quantity'] : null,
                'cash_amount' => $validated['aid_mode'] === 'cash' ? $validated['cash_amount'] : null,
                'distributed_at' => !empty($validated['distributed_date'])
                    ? Carbon::parse($validated['distributed_date'])->startOfDay()
                    : $aidDistribution->distributed_at,
                'notes' => $validated['distribution_notes'] ?? null,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('dashboard.aid-distributions.index')->with('success', 'تم تحديث العملية بنجاح');
    }

    public function destroy(AidDistribution $aidDistribution)
    {
        $this->authorize('delete', AidDistribution::class);

        // $aidDistribution->update([
        //     'status' => 'cancelled',
        //     'cancelled_at' => now(),
        //     'cancelled_by' => Auth::id(),
        // ]);
        $aidDistribution->delete();

        if(request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('dashboard.aid-distributions.index')->with('success', 'تم حذف العملية بنجاح');
    }

    public function getFilterOptions(Request $request, $column)
    {
        $this->authorizeLookupForAidDistribution();

        $year = $request->year ?? Carbon::now()->year;

        $query = AidDistribution::query()
            ->with(['family', 'office', 'aidItem', 'creator'])
            ->whereYear('distributed_at', $year);

        if ($request->from_date) {
            $query->whereDate('distributed_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('distributed_at', '<=', $request->to_date);
        }

        if ($request->active_filters) {
            $this->applyColumnFilters($query, $request->active_filters);
        }

        $rows = $query->get();
        $options = match ($column) {
            'distributed_at' => $rows->pluck('distributed_at')->filter()->map(fn ($d) => $d->format('Y-m-d'))->unique()->values()->toArray(),
            'office_name' => $rows->pluck('office.name')->filter()->unique()->values()->toArray(),
            'aid_mode' => $rows->pluck('aid_mode')->filter()->unique()->values()->toArray(),
            'aid_value' => $rows->map(function (AidDistribution $d) {
                return $d->aid_mode === 'cash' ? (string) ($d->cash_amount ?? 0) : ($d->aidItem?->name ?? null);
            })->filter()->unique()->values()->toArray(),
            'quantity' => $rows->map(function (AidDistribution $d) {
                return $d->aid_mode === 'in_kind' && $d->quantity !== null
                    ? number_format((float) $d->quantity, 2)
                    : null;
            })->filter()->unique()->values()->toArray(),
            'primary_name' => $rows->pluck('family.full_name')->filter()->unique()->values()->toArray(),
            'national_id' => $rows->pluck('family.national_id')->filter()->unique()->values()->toArray(),
            'housing_location' => $rows->pluck('family.address')->filter()->unique()->values()->toArray(),
            'family_members_count' => $rows->pluck('family.family_members_count')->filter()->unique()->values()->toArray(),
            'marital_status' => $rows->pluck('family.marital_status')->filter()->map(fn ($value) => $this->translateMaritalStatus($value))->unique()->values()->toArray(),
            'mobile' => $rows->pluck('family.phone')->filter()->unique()->values()->toArray(),
            'creator_name' => $rows->pluck('creator.name')->filter()->unique()->values()->toArray(),
            default => [],
        };

        return response()->json($options);
    }

    private function validateForm(Request $request): array
    {
        $validated = $request->validate([
            'family_id' => 'nullable|exists:families,id',
            'resolution_mode' => 'nullable|in:attach_to_existing,create_new_family',
            'primary_name' => 'required|string|max:255',
            'national_id' => 'required|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'family_members_count' => 'nullable|integer|min:1',
            'housing_location' => 'nullable|string|max:255',
            'marital_status' => 'required|in:single,married,polygamous,widowed,divorced',
            'spouse_name' => 'nullable|string|max:255',
            'spouse_national_id' => 'nullable|string|max:20',
            'spouses' => 'nullable|array|max:4',
            'spouses.*.full_name' => 'nullable|string|max:255',
            'spouses.*.national_id' => 'nullable|string|max:20',

            'office_id' => 'required|exists:offices,id',
            'aid_mode' => 'required|in:cash,in_kind',
            'cash_amount' => 'nullable|numeric|min:0|required_if:aid_mode,cash',
            'aid_item_id' => 'nullable|exists:aid_items,id|required_if:aid_mode,in_kind',
            'quantity' => 'nullable|numeric|min:0.01|required_if:aid_mode,in_kind',
            'distributed_date' => 'nullable|date',
            'distribution_notes' => 'nullable|string',
        ]);

        $spouses = $this->normalizedSpousesFromInput($validated);
        $status = $validated['marital_status'];

        if (in_array($status, ['single', 'widowed', 'divorced'], true) && !empty($spouses)) {
            throw ValidationException::withMessages([
                'spouses' => 'لا يمكن إدخال بيانات الزوجات عند اختيار حالة اجتماعية غير متزوج.',
            ]);
        }

        if ($status === 'married' && empty($spouses[0]['national_id'])) {
            throw ValidationException::withMessages([
                'spouses.0.national_id' => 'رقم هوية الزوجة الأولى مطلوب عند اختيار متزوج/ة.',
            ]);
        }

        if ($status === 'polygamous') {
            if (count($spouses) < 2) {
                throw ValidationException::withMessages([
                    'spouses' => 'في حالة متعدد الزوجات يجب إدخال زوجتين على الأقل.',
                ]);
            }

            if (empty($spouses[0]['national_id']) || empty($spouses[1]['national_id'])) {
                throw ValidationException::withMessages([
                    'spouses.0.national_id' => 'رقم هوية الزوجة الأولى مطلوب.',
                    'spouses.1.national_id' => 'رقم هوية الزوجة الثانية مطلوب.',
                ]);
            }
        }

        $wifeNationalIds = collect($spouses)->pluck('national_id')->filter()->values();
        if ($wifeNationalIds->count() !== $wifeNationalIds->unique()->count()) {
            throw ValidationException::withMessages([
                'spouses' => 'لا يمكن تكرار رقم هوية الزوجة أكثر من مرة.',
            ]);
        }

        if ($wifeNationalIds->contains($validated['national_id'])) {
            throw ValidationException::withMessages([
                'spouses' => 'لا يمكن أن يكون رقم هوية المستفيد الأساسي هو نفسه رقم هوية الزوجة.',
            ]);
        }

        $validated['spouses'] = $spouses;

        return $validated;
    }

    private function extractFamilyData(array $validated): array
    {
        $status = $validated['marital_status'];
        $spouses = in_array($status, ['married', 'polygamous'], true)
            ? $this->normalizedSpousesFromInput($validated)
            : [];
        $firstSpouse = $spouses[0] ?? null;

        return [
            'full_name' => $validated['primary_name'],
            'national_id' => $validated['national_id'],
            'phone' => $validated['mobile'] ?? null,
            'family_members_count' => $validated['family_members_count'] ?? null,
            'address' => $validated['housing_location'] ?? null,
            'marital_status' => $status,
            'spouses' => !empty($spouses) ? $spouses : null,
            // توافق مؤقت مع الحقول القديمة
            'spouse_full_name' => $firstSpouse['full_name'] ?? null,
            'spouse_national_id' => $firstSpouse['national_id'] ?? null,
        ];
    }

    private function mapFamilyToForm(Family $family): array
    {
        $spouses = $this->getFamilySpouses($family);
        $firstSpouse = $spouses[0] ?? null;

        return [
            'primary_name' => $family->full_name,
            'national_id' => $family->national_id,
            'mobile' => $family->phone,
            'family_members_count' => $family->family_members_count,
            'housing_location' => $family->address,
            'marital_status' => $family->marital_status ?? 'single',
            'spouses' => $spouses,
            // توافق مؤقت مع الحقول القديمة في الواجهة
            'spouse_name' => $firstSpouse['full_name'] ?? null,
            'spouse_national_id' => $firstSpouse['national_id'] ?? null,
        ];
    }

    private function resolveOfficeIdForStore(array $validated): int
    {
        if (!$this->isEmployeeUser()) {
            return (int) $validated['office_id'];
        }

        $employeeOfficeId = Auth::user()?->office_id;
        if (!$employeeOfficeId) {
            throw ValidationException::withMessages([
                'office_id' => 'لا يمكن إتمام الحفظ لأن مكتب المستخدم غير محدد.',
            ]);
        }

        return (int) $employeeOfficeId;
    }

    private function resolveOfficeIdForUpdate(array $validated, AidDistribution $aidDistribution): int
    {
        if ($this->isEmployeeUser()) {
            return (int) $aidDistribution->office_id;
        }

        return (int) $validated['office_id'];
    }

    private function isEmployeeUser(): bool
    {
        return Auth::user()?->user_type === 'employee';
    }

    private function applyColumnFilters($query, array $columnFilters): void
    {
        foreach ($columnFilters as $fieldName => $values) {
            if (empty($values)) {
                continue;
            }

            if ($fieldName === 'distributed_at' && is_array($values)) {
                if (isset($values['from'])) {
                    $query->whereDate('distributed_at', '>=', $values['from']);
                }
                if (isset($values['to'])) {
                    $query->whereDate('distributed_at', '<=', $values['to']);
                }
                continue;
            }

            $filteredValues = array_values(array_filter((array) $values, function ($value) {
                return !in_array($value, ['الكل', 'all', 'All'], true);
            }));

            if (empty($filteredValues)) {
                continue;
            }

            switch ($fieldName) {
                case 'office_name':
                    $query->whereHas('office', fn ($q) => $q->whereIn('name', $filteredValues));
                    break;
                case 'creator_name':
                    $query->whereHas('creator', fn ($q) => $q->whereIn('name', $filteredValues));
                    break;
                case 'primary_name':
                    $query->whereHas('family', fn ($q) => $q->whereIn('full_name', $filteredValues));
                    break;
                case 'national_id':
                    $query->whereHas('family', fn ($q) => $q->whereIn('national_id', $filteredValues));
                    break;
                case 'mobile':
                    $query->whereHas('family', fn ($q) => $q->whereIn('phone', $filteredValues));
                    break;
                case 'family_members_count':
                    $query->whereHas('family', fn ($q) => $q->whereIn('family_members_count', $filteredValues));
                    break;
                case 'marital_status':
                    $maritalValues = array_map(function ($value) {
                        return match ($value) {
                            'متزوج/ة' => 'married',
                            'ارمل/ة' => 'widowed',
                            default => $value,
                        };
                    }, $filteredValues);
                    $query->whereHas('family', fn ($q) => $q->whereIn('marital_status', $maritalValues));
                    break;
                case 'housing_location':
                    $query->whereHas('family', fn ($q) => $q->whereIn('address', $filteredValues));
                    break;
                case 'aid_value':
                    $query->where(function ($subQ) use ($filteredValues) {
                        foreach ($filteredValues as $value) {
                            $subQ->orWhere('cash_amount', 'like', '%' . $value . '%')
                                ->orWhereHas('aidItem', fn ($q) => $q->where('name', 'like', '%' . $value . '%'));
                        }
                    });
                    break;
                case 'quantity':
                    $query->where(function ($subQ) use ($filteredValues) {
                        foreach ($filteredValues as $value) {
                            $subQ->orWhere('quantity', 'like', '%' . $value . '%');
                        }
                    });
                    break;
                default:
                    $query->whereIn($fieldName, $filteredValues);
                    break;
            }
        }
    }

    private function translateMaritalStatus(?string $status): string
    {
        return match ($status) {
            'single' => 'أعزب/عزباء',
            'married' => 'متزوج/ة',
            'widowed' => 'ارمل/ة',
            'divorced' => 'مطلق/ة',
            'polygamous' => 'متعدد الزوجات',
            default => '-',
        };
    }

    /**
     * API: Search for family by national ID (primary or spouse)
     */
    public function searchByNationalId(string $id)
    {
        $this->authorizeLookupForAidDistribution();

        // البحث في العمودين: national_id أو spouse_national_id
        $primaryMatch = Family::query()->where('national_id', $id)->first();
        $spouseMatch = $this->findFamilyBySpouseNationalId($id);

        // تحديد نوع التطابق
        if ($primaryMatch) {
            $family = $primaryMatch;
            $matchType = 'primary_match';
        } elseif ($spouseMatch) {
            $family = $spouseMatch;
            $matchType = 'spouse_match';
        } else {
            return response()->json(['match_type' => 'no_match']);
        }

        // جلب آخر 10 مساعدات (status=active فقط)
        $aids = $family->distributions()
            ->with(['office', 'aidItem'])
            ->where('status', 'active')
            ->orderBy('distributed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function (AidDistribution $dist) {
                return [
                    'id' => $dist->id,
                    'office_name' => $dist->office?->name ?? '-',
                    'distributed_at' => $dist->distributed_at?->format('Y-m-d') ?? '-',
                    'aid_mode' => $dist->aid_mode === 'cash' ? 'نقدية' : 'عينية',
                    'aid_value' => $dist->aid_mode === 'cash'
                        ? number_format($dist->cash_amount, 2) . ' ₪'
                        : ($dist->aidItem?->name ?? '-'),
                    'quantity' => $dist->aid_mode === 'in_kind' && $dist->quantity !== null
                        ? number_format((float) $dist->quantity, 2)
                        : '-',
                ];
            });

        // إجمالي عدد المساعدات
        $aidsTotal = $family->distributions()->where('status', 'active')->count();

        return response()->json([
            'match_type' => $matchType,
            'family' => [
                'id' => $family->id,
                'national_id' => $family->national_id,
                'full_name' => $family->full_name,
                'phone' => $family->phone,
                'family_members_count' => $family->family_members_count,
                'address' => $family->address,
                'marital_status' => $family->marital_status,
                'spouses' => $this->getFamilySpouses($family),
                'spouse_national_id' => $this->getFamilySpouses($family)[0]['national_id'] ?? null,
                'spouse_full_name' => $this->getFamilySpouses($family)[0]['full_name'] ?? null,
            ],
            'last_10_aids' => $aids,
            'total_aids' => $aidsTotal,
        ]);
    }

    /**
     * API: Get all aids for a family (for lazy load)
     */
    public function getAllAids(int $familyId)
    {
        $this->authorizeLookupForAidDistribution();

        $family = Family::findOrFail($familyId);

        $aids = $family->distributions()
            ->with(['office', 'aidItem'])
            ->where('status', 'active')
            ->orderBy('distributed_at', 'desc')
            ->get()
            ->map(function (AidDistribution $dist) {
                return [
                    'id' => $dist->id,
                    'office_name' => $dist->office?->name ?? '-',
                    'distributed_at' => $dist->distributed_at?->format('Y-m-d') ?? '-',
                    'aid_mode' => $dist->aid_mode === 'cash' ? 'نقدية' : 'عينية',
                    'aid_value' => $dist->aid_mode === 'cash'
                        ? number_format($dist->cash_amount, 2) . ' ₪'
                        : ($dist->aidItem?->name ?? '-'),
                    'quantity' => $dist->aid_mode === 'in_kind' && $dist->quantity !== null
                        ? number_format((float) $dist->quantity, 2)
                        : '-',
                ];
            });

        return response()->json([
            'aids' => $aids,
            'total' => $aids->count(),
        ]);
    }

    /**
     * API: Show single aid distribution details for modal
     */
    public function showAidDistribution(int $id)
    {
        $this->authorizeLookupForAidDistribution();

        $distribution = AidDistribution::with(['family', 'office', 'aidItem', 'creator'])
            ->findOrFail($id);

        return response()->json([
            'distribution' => [
                'id' => $distribution->id,
                'office_name' => $distribution->office?->name ?? '-',
                'aid_mode' => $distribution->aid_mode === 'cash' ? 'نقدية' : 'عينية',
                'cash_amount' => $distribution->cash_amount,
                'aid_item_name' => $distribution->aidItem?->name ?? '-',
                'quantity' => $distribution->quantity,
                'distributed_at' => $distribution->distributed_at?->format('Y-m-d') ?? '-',
                'notes' => $distribution->notes,
                'status' => $distribution->status,
                'creator_name' => $distribution->creator?->name ?? '-',
            ],
            'family' => [
                'full_name' => $distribution->family?->full_name ?? '-',
                'national_id' => $distribution->family?->national_id ?? '-',
                'phone' => $distribution->family?->phone ?? '-',
                'family_members_count' => $distribution->family?->family_members_count ?? '-',
                'address' => $distribution->family?->address ?? '-',
                'marital_status' => $this->translateMaritalStatus($distribution->family?->marital_status),
                'spouses' => $distribution->family ? $this->getFamilySpouses($distribution->family) : [],
                'spouse_full_name' => $distribution->family ? ($this->getFamilySpouses($distribution->family)[0]['full_name'] ?? '-') : '-',
                'spouse_national_id' => $distribution->family ? ($this->getFamilySpouses($distribution->family)[0]['national_id'] ?? '-') : '-',
            ],
        ]);
    }

    private function findFamilyBySpouseNationalId(string $nationalId): ?Family
    {
        return Family::query()
            ->where(function ($query) use ($nationalId) {
                $query->where('wife_1_national_id_gen', $nationalId)
                    ->orWhere('wife_2_national_id_gen', $nationalId)
                    ->orWhere('wife_3_national_id_gen', $nationalId)
                    ->orWhere('wife_4_national_id_gen', $nationalId)
                    // fallback مؤقت للسجلات القديمة قبل الترحيل الكامل
                    ->orWhere('spouse_national_id', $nationalId);
            })
            ->first();
    }

    private function normalizedSpousesFromInput(array $validated): array
    {
        $spouses = collect($validated['spouses'] ?? [])->map(function ($spouse) {
            $fullName = isset($spouse['full_name']) ? trim((string) $spouse['full_name']) : null;
            $nationalId = isset($spouse['national_id']) ? trim((string) $spouse['national_id']) : null;

            return [
                'full_name' => $fullName !== '' ? $fullName : null,
                'national_id' => $nationalId !== '' ? $nationalId : null,
            ];
        });

        // fallback للواجهة القديمة إن أرسلت الحقول المفردة
        $legacySpouseName = trim((string) ($validated['spouse_name'] ?? ''));
        $legacySpouseNationalId = trim((string) ($validated['spouse_national_id'] ?? ''));
        if ($spouses->isEmpty() && ($legacySpouseName !== '' || $legacySpouseNationalId !== '')) {
            $spouses = collect([[
                'full_name' => $legacySpouseName !== '' ? $legacySpouseName : null,
                'national_id' => $legacySpouseNationalId !== '' ? $legacySpouseNationalId : null,
            ]]);
        }

        return $spouses
            ->filter(fn ($spouse) => !empty($spouse['full_name']) || !empty($spouse['national_id']))
            ->take(4)
            ->values()
            ->toArray();
    }

    private function getFamilySpouses(Family $family): array
    {
        return $family->wives;
    }

    private function authorizeLookupForAidDistribution(): void
    {
        $user = Auth::user();

        if (
            $user?->can('view', AidDistribution::class) ||
            $user?->can('create', AidDistribution::class) ||
            $user?->can('update', AidDistribution::class)
        ) {
            return;
        }

        abort(403);
    }
}
