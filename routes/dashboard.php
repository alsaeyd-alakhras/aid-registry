<?php


// dashboard routes

use App\Http\Controllers\Dashboard\ActivityLogController;
use App\Http\Controllers\Dashboard\AidDistributionController;
use App\Http\Controllers\Dashboard\AidItemController;
use App\Http\Controllers\Dashboard\ConstantController;
use App\Http\Controllers\Dashboard\CurrencyController;
use App\Http\Controllers\Dashboard\HomeController;
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
    Route::get('import', [HomeController::class,'import'])->name('import');
    Route::post('import-excel', [HomeController::class,'import_excel'])->name('import.excel');

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
    Route::get('aid-items-filters/{cloumn}', [AidItemController::class, 'getFilterOptions'])->name('aid-items.filters');
    Route::get('aid-distributions-filters/{cloumn}', [AidDistributionController::class, 'getFilterOptions'])->name('aid-distributions.filters');


    /* ********************************************************** */

    // Resources

    Route::resource('constants', ConstantController::class)->only(['index','store','destroy']);
    Route::resource('currencies', CurrencyController::class)->except(['show','edit','create']);


    Route::resources([
        'users' => UserController::class,
        'offices' => OfficeController::class,
        'aid-items' => AidItemController::class,
        'aid-distributions' => AidDistributionController::class,
    ]);

    // API Routes for AJAX requests
    Route::prefix('api')->group(function () {
        Route::get('families/search-by-national-id/{id}', [AidDistributionController::class, 'searchByNationalId'])->name('api.families.search');
        Route::get('aid-distributions/{id}', [AidDistributionController::class, 'showAidDistribution'])->name('api.aid-distributions.show');
        Route::get('families/{familyId}/all-aids', [AidDistributionController::class, 'getAllAids'])->name('api.families.all-aids');
    });
    /* ********************************************************** */
});
