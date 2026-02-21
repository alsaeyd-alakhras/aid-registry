<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AidItem;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AidItemController extends Controller
{
    /**
     * Display a listing of aid items
     */
    public function index(Request $request)
    {
        $this->authorize('view', AidItem::class);

        if ($request->ajax()) {
            $aidItems = AidItem::query()->orderBy('name', 'asc');

            // Apply filters
            if ($request->column_filters) {
                foreach ($request->column_filters as $fieldName => $values) {
                    if (!empty($values)) {
                        $filteredValues = array_filter($values, function ($value) {
                            return !in_array($value, ['الكل', 'all', 'All']);
                        });

                        if (!empty($filteredValues)) {
                            if ($fieldName === 'is_active') {
                                $aidItems->whereIn($fieldName, $filteredValues);
                            } else {
                                $aidItems->where($fieldName, 'like', '%' . implode('%', $filteredValues) . '%');
                            }
                        }
                    }
                }
            }

            return DataTables::of($aidItems)
                ->addIndexColumn()
                ->addColumn('edit', function ($aidItem) {
                    return $aidItem->id;
                })
                ->addColumn('estimated_value_formatted', function ($aidItem) {
                    return $aidItem->estimated_value !== null
                        ? number_format((float) $aidItem->estimated_value, 2, '.', '')
                        : '';
                })
                ->addColumn('is_active_badge', function ($aidItem) {
                    return $aidItem->is_active ? '<span class="badge bg-success">مفعل</span>' : '<span class="badge bg-danger">معطل</span>';
                })
                ->addColumn('delete', function ($aidItem) {
                    return $aidItem->id;
                })
                ->rawColumns(['is_active_badge'])
                ->make(true);
        }

        return view('dashboard.aid-items.index');
    }

    /**
     * Get filter options for a specific column
     */
    public function getFilterOptions(Request $request, $column)
    {
        $query = AidItem::query();

        // Apply active filters
        if ($request->active_filters) {
            foreach ($request->active_filters as $fieldName => $values) {
                $filteredValues = array_filter($values, function ($value) {
                    return !in_array($value, ['الكل', 'all', 'All']);
                });

                if (!empty($filteredValues)) {
                    if ($fieldName === 'is_active') {
                        $query->whereIn($fieldName, $filteredValues);
                    } else {
                        $query->where($fieldName, 'like', '%' . implode('%', $filteredValues) . '%');
                    }
                }
            }
        }

        // Get unique values for the requested column
        $uniqueValues = [];
        if ($column === 'is_active') {
            $uniqueValues = ['1' => 'مفعل', '0' => 'معطل'];
        } else {
            $uniqueValues = $query->whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->pluck($column)
                ->filter()
                ->values()
                ->toArray();
        }

        return response()->json($uniqueValues);
    }

    /**
     * Show form for creating a new aid item
     */
    public function create(Request $request)
    {
        $this->authorize('create', AidItem::class);

        if ($request->ajax()) {
            return response()->json(new AidItem());
        }
    }

    /**
     * Store a newly created aid item
     */
    public function store(Request $request)
    {
        $this->authorize('create', AidItem::class);

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:aid_items',
            'estimated_value' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $aidItem = AidItem::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'تم الإضافة بنجاح', 'aid_item' => $aidItem]);
        }

        return redirect()->route('dashboard.aid-items.index')->with('success', 'تمت عملية الإضافة بنجاح');
    }

    /**
     * Show aid item details for editing
     */
    public function edit(Request $request, AidItem $aidItem)
    {
        $this->authorize('update', AidItem::class);

        if ($request->ajax()) {
            return response()->json($aidItem);
        }
    }

    /**
     * Update aid item details
     */
    public function update(Request $request, AidItem $aidItem)
    {
        $this->authorize('update', AidItem::class);

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:aid_items,name,' . $aidItem->id,
            'estimated_value' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $aidItem->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'تم التحديث بنجاح']);
        }

        return redirect()->route('dashboard.aid-items.index')->with('success', 'تمت عملية التعديل بنجاح');
    }

    /**
     * Delete an aid item
     */
    public function destroy(Request $request, AidItem $aidItem)
    {
        $this->authorize('delete', AidItem::class);

        // Check if aid item has distributions
        if ($aidItem->distributions()->exists()) {
            if ($request->ajax()) {
                return response()->json(['error' => 'لا يمكن حذف نوع المساعدة لأنه مستخدم في عمليات صرف'], 422);
            }
            return redirect()->route('dashboard.aid-items.index')->with('danger', 'لا يمكن حذف نوع المساعدة لأنه مستخدم في عمليات صرف');
        }

        $aidItem->delete();

        if ($request->ajax()) {
            return response()->json(['success' => 'تمت عملية الحذف بنجاح']);
        }

        return redirect()->route('dashboard.aid-items.index')->with('success', 'تمت عملية الحذف بنجاح');
    }
}
