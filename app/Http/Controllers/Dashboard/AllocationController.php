<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Imports\AllocationsImport;
use App\Models\AccreditationProject;
use App\Models\Allocation;
use App\Models\Broker;
use App\Models\Currency;
use App\Models\Executive;
use App\Models\Item;
use App\Models\Logs;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;


class AllocationController extends Controller
{
    // /**
    //  * Display a listing of the resource.
    //  */
    // public function index(Request $request)
    // {
    //     $this->authorize('view', Allocation::class);

    //     if($request->ajax()) {
    //         // جلب بيانات المستخدمين من الجدول
    //         $year = $request->year ?? Carbon::now()->year;
    //         $allocations = Allocation::query()->orderBy('date_allocation', 'asc')->orderBy('budget_number', 'asc');


    //         // التصفية بناءً على التواريخ
    //         if ($request->from_date != null && $request->to_date != null) {
    //             $allocations = Allocation::query()->orderBy('date_allocation', 'asc')->orderBy('budget_number', 'asc');
    //             $allocations->whereBetween('date_allocation', [$request->from_date, $request->to_date]);
    //         }
    //         if ($request->from_date_implementation != null && $request->to_date_implementation != null) {
    //             $allocations = Allocation::query()->orderBy('date_allocation', 'asc')->orderBy('budget_number', 'asc');
    //             $allocations->whereBetween('date_implementation', [$request->from_date_implementation, $request->to_date_implementation]);
    //         }

    //         if($request->from_date == null && $request->from_date_implementation == null){
    //             $allocations = Allocation::query()->orderBy('date_allocation', 'asc')->orderBy('budget_number', 'asc');
    //             // التصفية بناءً على السنة
    //             $allocations->whereYear('date_allocation', $request->year);
    //         }

    //         return DataTables::of($allocations->take(20))
    //                 ->addIndexColumn()  // إضافة عمود الترقيم التلقائي
    //                 ->addColumn('edit', function ($allocation) {
    //                     return $allocation->id;
    //                 })
    //                 ->addColumn('currency_allocation_name', function ($allocation) {
    //                     $currency = Currency::where('code', $allocation->currency_allocation)->first();
    //                     return $currency ? "$currency->name" : '';
    //                 })
    //                 ->addColumn('select', function ($allocation) {
    //                     return $allocation->id;
    //                 })
    //                 ->addColumn('print', function ($allocation) {
    //                     return $allocation->id;
    //                 })
    //                 ->addColumn('delete', function ($allocation) {
    //                     return $allocation->id;
    //                 })
    //                 ->make(true);
    //     }

    //     $budgets = Allocation::select('budget_number')->distinct()->pluck('budget_number')->toArray();
    //     $brokers = Broker::select('name')->distinct()->pluck('name')->toArray();
    //     $organizations = Allocation::select('organization_name')->distinct()->pluck('organization_name')->toArray();
    //     $projs = Allocation::select('project_name')->distinct()->pluck('project_name')->toArray();
    //     $items =  Item::select('name')->distinct()->pluck('name')->toArray();
    //     $currencies = Currency::get();
    //     $USD = Currency::where('code', 'USD')->first() ? Currency::where('code', 'USD')->first()->value : 1;
    //     $ILS = Currency::where('code', 'ILS')->first() ? Currency::where('code', 'ILS')->first()->value : 3.5;

    //     return view('dashboard.allocations.index', compact('budgets','brokers', 'organizations', 'projs', 'items', 'currencies', 'USD', 'ILS'));
    // }
    public function index()
    {
        $request = request();
        if($request->ajax()) {
            $year = $request->year ?? Carbon::now()->year;

            $allocations = Allocation::query()->orderBy('date_allocation', 'asc')->orderBy('budget_number', 'asc');

            $allocations->whereYear('date_allocation', $year);


            if ($request->from_date) {
                $allocations->whereDate('date_allocation', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $allocations->whereDate('date_allocation', '<=', $request->to_date);
            }


            if ($request->column_filters) {
                foreach ($request->column_filters as $fieldName => $values) {
                    if (!empty($values)) {
                        if ($fieldName === 'date_allocation' && is_array($values)) {
                            // معالجة فلتر التاريخ من column_filters
                            if (isset($values['from'])) {
                                $allocations->whereDate('date_allocation', '>=', $values['from']);
                            }
                            if (isset($values['to'])) {
                                $allocations->whereDate('date_allocation', '<=', $values['to']);
                            }
                        } else {
                            // فلاتر checkboxes العادية
                            $filteredValues = array_filter($values, function($value) {
                                return !in_array($value, ['الكل', 'all', 'All']);
                            });

                            if (!empty($filteredValues)) {
                                $allocations->whereIn($fieldName, $filteredValues);
                            }
                        }
                    }
                }
            }
            // حساب المجاميع للبيانات المفلترة
            $totals = $this->calculateTotals($allocations);
            return DataTables::of($allocations)
                    ->addIndexColumn()  // إضافة عمود الترقيم التلقائي
                    ->addColumn('edit', function ($allocation) {
                        return $allocation->id;
                    })
                    ->addColumn('currency_allocation_name', function ($allocation) {
                        $currency = Currency::where('code', $allocation->currency_allocation)->first();
                        return $currency ? $currency->name : '';
                    })
                    ->addColumn('delete', function ($allocation) {
                        return $allocation->id;
                    })
                    ->addColumn('amount_administrative_fees_shekel', function ($allocation) {
                        $percentage_female_administrators = $allocation->percentage_female_administrators;
                        $amount_administrative_fees_shekel = $allocation->amount * ($allocation->exchange_rate ?? 3.6) * $percentage_female_administrators;
                        return $amount_administrative_fees_shekel;
                    })
                    ->addColumn('budget_amount_shekels', function ($allocation) {
                        $currency = Currency::where('code', $allocation->currency_allocation)->first();
                        $percentage_female_administrators = $allocation->percentage_female_administrators;
                        $amount = 0;
                        if($currency->code == 'ILS'){
                            $amount = $allocation->allocation * 1;
                        }else{
                            $amount = $allocation->amount * ($allocation->exchange_rate ?? 3.6);
                        }
                        $amount_administrative_fees_shekel = $allocation->amount * ($allocation->exchange_rate ?? 3.6) * $percentage_female_administrators;
                        $budget_amount_shekels = $amount - $amount_administrative_fees_shekel;

                        return $budget_amount_shekels;
                    })
                    ->with('totals', $totals)
                    ->make(true);
        }

        // get data
        $brokers = Broker::select('name')->distinct()->pluck('name')->toArray();
        $organizations = Allocation::select('organization_name')->distinct()->pluck('organization_name')->toArray();
        $projs = Allocation::select('project_name')->distinct()->pluck('project_name')->toArray();
        $items =  Item::select('name')->distinct()->pluck('name')->toArray();
        $currencies = Currency::get();
        $USD = Currency::where('code', 'USD')->first() ? Currency::where('code', 'USD')->first()->value : 1;
        $ILS = Currency::where('code', 'ILS')->first() ? Currency::where('code', 'ILS')->first()->value : 3.5;
        return view('dashboard.allocations.index', compact('ILS','brokers','organizations', 'projs', 'items', 'currencies', 'USD', 'ILS'));
    }

    public function getFilterOptions(Request $request, $column)
    {
        $query = Allocation::query();

        // فلاتر التواريخ الأساسية
        if ($request->from_date) {
            $query->whereDate('date_allocation', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('date_allocation', '<=', $request->to_date);
        }

        // تطبيق الفلاتر النشطة من الأعمدة الأخرى
        if ($request->active_filters) {
            foreach ($request->active_filters as $fieldName => $values) {
                if ($fieldName === 'date_allocation' && is_array($values)) {
                    // معالجة فلتر التاريخ
                    if (isset($values['from'])) {
                        $query->whereDate('date_allocation', '>=', $values['from']);
                    }
                    if (isset($values['to'])) {
                        $query->whereDate('date_allocation', '<=', $values['to']);
                    }
                } else {
                    // فلاتر checkboxes العادية
                    $filteredValues = array_filter($values, function($value) {
                        return !in_array($value, ['الكل', 'all', 'All']);
                    });

                    if (!empty($filteredValues)) {
                        $query->whereIn($fieldName, $filteredValues);
                    }
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
     * حساب المجاميع للأعمدة المحددة
     */
    private function calculateTotals($query)
    {
        // إنشاء استعلام منفصل للمجاميع (clone للحفاظ على الفلاتر)
        $totalsQuery = clone $query;

        // إزالة ORDER BY و LIMIT لتجنب خطأ MySQL
        $totalsQuery->getQuery()->orders = null;
        $totalsQuery->getQuery()->limit = null;
        $totalsQuery->getQuery()->offset = null;

        $totals = $totalsQuery->selectRaw('
            COUNT(*) as total_count,
            COALESCE(SUM(quantity), 0) as total_quantity,
            COALESCE(SUM(price), 0) as total_price,
            COALESCE(SUM(total_dollar), 0) as total_total_dollar,
            COALESCE(SUM(allocation), 0) as total_allocation,
            COALESCE(SUM(amount), 0) as total_amount,
            COALESCE(SUM(amount_received), 0) as total_amount_received
        ')->first();

        return [
            'total_count' => $totals->total_count ?? 0,
            'quantity' => $totals->total_quantity ?? 0,
            'price' => $totals->total_price ?? 0,
            'total_dollar' => $totals->total_total_dollar ?? 0,
            'allocation' => $totals->total_allocation ?? 0,
            'amount' => $totals->total_amount ?? 0,
            'amount_received' => $totals->total_amount_received ?? 0,
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Allocation::class);
        $allocation = new Allocation();
        if($request->ajax()){
            if(AccreditationProject::where('type', 'allocation')->count() > 0){
                $allocation->budget_number =  AccreditationProject::where('type', 'allocation')->orderBy('budget_number', 'desc')->first() ? AccreditationProject::where('type', 'allocation')->orderBy('budget_number', 'desc')->first()->budget_number + 1 : 1;
            }else{
                $allocation->budget_number =  Allocation::orderBy('budget_number', 'desc')->first() ? Allocation::orderBy('budget_number', 'desc')->first()->budget_number + 1 : 1;
            }
            $allocation->date_allocation =  Carbon::now()->format('Y-m-d');
            $allocation->currency_allocation =  'USD';
            $allocation->currency_allocation_value =  '1';
            $allocation->percentage_female_administrators =  5;
            return $allocation;
        }
        // return view('dashboard.allocations.create', compact('allocation'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Allocation::class);
        $request->validate([
            'date_allocation' => 'required|date',
            'budget_number' => 'required|integer',
            'quantity' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'total_dollar' => 'nullable|numeric',
            'allocation' => 'nullable|numeric',
            'currency_allocation' => 'required|exists:currencies,code',
            'amount' => 'nullable|numeric',
            'implementation_items' => 'nullable|string',
            'date_implementation' => 'nullable|date',
            'implementation_statement' => 'nullable|string',
            'amount_received' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'number_beneficiaries' => 'nullable|integer',
        ]);

        $request->merge([
            'user_id' => Auth::user()->id,
            'user_name' => Auth::user()->name,
            'percentage_female_administrators' => $request->percentage_female_administrators / 100
        ]);
        $allocation = Allocation::create($request->all());
        if($request->ajax()) {
            return response()->json(['message' => 'تم الإضافة بنجاح']);
        }
        return redirect()->route('dashboard.allocations.index')->with('success', 'تمت عملية الاضافة بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Allocation $allocation)
    {
        if($request->ajax()){
            return response()->json($allocation);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Allocation $allocation)
    {
        if($request->ajax()) {
            $allocation->currency_allocation_name = Currency::where('code', $allocation->currency_allocation)->first() ? Currency::where('code', $allocation->currency_allocation)->first()->name : '';
            $allocation->user = $allocation->user() ?? new User();
            $allocation->percentage_female_administrators = $allocation->percentage_female_administrators * 100;
            return response()->json($allocation);
        }
        $this->authorize('update', Allocation::class);
        $editForm = true;
        $btn_label = 'تعديل';
        $files = json_decode($allocation->files, true) ?? [];
        return view('dashboard.allocations.edit', compact('allocation', 'editForm', 'btn_label', 'files'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Allocation $allocation)
    {
        $this->authorize('update', Allocation::class);
        $request->validate([
            'date_allocation' => 'required|date',
            'budget_number' => 'required|integer',
            'quantity' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'total_dollar' => 'nullable|numeric',
            'allocation' => 'nullable|numeric',
            'currency_allocation' => 'required|exists:currencies,code',
            'amount' => 'nullable|numeric',
            'implementation_items' => 'nullable|string',
            'date_implementation' => 'nullable|date',
            'implementation_statement' => 'nullable|string',
            'amount_received' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'number_beneficiaries' => 'nullable|integer',
        ]);

        $request->merge([
            'percentage_female_administrators' => $request->percentage_female_administrators / 100
        ]);
        $allocation->update($request->all());
        if($request->ajax()) {
            return response()->json(['message' => 'تم التحديث بنجاح']);
        }
        return redirect()->route('dashboard.allocations.index')->with('success', 'تمت عملية التعديل بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('delete', Allocation::class);
        $allocation = Allocation::findOrFail($id);
        $allocation->delete();

        if($request->ajax()) {
            return response()->json(['success' => 'تمت عملية الحذف بنجاح']);
        }
        return redirect()->route('dashboard.allocations.index')->with('danger', 'تمت عملية الحذف بنجاح');
    }

    public function import(Request $request){
        $this->authorize('import', Allocation::class);
        if(!$request->hasFile('file')){
            return redirect()->route('allocations.index')->with('danger', 'الرجاء تحميل الملف');
        }
        $file = $request->file('file');
        Excel::import(new AllocationsImport, $file);
        return redirect()->route('dashboard.allocations.index')->with('success', 'تمت عملية الاستيراد بنجاح');
    }

    public function print(Request $request, Allocation $allocation){
        $pdf = PDF::loadView('dashboard.reports.allocation',['allocation' => $allocation],[],
        [
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 12,
            'default_font' => 'Arial',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 30,
            'margin_bottom' => 0,
        ]);
        $time = Carbon::now();
        return $pdf->stream('التخصيص رقم '.$allocation->budget_number.'  _ '.$time.'.pdf');
    }

    public function getDataByBudgetNumber(Request $request)
    {
        $validated = $request->validate([
            'budget_number' => 'required|numeric',
        ]);

        $data = Allocation::where('budget_number', 'like', "{$validated['budget_number']}%")
            ->select(
                DB::raw('MIN(id) as id'), // أخذ أصغر ID من كل مجموعة
                'budget_number',
                'broker_name',
                'organization_name',
                'project_name',
                'item_name'
            )
            ->groupBy('budget_number', 'broker_name', 'organization_name', 'project_name', 'item_name')
            ->limit(8)
            ->get();

        return response()->json($data);
    }

    public function getDetails(Request $request)
    {
        $validated = $request->validate([
            'allocation_id' => 'required|exists:allocations,id',
        ]);

        $allocation = Allocation::findOrFail($validated['allocation_id']);
        $allocations = Allocation::where('budget_number',$allocation->budget_number)
                ->where('broker_name',$allocation->broker_name)
                ->where('organization_name',$allocation->organization_name)
                ->where('project_name',$allocation->project_name)
                ->where('item_name',$allocation->item_name)->get();

        $quantity_allocation = $allocations->sum('quantity') ?? 0;
        $amount_allocation = $allocations->sum('amount') ?? 0;
        $amount_received_allocation = $allocations->sum('amount_received') ?? 0;


        $executives = Executive::where('allocation_id', $allocation->id)->get();
        $quantity_executives = $executives->sum('quantity') ?? 0;
        $total_ils_executives = $executives->sum('total_ils') ?? 0;
        $amount_payments_executives = $executives->sum('amount_payments') ?? 0;

        return response()->json([
            'id' => $allocation->id,
            'allocation' => $allocation,
            'quantity_allocation' => $quantity_allocation,
            'amount_allocation' => $amount_allocation,
            'amount_received_allocation' => $amount_received_allocation,
            'quantity_executives' => $quantity_executives,
            'total_ils_executives' => $total_ils_executives,
            'amount_payments_executives' => $amount_payments_executives,
        ]);
    }
}
