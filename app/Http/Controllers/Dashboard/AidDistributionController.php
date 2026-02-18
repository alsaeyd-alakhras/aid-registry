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
use Yajra\DataTables\Facades\DataTables;

class AidDistributionController extends Controller
{
    public function index(Request $request)
    {
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
        $offices = Office::query()->where('is_active', true)->orderBy('name')->get();
        $aidItems = AidItem::query()->where('is_active', true)->orderBy('name')->get();

        $distribution = new AidDistribution([
            'aid_mode' => 'cash',
            'distributed_at' => now()->format('Y-m-d\TH:i'),
            'office_id' => Auth::user()?->office_id,
        ]);
        $familyForm = null;

        return view('dashboard.aid_distributions.create', compact('offices', 'aidItems', 'distribution', 'familyForm'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateForm($request);

        DB::beginTransaction();
        try {
            $family = $this->resolveFamilyForDistribution($validated);

            // إنشاء عملية الصرف
            AidDistribution::create([
                'family_id' => $family->id,
                'office_id' => $validated['office_id'],
                'aid_mode' => $validated['aid_mode'],
                'aid_item_id' => $validated['aid_mode'] === 'in_kind' ? $validated['aid_item_id'] : null,
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
        $spouseMatch = Family::query()->where('spouse_national_id', $nationalId)->first();

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
        $offices = Office::query()->where('is_active', true)->orderBy('name')->get();
        $aidItems = AidItem::query()->where('is_active', true)->orderBy('name')->get();

        $family = $aidDistribution->family;
        $familyForm = $this->mapFamilyToForm($family);
        $distribution = $aidDistribution;

        return view('dashboard.aid_distributions.edit', compact('offices', 'aidItems', 'distribution', 'familyForm'));
    }

    public function show(AidDistribution $aidDistribution)
    {
        return redirect()->route('dashboard.aid-distributions.edit', $aidDistribution->id);
    }

    public function update(Request $request, AidDistribution $aidDistribution)
    {
        $validated = $this->validateForm($request);

        DB::beginTransaction();
        try {
            $aidDistribution->family->update($this->extractFamilyData($validated));

            $aidDistribution->update([
                'office_id' => $validated['office_id'],
                'aid_mode' => $validated['aid_mode'],
                'aid_item_id' => $validated['aid_mode'] === 'in_kind' ? $validated['aid_item_id'] : null,
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
        $aidDistribution->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => Auth::id(),
        ]);

        return redirect()->route('dashboard.aid-distributions.create')->with('success', 'تم إلغاء العملية بنجاح');
    }

    public function getFilterOptions(Request $request, $column)
    {
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
        return $request->validate([
            'family_id' => 'nullable|exists:families,id',
            'resolution_mode' => 'nullable|in:attach_to_existing,create_new_family',
            'primary_name' => 'required|string|max:255',
            'national_id' => 'required|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'family_members_count' => 'nullable|integer|min:1',
            'housing_location' => 'nullable|string|max:255',
            'marital_status' => 'required|in:single,married,widowed,divorced',
            'spouse_name' => 'nullable|string|max:255|required_if:marital_status,married',
            'spouse_national_id' => 'nullable|string|max:20|required_if:marital_status,married',

            'office_id' => 'required|exists:offices,id',
            'aid_mode' => 'required|in:cash,in_kind',
            'cash_amount' => 'nullable|numeric|min:0|required_if:aid_mode,cash',
            'aid_item_id' => 'nullable|exists:aid_items,id|required_if:aid_mode,in_kind',
            'distributed_date' => 'nullable|date',
            'distribution_notes' => 'nullable|string',
        ]);
    }

    private function extractFamilyData(array $validated): array
    {
        return [
            'full_name' => $validated['primary_name'],
            'national_id' => $validated['national_id'],
            'phone' => $validated['mobile'] ?? null,
            'family_members_count' => $validated['family_members_count'] ?? null,
            'address' => $validated['housing_location'] ?? null,
            'marital_status' => $validated['marital_status'],
            'spouse_full_name' => $validated['marital_status'] === 'married' ? ($validated['spouse_name'] ?? null) : null,
            'spouse_national_id' => $validated['marital_status'] === 'married' ? ($validated['spouse_national_id'] ?? null) : null,
        ];
    }

    private function mapFamilyToForm(Family $family): array
    {
        return [
            'primary_name' => $family->full_name,
            'national_id' => $family->national_id,
            'mobile' => $family->phone,
            'family_members_count' => $family->family_members_count,
            'housing_location' => $family->address,
            'marital_status' => $family->marital_status ?? 'single',
            'spouse_name' => $family->spouse_full_name,
            'spouse_national_id' => $family->spouse_national_id,
        ];
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
                default:
                    $query->whereIn($fieldName, $filteredValues);
                    break;
            }
        }
    }

    private function translateMaritalStatus(?string $status): string
    {
        return match ($status) {
            'married' => 'متزوج/ة',
            'widowed' => 'ارمل/ة',
            default => '-',
        };
    }

    /**
     * API: Search for family by national ID (primary or spouse)
     */
    public function searchByNationalId(string $id)
    {
        // البحث في العمودين: national_id أو spouse_national_id
        $primaryMatch = Family::query()->where('national_id', $id)->first();
        $spouseMatch = Family::query()->where('spouse_national_id', $id)->first();

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
                'spouse_national_id' => $family->spouse_national_id,
                'spouse_full_name' => $family->spouse_full_name,
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
        $distribution = AidDistribution::with(['family', 'office', 'aidItem', 'creator'])
            ->findOrFail($id);

        return response()->json([
            'distribution' => [
                'id' => $distribution->id,
                'office_name' => $distribution->office?->name ?? '-',
                'aid_mode' => $distribution->aid_mode === 'cash' ? 'نقدية' : 'عينية',
                'cash_amount' => $distribution->cash_amount,
                'aid_item_name' => $distribution->aidItem?->name ?? '-',
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
                'spouse_full_name' => $distribution->family?->spouse_full_name ?? '-',
                'spouse_national_id' => $distribution->family?->spouse_national_id ?? '-',
            ],
        ]);
    }
}
