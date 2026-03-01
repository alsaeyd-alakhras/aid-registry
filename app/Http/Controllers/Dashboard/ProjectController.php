<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AidDistribution;
use App\Models\AidItem;
use App\Models\Institution;
use App\Models\Office;
use App\Models\Project;
use App\Models\ProjectStat;
use App\Services\ProjectNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', Project::class);

        if ($request->ajax()) {
            $projects = Project::query()
                ->with(['institution', 'aidItem', 'creator', 'dependencyOffice'])
                ->orderBy('created_at', 'desc');

            if ($request->column_filters) {
                $this->applyColumnFilters($projects, $request->column_filters);
            }

            $rows = $projects->get()->map(function (Project $project) {
                return [
                    'id' => $project->id,
                    'project_number' => $project->project_number,
                    'name' => $project->name,
                    'institution_name' => $project->institution?->name ?? '-',
                    'project_type' => $project->project_type === 'cash' ? 'نقدي' : 'عيني',
                    'aid_item_name' => $project->project_type === 'in_kind' ? ($project->aidItem?->name ?? '-') : '-',
                    'total_display' => $project->project_type === 'cash'
                        ? number_format($project->total_amount_ils, 2) . ' ₪'
                        : number_format($project->total_quantity, 2),
                    'consumed_display' => $project->project_type === 'cash'
                        ? number_format($project->consumed_amount, 2) . ' ₪'
                        : number_format($project->consumed_quantity, 2),
                    'remaining_display' => $project->project_type === 'cash'
                        ? number_format($project->remaining_amount, 2) . ' ₪'
                        : number_format($project->remaining_quantity, 2),
                    'beneficiaries_total' => $project->beneficiaries_total,
                    'beneficiaries_consumed' => $project->beneficiaries_consumed,
                    'beneficiaries_remaining' => $project->remaining_beneficiaries,
                    'creator_name' => $project->creator?->name ?? '-',
                    'dependency_display' => $project->dependency_type === 'admin'
                        ? 'الإدارة'
                        : ($project->dependencyOffice?->name ?? '-'),
                    'status' => $project->status ?? 'active',
                    'status_display' => ($project->status ?? 'active') === 'active' ? 'فعال' : 'مغلق',
                    'can_edit' => Auth::user()->can('update', $project),
                    'can_delete' => Auth::user()->can('delete', $project),
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

        return view('dashboard.projects.index');
    }

    public function create()
    {
        $this->authorize('create', Project::class);

        $institutions = Institution::query()->where('is_active', true)->orderBy('name')->get();
        $aidItems = AidItem::query()->where('is_active', true)->orderBy('name')->get();
        $offices = Office::query()->where('is_active', true)->orderBy('name')->get();

        $project = new Project([
            'project_type' => 'cash',
            'beneficiaries_total' => 0,
            'status' => 'active',
        ]);
        $isEdit = false;

        return view('dashboard.projects.create', compact('institutions', 'aidItems', 'offices', 'project', 'isEdit'));
    }

    public function store(Request $request, ProjectNumberService $numberService)
    {
        $this->authorize('create', Project::class);

        try {
            $validated = $this->validateForm($request);

            DB::beginTransaction();
            try {
                $user = Auth::user();
                $dependencyType = $user?->user_type === 'employee' ? 'office' : 'admin';
                $dependencyOfficeId = $dependencyType === 'office' ? $user?->office_id : null;

                // تسلسل تلقائي (معطل حالياً - استخدام رقم يدوي)
                // $projectNumber = $numberService->generateNumber($dependencyType, $dependencyOfficeId);
                
                // استخدام الرقم اليدوي من المستخدم
                $projectNumber = $validated['project_number'];

                $status = 'active';
                if ($user->user_type !== 'employee' && isset($validated['status'])) {
                    $status = $validated['status'];
                }

                $project = Project::create([
                    'project_number' => $projectNumber,
                    'name' => $validated['name'],
                    'institution_id' => $validated['institution_id'],
                    'project_type' => $validated['project_type'],
                    'aid_item_id' => $validated['project_type'] === 'in_kind' ? $validated['aid_item_id'] : null,
                    'total_quantity' => $validated['project_type'] === 'in_kind' ? $validated['total_quantity'] : 0,
                    'total_amount_ils' => $validated['project_type'] === 'cash' ? $validated['total_amount_ils'] : 0,
                    'estimated_amount' => $validated['estimated_amount'] ?? null,
                    'beneficiaries_total' => $validated['beneficiaries_total'],
                    'consumed_quantity' => 0,
                    'consumed_amount' => 0,
                    'beneficiaries_consumed' => 0,
                    'created_by' => Auth::id(),
                    'dependency_type' => $dependencyType,
                    'dependency_office_id' => $dependencyOfficeId,
                    'notes' => $validated['notes'] ?? null,
                    'project_date' => $validated['project_date'] ?? null,
                    'execution_date' => $validated['execution_date'] ?? null,
                    'receipt_date' => $validated['receipt_date'] ?? null,
                    'department' => $validated['department'] ?? null,
                    'supervisor_name' => $validated['supervisor_name'] ?? null,
                    'execution_location' => $validated['execution_location'] ?? null,
                    'status' => $status,
                ]);

                $this->saveAllocations($project, $request->input('allocations', []));

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return redirect()->route('dashboard.projects.index')
                ->with('success', 'تم حفظ المشروع بنجاح ✓');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('danger', 'حدث خطأ أثناء حفظ المشروع: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        $institutions = Institution::query()->where('is_active', true)->orderBy('name')->get();
        $aidItems = AidItem::query()->where('is_active', true)->orderBy('name')->get();
        $offices = Office::query()->where('is_active', true)->orderBy('name')->get();

        $isEdit = true;

        return view('dashboard.projects.edit', compact('institutions', 'aidItems', 'offices', 'project', 'isEdit'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        try {
            $validated = $this->validateForm($request);

            DB::beginTransaction();
            try {
                $updateData = [
                    'project_number' => $validated['project_number'],
                    'name' => $validated['name'],
                    'institution_id' => $validated['institution_id'],
                    'project_type' => $validated['project_type'],
                    'aid_item_id' => $validated['project_type'] === 'in_kind' ? $validated['aid_item_id'] : null,
                    'total_quantity' => $validated['project_type'] === 'in_kind' ? $validated['total_quantity'] : 0,
                    'total_amount_ils' => $validated['project_type'] === 'cash' ? $validated['total_amount_ils'] : 0,
                    'estimated_amount' => $validated['estimated_amount'] ?? null,
                    'beneficiaries_total' => $validated['beneficiaries_total'],
                    'notes' => $validated['notes'] ?? null,
                    'project_date' => $validated['project_date'] ?? null,
                    'execution_date' => $validated['execution_date'] ?? null,
                    'receipt_date' => $validated['receipt_date'] ?? null,
                    'department' => $validated['department'] ?? null,
                    'supervisor_name' => $validated['supervisor_name'] ?? null,
                    'execution_location' => $validated['execution_location'] ?? null,
                ];
                if (Auth::user()->user_type !== 'employee' && isset($validated['status'])) {
                    $updateData['status'] = $validated['status'];
                }
                $project->update($updateData);

                $project->officeAllocations()->delete();
                $this->saveAllocations($project, $request->input('allocations', []));

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return redirect()->route('dashboard.projects.index')
                ->with('success', 'تم تحديث المشروع بنجاح ✓');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('danger', 'حدث خطأ أثناء تحديث المشروع: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        if ($project->aidDistributions()->exists()) {
            if (request()->ajax()) {
                return response()->json(['error' => 'لا يمكن حذف المشروع لأنه مرتبط بعمليات صرف'], 422);
            }
            return redirect()->route('dashboard.projects.index')->with('danger', 'لا يمكن حذف المشروع لأنه مرتبط بعمليات صرف');
        }

        $project->delete();

        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('dashboard.projects.index')->with('success', 'تم حذف المشروع بنجاح');
    }

    public function getFilterOptions(Request $request, $column)
    {
        $this->authorize('view', Project::class);

        $query = Project::query()->with(['institution', 'aidItem', 'creator', 'dependencyOffice']);

        if ($request->active_filters) {
            $this->applyColumnFilters($query, $request->active_filters);
        }

        $rows = $query->get();
        $options = match ($column) {
            'project_number' => $rows->pluck('project_number')->filter()->unique()->values()->toArray(),
            'name' => $rows->pluck('name')->filter()->unique()->values()->toArray(),
            'institution_name' => $rows->pluck('institution.name')->filter()->unique()->values()->toArray(),
            'project_type' => $rows->pluck('project_type')->filter()->map(fn($t) => $t === 'cash' ? 'نقدي' : 'عيني')->unique()->values()->toArray(),
            'aid_item_name' => $rows->filter(fn($p) => $p->project_type === 'in_kind')->pluck('aidItem.name')->filter()->unique()->values()->toArray(),
            'creator_name' => $rows->pluck('creator.name')->filter()->unique()->values()->toArray(),
            'dependency_display' => $rows->map(fn($p) => $p->dependency_type === 'admin' ? 'الإدارة' : ($p->dependencyOffice?->name ?? '-'))->unique()->values()->toArray(),
            'status_display' => $rows->pluck('status')->filter()->map(fn($s) => $s === 'active' ? 'فعال' : 'مغلق')->unique()->values()->toArray(),
            default => [],
        };

        return response()->json($options);
    }

    private function validateForm(Request $request): array
    {
        $projectId = $request->route('project') ? $request->route('project')->id : null;
        
        $validated = $request->validate([
            'project_number' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('projects', 'project_number')->ignore($projectId),
            ],
            'name' => 'required|string|max:255',
            'institution_id' => 'required|exists:institutions,id',
            'project_type' => 'required|in:cash,in_kind',
            'aid_item_id' => 'nullable|exists:aid_items,id|required_if:project_type,in_kind',
            'total_quantity' => 'nullable|numeric|min:0.01|required_if:project_type,in_kind',
            'total_amount_ils' => 'nullable|numeric|min:0.01|required_if:project_type,cash',
            'estimated_amount' => 'nullable|numeric|min:0',
            'beneficiaries_total' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'project_date' => 'nullable|date',
            'execution_date' => 'nullable|date',
            'receipt_date' => 'nullable|date',
            'department' => 'nullable|string|max:255',
            'supervisor_name' => 'nullable|string|max:255',
            'execution_location' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,closed',
        ], [
            'project_number.required' => 'رقم المشروع مطلوب',
            'project_number.unique' => 'رقم المشروع موجود مسبقاً، يرجى اختيار رقم آخر',
        ]);

        if ($validated['project_type'] === 'in_kind' && empty($validated['aid_item_id'])) {
            throw ValidationException::withMessages([
                'aid_item_id' => 'نوع المساعدة العينية مطلوب للمشاريع العينية.',
            ]);
        }

        if ($validated['project_type'] === 'cash' && empty($validated['total_amount_ils'])) {
            throw ValidationException::withMessages([
                'total_amount_ils' => 'المبلغ الإجمالي مطلوب للمشاريع النقدية.',
            ]);
        }

        return $validated;
    }

    private function applyColumnFilters($query, array $columnFilters): void
    {
        foreach ($columnFilters as $fieldName => $values) {
            if (empty($values)) {
                continue;
            }

            $filteredValues = array_values(array_filter((array) $values, function ($value) {
                return !in_array($value, ['الكل', 'all', 'All'], true);
            }));

            if (empty($filteredValues)) {
                continue;
            }

            switch ($fieldName) {
                case 'institution_name':
                    $query->whereHas('institution', fn($q) => $q->whereIn('name', $filteredValues));
                    break;
                case 'aid_item_name':
                    $query->whereHas('aidItem', fn($q) => $q->whereIn('name', $filteredValues));
                    break;
                case 'creator_name':
                    $query->whereHas('creator', fn($q) => $q->whereIn('name', $filteredValues));
                    break;
                case 'project_type':
                    $mapped = array_map(fn($v) => $v === 'نقدي' ? 'cash' : ($v === 'عيني' ? 'in_kind' : $v), $filteredValues);
                    $query->whereIn('project_type', $mapped);
                    break;
                case 'status_display':
                    $mapped = array_map(fn($v) => $v === 'فعال' ? 'active' : ($v === 'مغلق' ? 'closed' : $v), $filteredValues);
                    $query->whereIn('status', $mapped);
                    break;
                default:
                    $query->whereIn($fieldName, $filteredValues);
                    break;
            }
        }
    }

    public function getProjectsByInstitution(Request $request, int $institutionId)
    {
        $this->authorize('view', Project::class);

        $officeId = $request->query('office_id') ? (int) $request->query('office_id') : null;
        $includeProjectId = $request->query('include_project_id') ? (int) $request->query('include_project_id') : null;

        $projects = ProjectStat::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'active')
            ->orderBy('project_number')
            ->get();

        $result = [];
        $resultIds = [];
        foreach ($projects as $project) {
            $projectModel = Project::with('officeAllocations')->find($project->id);
            $hasAllocations = $projectModel && $projectModel->officeAllocations()->exists();

            if ($hasAllocations && $officeId === null) {
                continue;
            }

            if ($officeId !== null && $hasAllocations) {
                $allocation = $projectModel->officeAllocations()->where('office_id', $officeId)->first();
                if (!$allocation) {
                    continue;
                }
                $consumed = AidDistribution::query()
                    ->where('project_id', $project->id)
                    ->where('office_id', $officeId)
                    ->where('status', 'active')
                    ->selectRaw('COUNT(*) as beneficiaries_count')
                    ->selectRaw('SUM(CASE WHEN aid_mode = "cash" THEN cash_amount ELSE 0 END) as total_cash')
                    ->selectRaw('SUM(CASE WHEN aid_mode = "in_kind" THEN quantity ELSE 0 END) as total_quantity')
                    ->first();

                $consumedBeneficiaries = (int) ($consumed->beneficiaries_count ?? 0);
                $consumedCash = (float) ($consumed->total_cash ?? 0);
                $consumedQuantity = (float) ($consumed->total_quantity ?? 0);

                $officeRemainingBeneficiaries = max(0, $allocation->max_beneficiaries - $consumedBeneficiaries);
                $officeRemainingAmount = $allocation->max_amount !== null ? max(0, (float) $allocation->max_amount - $consumedCash) : null;
                $officeRemainingQuantity = $allocation->max_quantity !== null ? max(0, (float) $allocation->max_quantity - $consumedQuantity) : null;

                $canUse = $officeRemainingBeneficiaries > 0
                    && ($project->project_type !== 'cash' || $officeRemainingAmount === null || $officeRemainingAmount > 0)
                    && ($project->project_type !== 'in_kind' || $officeRemainingQuantity === null || $officeRemainingQuantity > 0);

                if (!$canUse) {
                    continue;
                }

                $result[] = [
                    'id' => $project->id,
                    'project_number' => $project->project_number,
                    'name' => $project->name,
                    'project_type' => $project->project_type,
                    'aid_item_id' => $project->aid_item_id,
                    'remaining_amount' => $officeRemainingAmount ?? (float) $project->remaining_amount,
                    'remaining_quantity' => $officeRemainingQuantity ?? (float) $project->remaining_quantity,
                    'remaining_beneficiaries' => $officeRemainingBeneficiaries,
                    'total_amount' => (float) $project->total_amount_ils,
                    'total_quantity' => (float) $project->total_quantity,
                    'beneficiaries_total' => (int) $project->beneficiaries_total,
                    'by_office' => true,
                    'is_closed' => false,
                ];
                $resultIds[] = $project->id;
            } else {
                if ($project->remaining_beneficiaries <= 0) {
                    continue;
                }
                $result[] = [
                    'id' => $project->id,
                    'project_number' => $project->project_number,
                    'name' => $project->name,
                    'project_type' => $project->project_type,
                    'aid_item_id' => $project->aid_item_id,
                    'remaining_amount' => (float) $project->remaining_amount,
                    'remaining_quantity' => (float) $project->remaining_quantity,
                    'remaining_beneficiaries' => (int) $project->remaining_beneficiaries,
                    'total_amount' => (float) $project->total_amount_ils,
                    'total_quantity' => (float) $project->total_quantity,
                    'beneficiaries_total' => (int) $project->beneficiaries_total,
                    'by_office' => false,
                    'is_closed' => false,
                ];
                $resultIds[] = $project->id;
            }
        }

        if ($includeProjectId && !in_array($includeProjectId, $resultIds, true)) {
            $includedProject = ProjectStat::query()
                ->where('id', $includeProjectId)
                ->where('institution_id', $institutionId)
                ->first();
            if ($includedProject && ($includedProject->status ?? 'active') === 'closed') {
                $result[] = [
                    'id' => $includedProject->id,
                    'project_number' => $includedProject->project_number,
                    'name' => $includedProject->name,
                    'project_type' => $includedProject->project_type,
                    'aid_item_id' => $includedProject->aid_item_id,
                    'remaining_amount' => (float) $includedProject->remaining_amount,
                    'remaining_quantity' => (float) $includedProject->remaining_quantity,
                    'remaining_beneficiaries' => (int) $includedProject->remaining_beneficiaries,
                    'total_amount' => (float) $includedProject->total_amount_ils,
                    'total_quantity' => (float) $includedProject->total_quantity,
                    'beneficiaries_total' => (int) $includedProject->beneficiaries_total,
                    'by_office' => false,
                    'is_closed' => true,
                ];
            }
        }

        return response()->json($result);
    }

    public function getProjectStats(Request $request, int $projectId)
    {
        $this->authorize('view', Project::class);

        $officeId = $request->query('office_id') ? (int) $request->query('office_id') : null;
        $project = ProjectStat::query()->findOrFail($projectId);
        if (($project->status ?? 'active') !== 'active') {
            abort(404, 'المشروع مغلق ولا يقبل صرفاً.');
        }
        $projectModel = Project::with('officeAllocations')->find($projectId);
        $hasAllocations = $projectModel && $projectModel->officeAllocations()->exists();

        $remainingAmount = (float) $project->remaining_amount;
        $remainingQuantity = (float) $project->remaining_quantity;
        $remainingBeneficiaries = (int) $project->remaining_beneficiaries;
        $byOffice = false;

        if ($officeId !== null && $hasAllocations) {
            $allocation = $projectModel->officeAllocations()->where('office_id', $officeId)->first();
            if ($allocation) {
                $consumed = AidDistribution::query()
                    ->where('project_id', $projectId)
                    ->where('office_id', $officeId)
                    ->where('status', 'active')
                    ->selectRaw('COUNT(*) as beneficiaries_count')
                    ->selectRaw('SUM(CASE WHEN aid_mode = "cash" THEN cash_amount ELSE 0 END) as total_cash')
                    ->selectRaw('SUM(CASE WHEN aid_mode = "in_kind" THEN quantity ELSE 0 END) as total_quantity')
                    ->first();

                $consumedBeneficiaries = (int) ($consumed->beneficiaries_count ?? 0);
                $consumedCash = (float) ($consumed->total_cash ?? 0);
                $consumedQuantity = (float) ($consumed->total_quantity ?? 0);

                $remainingBeneficiaries = max(0, $allocation->max_beneficiaries - $consumedBeneficiaries);
                $remainingAmount = $allocation->max_amount !== null ? max(0, (float) $allocation->max_amount - $consumedCash) : (float) $project->remaining_amount;
                $remainingQuantity = $allocation->max_quantity !== null ? max(0, (float) $allocation->max_quantity - $consumedQuantity) : (float) $project->remaining_quantity;
                $byOffice = true;
            }
        }

        return response()->json([
            'id' => $project->id,
            'project_number' => $project->project_number,
            'name' => $project->name,
            'project_type' => $project->project_type,
            'aid_item_id' => $project->aid_item_id,
            'total_amount' => (float) $project->total_amount_ils,
            'consumed_amount' => (float) $project->consumed_amount,
            'remaining_amount' => $remainingAmount,
            'total_quantity' => (float) $project->total_quantity,
            'consumed_quantity' => (float) $project->consumed_quantity,
            'remaining_quantity' => $remainingQuantity,
            'beneficiaries_total' => (int) $project->beneficiaries_total,
            'beneficiaries_consumed' => (int) $project->beneficiaries_consumed,
            'remaining_beneficiaries' => $remainingBeneficiaries,
            'by_office' => $byOffice,
        ]);
    }

    public function getProjectBreakdown(int $projectId)
    {
        $this->authorize('view', Project::class);

        $project = Project::query()->with('institution', 'aidItem')->findOrFail($projectId);

        $breakdown = AidDistribution::query()
            ->where('project_id', $projectId)
            ->where('status', 'active')
            ->with(['office', 'creator'])
            ->select('office_id', 'created_by')
            ->selectRaw('COUNT(*) as beneficiaries')
            ->selectRaw("SUM(CASE WHEN aid_mode = 'cash' THEN cash_amount ELSE 0 END) as total_cash")
            ->selectRaw("SUM(CASE WHEN aid_mode = 'in_kind' THEN quantity ELSE 0 END) as total_quantity")
            ->groupBy('office_id', 'created_by')
            ->get()
            ->map(function ($item) {
                return [
                    'office_name' => $item->office?->name ?? '-',
                    'creator_name' => $item->creator?->name ?? '-',
                    'beneficiaries' => (int) $item->beneficiaries,
                    'total_cash' => (float) $item->total_cash,
                    'total_quantity' => (float) $item->total_quantity,
                ];
            });

        return response()->json([
            'project' => [
                'project_number' => $project->project_number,
                'name' => $project->name,
                'institution_name' => $project->institution?->name ?? '-',
                'project_type' => $project->project_type,
                'aid_item_name' => $project->aidItem?->name ?? '-',
            ],
            'breakdown' => $breakdown,
        ]);
    }

    private function saveAllocations(Project $project, array $allocations): void
    {
        foreach ($allocations as $officeId => $data) {
            if (!isset($data['enabled']) || $data['enabled'] != 1) {
                continue;
            }

            $maxBeneficiaries = (int) ($data['max_beneficiaries'] ?? 0);
            $maxAmount = isset($data['max_amount']) && $data['max_amount'] !== '' ? (float) $data['max_amount'] : null;
            $maxQuantity = isset($data['max_quantity']) && $data['max_quantity'] !== '' ? (float) $data['max_quantity'] : null;

            if ($maxBeneficiaries > 0 || $maxAmount > 0 || $maxQuantity > 0) {
                $project->officeAllocations()->create([
                    'office_id' => $officeId,
                    'max_beneficiaries' => $maxBeneficiaries,
                    'max_amount' => $maxAmount,
                    'max_quantity' => $maxQuantity,
                ]);
            }
        }
    }
}
