<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('view', Currency::class);
        $currencies = Currency::all();
        return view('dashboard.pages.currencies', compact('currencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Currency::class);
        Currency::create($request->all());
        return redirect()->route('dashboard.currencies.index')->with('success','تم إضافة عملة جديدة');
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Currency $currency)
    {
        $this->authorize('update', Currency::class);
        $currency->update($request->all());
        ActivityLogService::log(
            'Updated',
            'Currency',
            "تم تعديل عملة : {$currency->name}.",
            $currency->getOriginal(),
            $currency->getChanges()
        );
        return redirect()->route('dashboard.currencies.index')->with('success','تم تحديث البيانات');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request,Currency $currency)
    {
        $this->authorize('delete', Currency::class);
        $currency->delete();
        return redirect()->route('dashboard.currencies.index')->with('success','تم حذف البيانات');
    }
}
