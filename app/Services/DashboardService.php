<?php

namespace App\Services;

use App\Models\AidDistribution;
use App\Models\AidItem;
use App\Models\Family;
use App\Models\Institution;
use App\Models\Office;
use App\Models\ProjectOfficeAllocation;
use App\Models\ProjectStat;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const CACHE_TTL_SECONDS = 300;
    private const TABLE_PER_PAGE = 10;
    private const CACHE_VERSION_KEY = 'dashboard:cache:version';

    public function getGlobalStats(): array
    {
        $scope = $this->getOfficeScopeCacheKey();

        return Cache::remember($this->cacheKey("global-stats:{$scope}"), self::CACHE_TTL_SECONDS, function () {
            $startCurrentMonth = now()->startOfMonth();
            $endCurrentMonth = now()->endOfMonth();
            $startPreviousMonth = now()->subMonthNoOverflow()->startOfMonth();
            $endPreviousMonth = now()->subMonthNoOverflow()->endOfMonth();

            $currentMonthDistributions = AidDistribution::query()
                ->officeEmployee()
                ->whereBetween('distributed_at', [$startCurrentMonth, $endCurrentMonth])
                ->count();
            $previousMonthDistributions = AidDistribution::query()
                ->officeEmployee()
                ->whereBetween('distributed_at', [$startPreviousMonth, $endPreviousMonth])
                ->count();

            $currentMonthCash = (float) AidDistribution::query()
                ->officeEmployee()
                ->where('aid_mode', 'cash')
                ->whereBetween('distributed_at', [$startCurrentMonth, $endCurrentMonth])
                ->sum('cash_amount');
            $previousMonthCash = (float) AidDistribution::query()
                ->officeEmployee()
                ->where('aid_mode', 'cash')
                ->whereBetween('distributed_at', [$startPreviousMonth, $endPreviousMonth])
                ->sum('cash_amount');

            $newFamiliesCurrentMonth = Family::query()
                ->whereBetween('created_at', [$startCurrentMonth, $endCurrentMonth])
                ->count();
            $newFamiliesPreviousMonth = Family::query()
                ->whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])
                ->count();

            $activeOffices = $this->applyOfficeScopeToOfficesQuery(Office::query())
                ->where('is_active', true)
                ->count();
            $inactiveOffices = $this->applyOfficeScopeToOfficesQuery(Office::query())
                ->where('is_active', false)
                ->count();
            $activeInstitutions = Institution::query()->where('is_active', true)->count();
            $inactiveInstitutions = Institution::query()->where('is_active', false)->count();

            return [
                'total_families' => Family::query()->count(),
                'total_distributions' => AidDistribution::query()->officeEmployee()->count(),
                'total_cash_all_time' => (float) AidDistribution::query()
                    ->officeEmployee()
                    ->where('aid_mode', 'cash')
                    ->sum('cash_amount'),
                'current_month_distributions' => $currentMonthDistributions,
                'current_month_cash' => $currentMonthCash,
                'active_offices' => $activeOffices,
                'total_institutions' => Institution::query()->count(),
                'comparison' => [
                    'total_families' => $this->buildComparison($newFamiliesCurrentMonth, $newFamiliesPreviousMonth, 'عن الأسر الجديدة بالشهر الماضي'),
                    'total_distributions' => $this->buildComparison($currentMonthDistributions, $previousMonthDistributions, 'عن الشهر الماضي'),
                    'total_cash_all_time' => $this->buildComparison($currentMonthCash, $previousMonthCash, 'عن الشهر الماضي'),
                    'current_month_distributions' => $this->buildComparison($currentMonthDistributions, $previousMonthDistributions, 'عن الشهر الماضي'),
                    'current_month_cash' => $this->buildComparison($currentMonthCash, $previousMonthCash, 'عن الشهر الماضي'),
                    'active_offices' => $inactiveOffices > 0
                        ? "{$inactiveOffices} مكتب غير مفعل"
                        : 'كل المكاتب مفعلة',
                    'total_institutions' => $inactiveInstitutions > 0
                        ? "{$activeInstitutions} مؤسسة مفعلة / {$inactiveInstitutions} غير مفعلة"
                        : 'كل المؤسسات مفعلة',
                ],
            ];
        });
    }

    public function getMonthlyStats(): array
    {
        $year = now()->year;
        $scope = $this->getOfficeScopeCacheKey();

        return Cache::remember($this->cacheKey("monthly-stats:{$scope}:{$year}"), self::CACHE_TTL_SECONDS, function () use ($year) {
            $raw = AidDistribution::query()
                ->officeEmployee()
                ->selectRaw('MONTH(distributed_at) as month_num')
                ->selectRaw('COUNT(*) as distributions_count')
                ->selectRaw("SUM(CASE WHEN aid_mode = 'cash' THEN cash_amount ELSE 0 END) as cash_total")
                ->whereYear('distributed_at', $year)
                ->groupBy(DB::raw('MONTH(distributed_at)'))
                ->orderBy(DB::raw('MONTH(distributed_at)'))
                ->get()
                ->keyBy('month_num');

            $labels = [];
            $distributionSeries = [];
            $cashSeries = [];

            foreach (range(1, 12) as $monthNum) {
                $date = Carbon::create($year, $monthNum, 1);
                $labels[] = $date->translatedFormat('M');
                $distributionSeries[] = (int) ($raw[$monthNum]->distributions_count ?? 0);
                $cashSeries[] = (float) ($raw[$monthNum]->cash_total ?? 0);
            }

            return [
                'labels' => $labels,
                'distribution_series' => $distributionSeries,
                'cash_series' => $cashSeries,
            ];
        });
    }

    public function getOfficeStats(): LengthAwarePaginator
    {
        $page = request()->integer('office_page', 1);
        $scope = $this->getOfficeScopeCacheKey();

        $rows = Cache::remember($this->cacheKey("office-stats:{$scope}"), self::CACHE_TTL_SECONDS, function () {
            $query = Office::query();
            $this->applyOfficeScopeToOfficesQuery($query);

            return $query
                ->leftJoin('aid_distributions', 'aid_distributions.office_id', '=', 'offices.id')
                ->select('offices.id', 'offices.name')
                ->selectRaw('COUNT(aid_distributions.id) as total_distributions')
                ->selectRaw("SUM(CASE WHEN aid_distributions.aid_mode = 'cash' THEN aid_distributions.cash_amount ELSE 0 END) as cash_total")
                ->selectRaw("SUM(CASE WHEN aid_distributions.aid_mode = 'in_kind' THEN 1 ELSE 0 END) as in_kind_count")
                ->selectRaw('MAX(aid_distributions.distributed_at) as last_distribution_date')
                ->groupBy('offices.id', 'offices.name')
                ->orderByDesc('total_distributions')
                ->get();
        });

        return $this->paginateCollection($rows, self::TABLE_PER_PAGE, $page, 'office_page');
    }

    public function getTopAidItems(): LengthAwarePaginator
    {
        $page = request()->integer('aid_item_page', 1);
        $employeeOfficeId = $this->getEmployeeOfficeId();
        $scope = $this->getOfficeScopeCacheKey();

        $rows = Cache::remember($this->cacheKey("top-aid-items:{$scope}"), self::CACHE_TTL_SECONDS, function () use ($employeeOfficeId) {
            return AidItem::query()
                ->join('aid_distributions', 'aid_distributions.aid_item_id', '=', 'aid_items.id')
                ->where('aid_distributions.aid_mode', 'in_kind')
                ->when($employeeOfficeId, function ($query) use ($employeeOfficeId) {
                    $query->where('aid_distributions.office_id', $employeeOfficeId);
                })
                ->select('aid_items.id', 'aid_items.name')
                ->selectRaw('COUNT(aid_distributions.id) as total_distributed')
                ->selectRaw('MAX(aid_distributions.distributed_at) as last_distribution_date')
                ->groupBy('aid_items.id', 'aid_items.name')
                ->orderByDesc('total_distributed')
                ->get();
        });

        return $this->paginateCollection($rows, self::TABLE_PER_PAGE, $page, 'aid_item_page');
    }

    public function getRecentDistributions(): LengthAwarePaginator
    {
        $page = request()->integer('recent_page', 1);
        $scope = $this->getOfficeScopeCacheKey();

        $key = $this->cacheKey("recent-distributions:{$scope}:page:{$page}");
        $cacheResult = Cache::remember($key, self::CACHE_TTL_SECONDS, function () use ($page) {
            $query = AidDistribution::query()
                ->officeEmployee()
                ->with(['family:id,full_name', 'office:id,name', 'aidItem:id,name', 'creator:id,name'])
                ->orderByDesc('distributed_at')
                ->orderByDesc('id');

            return [
                // 'items' => $query->forPage($page, self::TABLE_PER_PAGE)->get(),
                'items' => $query->limit(20)->get(),
                'total' => 20,
            ];
        });

        return new LengthAwarePaginator(
            $cacheResult['items'],
            $cacheResult['total'],
            self::TABLE_PER_PAGE,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => 'recent_page',
            ]
        );
    }

    public function getInstitutionStats(): LengthAwarePaginator
    {
        $page = request()->integer('institution_page', 1);
        $employeeOfficeId = $this->getEmployeeOfficeId();
        $scope = $this->getOfficeScopeCacheKey();

        $rows = Cache::remember($this->cacheKey("institution-stats:{$scope}"), self::CACHE_TTL_SECONDS, function () use ($employeeOfficeId) {
            return Institution::query()
                ->leftJoin('aid_distributions', function ($join) use ($employeeOfficeId) {
                    $join->on('aid_distributions.institution_id', '=', 'institutions.id');
                    if ($employeeOfficeId) {
                        $join->where('aid_distributions.office_id', '=', $employeeOfficeId);
                    }
                })
                ->select('institutions.id', 'institutions.name')
                ->selectRaw('COUNT(aid_distributions.id) as total_distributions')
                ->selectRaw("SUM(CASE WHEN aid_distributions.aid_mode = 'cash' THEN aid_distributions.cash_amount ELSE 0 END) as total_spent_cash")
                ->selectRaw("SUM(CASE WHEN aid_distributions.aid_mode = 'in_kind' AND aid_distributions.quantity IS NOT NULL THEN aid_distributions.quantity ELSE 0 END) as total_spent_quantity")
                ->groupBy('institutions.id', 'institutions.name')
                ->orderByDesc('total_spent_cash')
                ->get();
        });

        return $this->paginateCollection($rows, self::TABLE_PER_PAGE, $page, 'institution_page');
    }

    private function paginateCollection(Collection $rows, int $perPage, int $page, string $pageName): LengthAwarePaginator
    {
        $items = $rows->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => $pageName,
            ]
        );
    }

    private function buildComparison(float|int $current, float|int $previous, string $suffix): string
    {
        if ((float) $previous === 0.0) {
            if ((float) $current === 0.0) {
                return "0% {$suffix}";
            }

            return "+100% {$suffix}";
        }

        $change = (($current - $previous) / $previous) * 100;
        $sign = $change > 0 ? '+' : '';

        return "{$sign}" . number_format($change, 1) . "% {$suffix}";
    }

    private function getEmployeeOfficeId(): ?int
    {
        $user = Auth::user();
        if ($user && $user->user_type === 'employee') {
            return $user->office_id ? (int) $user->office_id : null;
        }

        return null;
    }

    public function getProjectStats(): LengthAwarePaginator
    {
        $page = request()->integer('project_page', 1);
        $scope = $this->getOfficeScopeCacheKey();

        $rows = Cache::remember($this->cacheKey("project-stats:{$scope}"), self::CACHE_TTL_SECONDS, function () {
            $employeeOfficeId = $this->getEmployeeOfficeId();

            if ($employeeOfficeId) {
                return $this->getProjectStatsForEmployee($employeeOfficeId);
            }

            $allocationsByProject = ProjectOfficeAllocation::query()
                ->selectRaw('project_id, SUM(COALESCE(max_amount, 0)) as allocated_amount, SUM(COALESCE(max_quantity, 0)) as allocated_quantity')
                ->groupBy('project_id')
                ->get()
                ->keyBy('project_id');

            $receiptsByProject = ProjectOfficeAllocation::query()
                ->whereNotNull('receipt_file_path')
                ->selectRaw('project_id, COUNT(*) as receipts_count')
                ->groupBy('project_id')
                ->get()
                ->keyBy('project_id');

            $totalOfficesByProject = ProjectOfficeAllocation::query()
                ->selectRaw('project_id, COUNT(*) as total_offices')
                ->groupBy('project_id')
                ->get()
                ->keyBy('project_id');

            $repeatersByProject = $this->getRepeatersCountByProject(null);

            return ProjectStat::query()
                ->with(['institution', 'aidItem'])
                ->orderBy('project_number')
                ->get()
                ->map(function (ProjectStat $project) use ($allocationsByProject, $receiptsByProject, $totalOfficesByProject, $repeatersByProject) {
                    return $this->mapProjectStatToRow($project, $allocationsByProject, $receiptsByProject, $totalOfficesByProject, $repeatersByProject);
                });
        });

        return $this->paginateCollection($rows, self::TABLE_PER_PAGE, $page, 'project_page');
    }

    private function getProjectStatsForEmployee(int $officeId): Collection
    {
        $allocations = ProjectOfficeAllocation::query()
            ->where('office_id', $officeId)
            ->with(['project.institution', 'project.aidItem'])
            ->join('projects', 'projects.id', '=', 'project_office_allocations.project_id')
            ->orderBy('projects.project_number')
            ->select('project_office_allocations.*')
            ->get();

        $consumed = AidDistribution::query()
            ->where('office_id', $officeId)
            ->where('status', 'active')
            ->whereNotNull('project_id')
            ->selectRaw('project_id')
            ->selectRaw("SUM(CASE WHEN aid_mode = 'cash' THEN COALESCE(cash_amount, 0) ELSE 0 END) as consumed_amount")
            ->selectRaw("SUM(CASE WHEN aid_mode = 'in_kind' THEN COALESCE(quantity, 0) ELSE 0 END) as consumed_quantity")
            ->selectRaw('COUNT(DISTINCT family_id) as beneficiaries_consumed')
            ->selectRaw('COUNT(*) as aid_distributions_count')
            ->groupBy('project_id')
            ->get()
            ->keyBy('project_id');

        $repeatersByProject = $this->getRepeatersCountByProject($officeId);

        return $allocations->map(function ($allocation) use ($consumed, $repeatersByProject) {
            $project = $allocation->project;
            if (!$project) {
                return null;
            }

            $c = $consumed->get($project->id);
            $consumedAmount = (float) ($c->consumed_amount ?? 0);
            $consumedQuantity = (float) ($c->consumed_quantity ?? 0);
            $beneficiariesConsumed = (int) ($c->beneficiaries_consumed ?? 0);
            $aidDistributionsCount = (int) ($c->aid_distributions_count ?? 0);

            $totalAmount = (float) ($allocation->max_amount ?? 0);
            $totalQuantity = (float) ($allocation->max_quantity ?? 0);
            $maxBeneficiaries = (int) ($allocation->max_beneficiaries ?? 0);

            return [
                'id' => $project->id,
                'project_number' => $project->project_number,
                'name' => $project->name,
                'notes' => $project->notes ?? null,
                'institution_name' => $project->institution?->name ?? '-',
                'project_type' => $project->project_type,
                'aid_item_name' => $project->project_type === 'in_kind' ? ($project->aidItem?->name ?? '-') : '-',
                'aid_distributions_count' => $aidDistributionsCount,
                'total_amount' => $totalAmount,
                'consumed_amount' => $consumedAmount,
                'remaining_amount' => $totalAmount - $consumedAmount,
                'total_quantity' => $totalQuantity,
                'consumed_quantity' => $consumedQuantity,
                'remaining_quantity' => $totalQuantity - $consumedQuantity,
                'beneficiaries_total' => $maxBeneficiaries,
                'beneficiaries_consumed' => $beneficiariesConsumed,
                'remaining_beneficiaries' => $maxBeneficiaries - $beneficiariesConsumed,
                'allocation_id' => $allocation->id,
                'has_receipt' => !empty($allocation->receipt_file_path),
                'repeaters_count' => (int) ($repeatersByProject->get($project->id)?->repeaters_count ?? 0),
            ];
        })->filter()->values();
    }

    private function getRepeatersCountByProject(?int $officeId): Collection
    {
        $sub = AidDistribution::query()
            ->where('status', 'active')
            ->whereNotNull('project_id')
            ->when($officeId, fn ($q) => $q->where('office_id', $officeId))
            ->selectRaw('project_id, family_id, COUNT(*) as cnt')
            ->groupBy('project_id', 'family_id')
            ->havingRaw('COUNT(*) > 1');

        $baseQuery = DB::table(DB::raw('(' . $sub->toSql() . ') as sub'))
            ->mergeBindings($sub->getQuery())
            ->selectRaw('project_id, COUNT(*) as repeaters_count')
            ->groupBy('project_id');

        return $baseQuery->get()->keyBy('project_id');
    }

    private function mapProjectStatToRow(ProjectStat $project, ?Collection $allocationsByProject = null, ?Collection $receiptsByProject = null, ?Collection $totalOfficesByProject = null, ?Collection $repeatersByProject = null): array
    {
        $row = [
            'id' => $project->id,
            'project_number' => $project->project_number,
            'name' => $project->name,
            'notes' => $project->notes ?? null,
            'institution_name' => $project->institution?->name ?? '-',
            'project_type' => $project->project_type,
            'aid_item_name' => $project->project_type === 'in_kind' ? ($project->aidItem?->name ?? '-') : '-',
            'aid_distributions_count' => (int) $project->aid_distributions_count,
            'total_amount' => (float) $project->total_amount_ils,
            'consumed_amount' => (float) $project->consumed_amount,
            'remaining_amount' => (float) $project->remaining_amount,
            'total_quantity' => (float) $project->total_quantity,
            'consumed_quantity' => (float) $project->consumed_quantity,
            'remaining_quantity' => (float) $project->remaining_quantity,
            'beneficiaries_total' => (int) $project->beneficiaries_total,
            'beneficiaries_consumed' => (int) $project->beneficiaries_consumed,
            'remaining_beneficiaries' => (int) $project->remaining_beneficiaries,
            'repeaters_count' => (int) ($repeatersByProject?->get($project->id)?->repeaters_count ?? 0),
        ];

        if ($receiptsByProject !== null && $totalOfficesByProject !== null) {
            $receiptsCount = (int) ($receiptsByProject->get($project->id)?->receipts_count ?? 0);
            $totalOffices = (int) ($totalOfficesByProject->get($project->id)?->total_offices ?? 0);
            $row['receipts_uploaded_count'] = $receiptsCount;
            $row['total_offices_count'] = $totalOffices;
            $row['receipts_display'] = $totalOffices > 0 ? "{$receiptsCount}/{$totalOffices}" : '-';
        }

        if ($allocationsByProject !== null) {
            $alloc = $allocationsByProject->get($project->id);
            if ($alloc) {
                $allocatedAmount = (float) $alloc->allocated_amount;
                $allocatedQuantity = (float) $alloc->allocated_quantity;
                $row['storage_balance_amount'] = max(0, (float) $project->total_amount_ils - $allocatedAmount);
                $row['storage_balance_quantity'] = max(0, (float) $project->total_quantity - $allocatedQuantity);
                $row['offices_balance_amount'] = $allocatedAmount;
                $row['offices_balance_quantity'] = $allocatedQuantity;
            } else {
                $row['storage_balance_amount'] = (float) $project->remaining_amount;
                $row['storage_balance_quantity'] = (float) $project->remaining_quantity;
                $row['offices_balance_amount'] = (float) $project->consumed_amount;
                $row['offices_balance_quantity'] = (float) $project->consumed_quantity;
            }
        }

        return $row;
    }

    public function clearDashboardCache(): void
    {
        if (!Cache::has(self::CACHE_VERSION_KEY)) {
            Cache::forever(self::CACHE_VERSION_KEY, 1);
        }

        Cache::increment(self::CACHE_VERSION_KEY);
    }

    private function cacheKey(string $suffix): string
    {
        return 'dashboard:v' . $this->getCacheVersion() . ':' . $suffix;
    }

    private function getCacheVersion(): int
    {
        return (int) Cache::rememberForever(self::CACHE_VERSION_KEY, function () {
            return 1;
        });
    }

    private function getOfficeScopeCacheKey(): string
    {
        $employeeOfficeId = $this->getEmployeeOfficeId();

        return $employeeOfficeId ? "office:{$employeeOfficeId}" : 'all-offices';
    }

    private function applyOfficeScopeToOfficesQuery(Builder $query): Builder
    {
        $employeeOfficeId = $this->getEmployeeOfficeId();
        if ($employeeOfficeId) {
            $query->where('offices.id', $employeeOfficeId);
        }

        return $query;
    }
}
