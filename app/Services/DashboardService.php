<?php

namespace App\Services;

use App\Models\AidDistribution;
use App\Models\AidItem;
use App\Models\Family;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const CACHE_TTL_SECONDS = 300;
    private const TABLE_PER_PAGE = 10;

    public function getGlobalStats(): array
    {
        return Cache::remember('dashboard:global-stats', self::CACHE_TTL_SECONDS, function () {
            $startCurrentMonth = now()->startOfMonth();
            $endCurrentMonth = now()->endOfMonth();
            $startPreviousMonth = now()->subMonthNoOverflow()->startOfMonth();
            $endPreviousMonth = now()->subMonthNoOverflow()->endOfMonth();

            $currentMonthDistributions = AidDistribution::query()
                ->whereBetween('distributed_at', [$startCurrentMonth, $endCurrentMonth])
                ->count();
            $previousMonthDistributions = AidDistribution::query()
                ->whereBetween('distributed_at', [$startPreviousMonth, $endPreviousMonth])
                ->count();

            $currentMonthCash = (float) AidDistribution::query()
                ->where('aid_mode', 'cash')
                ->whereBetween('distributed_at', [$startCurrentMonth, $endCurrentMonth])
                ->sum('cash_amount');
            $previousMonthCash = (float) AidDistribution::query()
                ->where('aid_mode', 'cash')
                ->whereBetween('distributed_at', [$startPreviousMonth, $endPreviousMonth])
                ->sum('cash_amount');

            $newFamiliesCurrentMonth = Family::query()
                ->whereBetween('created_at', [$startCurrentMonth, $endCurrentMonth])
                ->count();
            $newFamiliesPreviousMonth = Family::query()
                ->whereBetween('created_at', [$startPreviousMonth, $endPreviousMonth])
                ->count();

            $activeOffices = Office::query()->where('is_active', true)->count();
            $inactiveOffices = Office::query()->where('is_active', false)->count();

            return [
                'total_families' => Family::query()->count(),
                'total_distributions' => AidDistribution::query()->count(),
                'total_cash_all_time' => (float) AidDistribution::query()
                    ->where('aid_mode', 'cash')
                    ->sum('cash_amount'),
                'current_month_distributions' => $currentMonthDistributions,
                'current_month_cash' => $currentMonthCash,
                'active_offices' => $activeOffices,
                'comparison' => [
                    'total_families' => $this->buildComparison($newFamiliesCurrentMonth, $newFamiliesPreviousMonth, 'عن الأسر الجديدة بالشهر الماضي'),
                    'total_distributions' => $this->buildComparison($currentMonthDistributions, $previousMonthDistributions, 'عن الشهر الماضي'),
                    'total_cash_all_time' => $this->buildComparison($currentMonthCash, $previousMonthCash, 'عن الشهر الماضي'),
                    'current_month_distributions' => $this->buildComparison($currentMonthDistributions, $previousMonthDistributions, 'عن الشهر الماضي'),
                    'current_month_cash' => $this->buildComparison($currentMonthCash, $previousMonthCash, 'عن الشهر الماضي'),
                    'active_offices' => $inactiveOffices > 0
                        ? "{$inactiveOffices} مكتب غير مفعل"
                        : 'كل المكاتب مفعلة',
                ],
            ];
        });
    }

    public function getMonthlyStats(): array
    {
        $year = now()->year;

        return Cache::remember("dashboard:monthly-stats:{$year}", self::CACHE_TTL_SECONDS, function () use ($year) {
            $raw = AidDistribution::query()
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

        $rows = Cache::remember('dashboard:office-stats', self::CACHE_TTL_SECONDS, function () {
            return Office::query()
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

        $rows = Cache::remember('dashboard:top-aid-items', self::CACHE_TTL_SECONDS, function () {
            return AidItem::query()
                ->join('aid_distributions', 'aid_distributions.aid_item_id', '=', 'aid_items.id')
                ->where('aid_distributions.aid_mode', 'in_kind')
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

        $key = "dashboard:recent-distributions:page:{$page}";
        $cacheResult = Cache::remember($key, self::CACHE_TTL_SECONDS, function () use ($page) {
            $query = AidDistribution::query()
                ->with(['family:id,full_name', 'office:id,name', 'aidItem:id,name', 'creator:id,name'])
                ->orderByDesc('distributed_at')
                ->orderByDesc('id');

            return [
                'items' => $query->forPage($page, self::TABLE_PER_PAGE)->get(),
                'total' => (int) $query->toBase()->getCountForPagination(),
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
}
