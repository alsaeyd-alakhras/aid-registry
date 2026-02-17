<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Imports\MerchantsImport;
use App\Models\Currency;
use App\Models\Merchant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class MerchantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $request = request();
        if($request->ajax()) {

            $merchants = Merchant::query();
            if ($request->column_filters) {
                foreach ($request->column_filters as $fieldName => $values) {
                    if (!empty($values)) {
                        // فلاتر checkboxes العادية
                        $filteredValues = array_filter($values, function($value) {
                            return !in_array($value, ['الكل', 'all', 'All']);
                        });

                        if (!empty($filteredValues)) {
                            $merchants->whereIn($fieldName, $filteredValues);
                        }
                    }
                }
            }
            return DataTables::of($merchants)
                    ->addIndexColumn()  // إضافة عمود الترقيم التلقائي
                    ->addColumn('edit', function ($merchant) {
                        return $merchant->id;
                    })
                    ->addColumn('remaining', function ($merchant) {
                        return $merchant->total_balance - $merchant->total_payments;
                    })
                    ->addColumn('delete', function ($merchant) {
                        return $merchant->id;
                    })
                    ->make(true);
        }
        return view('dashboard.merchants.index');
    }

    public function getFilterOptions(Request $request, $column)
    {
        $query = Merchant::query();

        // تطبيق الفلاتر النشطة من الأعمدة الأخرى
        if ($request->active_filters) {
            foreach ($request->active_filters as $fieldName => $values) {
                // فلاتر checkboxes العادية
                $filteredValues = array_filter($values, function($value) {
                    return !in_array($value, ['الكل', 'all', 'All']);
                });

                if (!empty($filteredValues)) {
                    $query->whereIn($fieldName, $filteredValues);
                }
            }
        }

        // جلب القيم الفريدة للعمود المطلوب
        $uniqueValues = $query->whereNotNull($column)
                            ->where($column, '!=', '')
                            ->distinct()
                            ->pluck($column)
                            ->filter()
                            ->values()
                            ->toArray();

        return response()->json($uniqueValues);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Merchant::class);
        $merchant = new Merchant();
        if($request->ajax()){
            return $merchant;
        }
        return view('dashboard.merchants.create', compact('merchant'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Merchant::class);
        $request->validate([
            'name_input' => 'required|string',
            'region' => 'nullable|string',
            'total_balance' => 'required|numeric',
        ]);
        $request->merge(['name' => $request->name_input]);

        Merchant::create($request->all());
        if($request->ajax()) {
            return response()->json(['message' => 'تم الإضافة بنجاح']);
        }
        return redirect()->route('dashboard.merchants.index')->with('success', 'تمت عملية الاضافة بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Merchant $merchant)
    {
        if($request->ajax()){
            return response()->json($merchant);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Merchant $merchant)
    {
        if($request->ajax()) {
            return response()->json($merchant);
        }
        $this->authorize('update', Merchant::class);
        $editForm = true;
        $btn_label = 'تعديل';
        return view('dashboard.merchants.edit', compact('merchant', 'editForm', 'btn_label'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Merchant $merchant)
    {
        $this->authorize('update', Merchant::class);
        $request->validate([
            'name_input' => 'required|string',
            'region' => 'nullable|string',
            'total_balance' => 'required|numeric',
        ]);
        $request->merge(['name' => $request->name_input]);
        $merchant->update($request->all());
        if($request->ajax()) {
            return response()->json(['message' => 'تم التحديث بنجاح']);
        }
        return redirect()->route('dashboard.merchants.index')->with('success', 'تمت عملية التعديل بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('delete', Merchant::class);

        $merchant = Merchant::findOrFail($id);

        $merchant->delete();
        if($request->ajax()) {
            return response()->json(['success' => 'تمت عملية الحذف بنجاح']);
        }
        return redirect()->route('dashboard.merchants.index')->with('danger', 'تمت عملية الحذف بنجاح');
    }
}
