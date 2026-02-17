<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Imports\ExecutivesImport;
use App\Models\Currency;
use App\Models\Executive;
use App\Models\Merchant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;
use Yajra\DataTables\Facades\DataTables;

class ExecutiveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index(Request $request)
    // {
    //     $this->authorize('view', Executive::class);
    //     if($request->ajax()) {
    //         $year = $request->year ?? Carbon::now()->year;
    //         // جلب بيانات المستخدمين من الجدول
    //         $executives = Executive::query()->orderBy('implementation_date', 'asc')->orderBy('budget_number', 'asc');

    //         // التصفية بناءً على السنة
    //         $executives->whereYear('implementation_date', $request->year);

    //         // التصفية بناءً على التواريخ
    //         if ($request->from_date != null && $request->to_date != null) {
    //             $executives->whereBetween('implementation_date', [$request->from_date, $request->to_date]);
    //         }

    //         return DataTables::of($executives)
    //                 ->addIndexColumn()  // إضافة عمود الترقيم التلقائي
    //                 ->addColumn('edit', function ($executive) {
    //                     return $executive->id;
    //                 })
    //                 ->addColumn('delete', function ($executive) {
    //                     return $executive->id;
    //                 })
    //                 ->make(true);
    //     }

    //     $ILS = Currency::where('code', 'ILS')->first()->value;
    //     // get data from executive table
    //     $accounts = Executive::select('account')->distinct()->pluck('account')->toArray();
    //     $affiliates = Executive::select('affiliate_name')->distinct()->pluck('affiliate_name')->toArray();
    //     $details = Executive::select('detail')->distinct()->pluck('detail')->toArray();
    //     $receiveds = Executive::select('received')->distinct()->pluck('received')->toArray();

    //     // get data from models
    //     $brokers = Executive::select('broker_name')->distinct()->pluck('broker_name')->toArray();
    //     $fields = Executive::select('field')->distinct()->pluck('field')->toArray();
    //     $projects = Executive::select('project_name')->distinct()->pluck('project_name')->toArray();
    //     $saends = Executive::select('project_name')->distinct()->pluck('project_name')->toArray();
    //     $items =  Executive::select('item_name')->distinct()->pluck('item_name')->toArray();

    //     return view('dashboard.executives.index', compact('ILS','accounts', 'affiliates', 'receiveds', 'details', 'brokers','saends', 'projects', 'items', 'fields'));
    // }

    public function index()
    {
        $request = request();
        if($request->ajax()) {
            $year = $request->year ?? Carbon::now()->year;

            $executives = Executive::query()->orderBy('implementation_date', 'asc')->orderBy('budget_number', 'asc');

            $executives->whereYear('implementation_date', $year);


            if ($request->from_date) {
                $executives->whereDate('implementation_date', '>=', $request->from_date);
            }
            if ($request->to_date) {
                $executives->whereDate('implementation_date', '<=', $request->to_date);
            }


            if ($request->column_filters) {
                foreach ($request->column_filters as $fieldName => $values) {
                    if (!empty($values)) {
                        if ($fieldName === 'implementation_date' && is_array($values)) {
                            // معالجة فلتر التاريخ من column_filters
                            if (isset($values['from'])) {
                                $executives->whereDate('implementation_date', '>=', $values['from']);
                            }
                            if (isset($values['to'])) {
                                $executives->whereDate('implementation_date', '<=', $values['to']);
                            }
                        } else {
                            // فلاتر checkboxes العادية
                            $filteredValues = array_filter($values, function($value) {
                                return !in_array($value, ['الكل', 'all', 'All']);
                            });

                            if (!empty($filteredValues)) {
                                $executives->whereIn($fieldName, $filteredValues);
                            }
                        }
                    }
                }
            }
            // حساب المجاميع للبيانات المفلترة
            $totals = $this->calculateTotals($executives);
            return DataTables::of($executives)
                    ->addIndexColumn()  // إضافة عمود الترقيم التلقائي
                    ->addColumn('edit', function ($executive) {
                        return $executive->id;
                    })
                    ->addColumn('delete', function ($executive) {
                        return $executive->id;
                    })
                    ->with('totals', $totals)
                    ->make(true);
        }
        $ILS = Currency::where('code', 'ILS')->first()->value;
        // get data from executive table
        $accounts = Executive::select('account')->distinct()->pluck('account')->toArray();
        $affiliates = Executive::select('affiliate_name')->distinct()->pluck('affiliate_name')->toArray();
        $details = Executive::select('detail')->distinct()->pluck('detail')->toArray();
        $receiveds = Executive::select('received')->distinct()->pluck('received')->toArray();

        // get data from models
        $brokers = Executive::select('broker_name')->distinct()->pluck('broker_name')->toArray();
        $fields_name = Executive::select('field')->distinct()->pluck('field')->toArray();
        $projects = Executive::select('project_name')->distinct()->pluck('project_name')->toArray();
        $saends = Executive::select('project_name')->distinct()->pluck('project_name')->toArray();
        $items =  Executive::select('item_name')->distinct()->pluck('item_name')->toArray();
        return view('dashboard.executives.index', compact('ILS','accounts', 'affiliates', 'receiveds', 'details', 'brokers','saends', 'projects', 'items', 'fields_name'));
    }

    public function getFilterOptions(Request $request, $column)
    {
        $query = Executive::query();

        // فلاتر التواريخ الأساسية
        if ($request->from_date) {
            $query->whereDate('implementation_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('implementation_date', '<=', $request->to_date);
        }

        // تطبيق الفلاتر النشطة من الأعمدة الأخرى
        if ($request->active_filters) {
            foreach ($request->active_filters as $fieldName => $values) {
                if ($fieldName === 'implementation_date' && is_array($values)) {
                    // معالجة فلتر التاريخ
                    if (isset($values['from'])) {
                        $query->whereDate('implementation_date', '>=', $values['from']);
                    }
                    if (isset($values['to'])) {
                        $query->whereDate('implementation_date', '<=', $values['to']);
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
            COALESCE(SUM(total_ils), 0) as total_total_ils,
            COALESCE(SUM(amount_payments), 0) as total_amount_payments
        ')->first();

        return [
            'total_count' => $totals->total_count ?? 0,
            'quantity' => $totals->total_quantity ?? 0,
            'price' => $totals->total_price ?? 0,
            'total_ils' => $totals->total_total_ils ?? 0,
            'amount_payments' => $totals->total_amount_payments ?? 0,
        ];
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Executive::class);
        $executive = new Executive();
        if($request->ajax()){
            $executive->implementation_date =  Carbon::now()->format('Y-m-d');
            return $executive;
        }
        return view('dashboard.executives.create', compact('executive'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Executive::class);
        $request->validate([
            'implementation_date' => 'required|date',
            'budget_number' => 'nullable|integer',
            'account' => 'required|string',
            'affiliate_name' => 'required|string',
            'detail' => 'nullable|string',
            'quantity' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'total_ils' => 'nullable|numeric',
            'received' => 'nullable|string',
            'executive' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'amount_payments' => 'nullable|numeric',
            'payment_mechanism' => 'nullable|string',
        ]);

        $month = Carbon::parse($request->implementation_date)->format('m');
        $request->merge([
            'month' => $month,
            'user_id' => Auth::user()->id,
            'user_name' => Auth::user()->name,
        ]);

        Executive::create($request->all());
        if($request->ajax()) {
            return response()->json(['message' => 'تم الإضافة بنجاح']);
        }
        return redirect()->route('dashboard.executives.index')->with('success', 'تمت عملية الاضافة بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Executive $executive)
    {
        if($request->ajax()){
            return response()->json($executive);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Executive $executive)
    {
        if($request->ajax()) {
            $executive->user = $executive->user() ?? new User();
            return response()->json($executive);
        }
        $this->authorize('update', Executive::class);
        $editForm = true;
        $btn_label = 'تعديل';
        $files = json_decode($executive->files, true) ?? [];
        return view('dashboard.executives.edit', compact('executive', 'editForm', 'btn_label', 'files'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Executive $executive)
    {
        $this->authorize('update', Executive::class);
        $request->validate([
            'implementation_date' => 'required|date',
            'budget_number' => 'nullable|integer',
            'account' => 'required|string',
            'affiliate_name' => 'required|string',
            'detail' => 'nullable|string',
            'quantity' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'total_ils' => 'nullable|numeric',
            'received' => 'nullable|string',
            'executive' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'amount_payments' => 'nullable|numeric',
            'payment_mechanism' => 'nullable|string',
        ]);

        $month = Carbon::parse($request->implementation_date)->format('m');

        $request->merge([
            'month' => $month,
        ]);
        $executive->update($request->all());
        if($request->ajax()) {
            return response()->json(['message' => 'تم التحديث بنجاح']);
        }
        return redirect()->route('dashboard.executives.index')->with('success', 'تمت عملية التعديل بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, $id)
    {
        $this->authorize('delete', Executive::class);

        $executive = Executive::findOrFail($id);

        $executive->delete();
        if($request->ajax()) {
            return response()->json(['success' => 'تمت عملية الحذف بنجاح']);
        }
        return redirect()->route('dashboard.executives.index')->with('danger', 'تمت عملية الحذف بنجاح');
    }


    public function import(Request $request){
        $this->authorize('import', Executive::class);
        if(!$request->hasFile('file')){
            return redirect()->route('dashboard.executives.index')->with('danger', 'الرجاء تحميل الملف');
        }
        $file = $request->file('file');
        Excel::import(new ExecutivesImport, $file);
        $executives = Executive::query();
        foreach($executives->get() as $executive){
            $date = Carbon::parse($executive->implementation_date)->format('Y-m-d');
            $month = Carbon::parse($date)->format('m');
            $executive->update([
                'month' => $month
            ]);
        }
        return redirect()->route('dashboard.executives.index')->with('success', 'تمت عملية الاستيراد بنجاح');
    }

    public function getDataByName(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
        ]);
        $data = Executive::where('account', 'like', "%{$validated['name']}%")
                    ->select('id','account')
                    ->select(
                        DB::raw('MIN(id) as id'), // أخذ أصغر ID من كل مجموعة
                        'account',
                    )
                    ->groupBy('account')
                    ->orderBy('account', 'asc')
                    ->limit(8)
                    ->get();
        return response()->json($data);
    }

    public function getMerchantDetails(Request $request)
    {
        $request->validate([
            'merchant_account' => 'required|string',
        ]);
        $merchant = Merchant::where('name', $request->merchant_account)->first();
        if($merchant){
            return response()->json([
                'error' => 'Merchant this account found',
                'merchant' => $merchant,
                'status' => 407
            ]);
        }
        $data = Executive::where('account', '=', $request->merchant_account)->get();

        $total_ils = $data->sum('total_ils');
        $amount_payments = $data->sum('amount_payments');


        return response()->json([
            'total_ils' => $total_ils,
            'amount_payments' => $amount_payments
        ]);
    }
}
