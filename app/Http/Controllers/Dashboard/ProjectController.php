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

                $projectNumber = $numberService->generateNumber($dependencyType, $dependencyOfficeId);

                Project::create([
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
                ]);

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
        $this->authorize('update', Project::class);

        $institutions = Institution::query()->where('is_active', true)->orderBy('name')->get();
        $aidItems = AidItem::query()->where('is_active', true)->orderBy('name')->get();
        $offices = Office::query()->where('is_active', true)->orderBy('name')->get();

        $isEdit = true;

        return view('dashboard.projects.edit', compact('institutions', 'aidItems', 'offices', 'project', 'isEdit'));
    }

    public function update(Request $request, Project $project)
    {
        $this->authorize('update', Project::class);

        try {
            $validated = $this->validateForm($request);

            DB::beginTransaction();
            try {
                $project->update([
                    'name' => $validated['name'],
                    'institution_id' => $validated['institution_id'],
                    'project_type' => $validated['project_type'],
                    'aid_item_id' => $validated['project_type'] === 'in_kind' ? $validated['aid_item_id'] : null,
                    'total_quantity' => $validated['project_type'] === 'in_kind' ? $validated['total_quantity'] : 0,
                    'total_amount_ils' => $validated['project_type'] === 'cash' ? $validated['total_amount_ils'] : 0,
                    'estimated_amount' => $validated['estimated_amount'] ?? null,
                    'beneficiaries_total' => $validated['beneficiaries_total'],
                    'notes' => $validated['notes'] ?? null,
                ]);

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
        $this->authorize('delete', Project::class);

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
            default => [],
        };

        return response()->json($options);
    }

    private function validateForm(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'institution_id' => 'required|exists:institutions,id',
            'project_type' => 'required|in:cash,in_kind',
            'aid_item_id' => 'nullable|exists:aid_items,id|required_if:project_type,in_kind',
            'total_quantity' => 'nullable|numeric|min:0.01|required_if:project_type,in_kind',
            'total_amount_ils' => 'nullable|numeric|min:0.01|required_if:project_type,cash',
            'estimated_amount' => 'nullable|numeric|min:0',
            'beneficiaries_total' => 'required|integer|min:1',
            'notes' => 'nullable|string',
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
                default:
                    $query->whereIn($fieldName, $filteredValues);
                    break;
            }
        }
    }

    public function getProjectsByInstitution(int $institutionId)
    {
        $this->authorize('view', Project::class);

        $projects = ProjectStat::query()
            ->where('institution_id', $institutionId)
            ->orderBy('project_number')
            ->get()
            ->map(function ($project) {
                return [
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
                ];
            });

        return response()->json($projects);
    }

    public function getProjectStats(int $projectId)
    {
        $this->authorize('view', Project::class);

        $project = ProjectStat::query()->findOrFail($projectId);

        return response()->json([
            'id' => $project->id,
            'project_number' => $project->project_number,
            'name' => $project->name,
            'project_type' => $project->project_type,
            'aid_item_id' => $project->aid_item_id,
            'total_amount' => (float) $project->total_amount_ils,
            'consumed_amount' => (float) $project->consumed_amount,
            'remaining_amount' => (float) $project->remaining_amount,
            'total_quantity' => (float) $project->total_quantity,
            'consumed_quantity' => (float) $project->consumed_quantity,
            'remaining_quantity' => (float) $project->remaining_quantity,
            'beneficiaries_total' => (int) $project->beneficiaries_total,
            'beneficiaries_consumed' => (int) $project->beneficiaries_consumed,
            'remaining_beneficiaries' => (int) $project->remaining_beneficiaries,
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
}
