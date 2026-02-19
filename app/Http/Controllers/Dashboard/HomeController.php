<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        $year = Carbon::now()->year;
        $globalStats = $dashboardService->getGlobalStats();
        $monthlyStats = $dashboardService->getMonthlyStats();
        $officeStats = $dashboardService->getOfficeStats();
        $topAidItems = $dashboardService->getTopAidItems();
        $recentDistributions = $dashboardService->getRecentDistributions();

        return view('dashboard.index', compact(
            'year',
            'globalStats',
            'monthlyStats',
            'officeStats',
            'topAidItems',
            'recentDistributions'
        ));
    }

    public function import()
    {
        return view('dashboard.import');
    }

}

