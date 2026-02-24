<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Imports\AidDistributionsImport;
use App\Models\AidDistribution;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class HomeController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        $year = Carbon::now()->year;
        $globalStats = $dashboardService->getGlobalStats();
        $monthlyStats = $dashboardService->getMonthlyStats();
        $officeStats = $dashboardService->getOfficeStats();
        $institutionStats = $dashboardService->getInstitutionStats();
        $topAidItems = $dashboardService->getTopAidItems();
        $recentDistributions = $dashboardService->getRecentDistributions();

        return view('dashboard.index', compact(
            'year',
            'globalStats',
            'monthlyStats',
            'officeStats',
            'institutionStats',
            'topAidItems',
            'recentDistributions'
        ));
    }

    public function import()
    {
        return view('dashboard.import');
    }

    public function import_excel(Request $request){
        $this->authorize('import', AidDistribution::class);
        if(!$request->hasFile('file')){
            return redirect()->route('dashboard.import')->with('danger', 'الرجاء تحميل الملف');
        }
        $file = $request->file('file');
        $import = new AidDistributionsImport();
        Excel::import($import, $file);

        $problemRows = $import->getProblemRows();
        if (!empty($problemRows)) {
            return redirect()
                ->route('dashboard.import')
                ->with('warning', 'تم الاستيراد مع تجاهل بعض السجلات التي ينقصها المكتب أو المؤسسة.')
                ->with('import_problem_rows', $problemRows);
        }

        return redirect()->route('dashboard.import')->with('success', 'تمت عملية الاستيراد بنجاح');
    }

}

