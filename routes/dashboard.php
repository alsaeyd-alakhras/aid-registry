<?php


// dashboard routes

use App\Http\Controllers\Dashboard\ActivityLogController;
use App\Http\Controllers\Dashboard\AidDistributionController;
use App\Http\Controllers\Dashboard\AidItemController;
use App\Http\Controllers\Dashboard\ConstantController;
use App\Http\Controllers\Dashboard\CurrencyController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\InstitutionController;
use App\Http\Controllers\Dashboard\ProjectController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\Dashboard\OfficeController;
use App\Http\Controllers\Dashboard\ReportController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => '',
    'middleware' => ['check.cookie'],
    'as' => 'dashboard.'
], function () {
    /* ********************************************************** */

    // Dashboard ************************
    Route::get('/', [HomeController::class,'index'])->name('home');
    Route::post('dashboard/refresh-cache', [HomeController::class, 'refreshDashboardCache'])->name('home.refresh-cache');
    Route::get('import', [HomeController::class,'import'])->name('import');
    Route::post('import-excel', [HomeController::class,'import_excel'])->name('import.excel');
    Route::post('import-excel/finalize/{uuid}', [HomeController::class,'import_finalize'])->name('import.finalize');
    Route::post('import-excel/update-decision/{uuid}', [HomeController::class,'import_update_decision'])->name('import.update-decision');

    // Logs ************************
    Route::get('logs',[ActivityLogController::class,'index'])->name('logs.index');
    Route::get('getLogs',[ActivityLogController::class,'getLogs'])->name('logs.getLogs');

    // users ************************
    Route::get('profile/settings',[UserController::class,'settings'])->name('profile.settings');

    // Reports ************************
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('reports/tradersReve', [ReportController::class, 'tradersReve'])->name('reports.tradersReve');
    Route::get('reports/brokersReve', [ReportController::class, 'brokersReve'])->name('reports.brokersReve');
    Route::get('reports/broker-details', [ReportController::class, 'brokerDetails'])->name('reports.brokerDetails');
    /* ********************************************************** */

    // Merchants ************************    
    Route::get('offices-filters/{cloumn}', [OfficeController::class, 'getFilterOptions'])->name('offices.filters');
    Route::get('institutions-filters/{cloumn}', [InstitutionController::class, 'getFilterOptions'])->name('institutions.filters');
    Route::get('aid-items-filters/{cloumn}', [AidItemController::class, 'getFilterOptions'])->name('aid-items.filters');
    Route::get('aid-distributions-filters/{cloumn}', [AidDistributionController::class, 'getFilterOptions'])->name('aid-distributions.filters');
    Route::get('projects-filters/{cloumn}', [ProjectController::class, 'getFilterOptions'])->name('projects.filters');


    /* ********************************************************** */

    // Resources

    Route::resource('constants', ConstantController::class)->only(['index','store','destroy']);
    Route::resource('currencies', CurrencyController::class)->except(['show','edit','create']);


    Route::resources([
        'users' => UserController::class,
        'offices' => OfficeController::class,
        'institutions' => InstitutionController::class,
        'projects' => ProjectController::class,
        'aid-items' => AidItemController::class,
        'aid-distributions' => AidDistributionController::class,
    ]);

    // API Routes for AJAX requests
    Route::prefix('api')->group(function () {
        Route::get('families/search-by-national-id/{id}', [AidDistributionController::class, 'searchByNationalId'])->name('api.families.search');
        Route::get('aid-distributions/{id}', [AidDistributionController::class, 'showAidDistribution'])->name('api.aid-distributions.show');
        Route::get('families/{familyId}/all-aids', [AidDistributionController::class, 'getAllAids'])->name('api.families.all-aids');
        Route::get('institutions/{institutionId}/projects', [ProjectController::class, 'getProjectsByInstitution'])->name('api.institutions.projects');
        Route::get('projects/{projectId}/stats', [ProjectController::class, 'getProjectStats'])->name('api.projects.stats');
        Route::get('projects/{projectId}/breakdown', [ProjectController::class, 'getProjectBreakdown'])->name('api.projects.breakdown');
    });
    /* ********************************************************** */
});
