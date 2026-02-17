<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;


class OfficeController extends Controller
{
    /**
     * Display a listing of offices
     */
    public function index(Request $request)
    {
        $this->authorize('view', Office::class);

        if ($request->ajax()) {
            $offices = Office::query()->orderBy('name', 'asc');

            // Apply filters
            if ($request->column_filters) {
                foreach ($request->column_filters as $fieldName => $values) {
                    if (!empty($values)) {
                        $filteredValues = array_filter($values, function ($value) {
                            return !in_array($value, ['الكل', 'all', 'All']);
                        });

                        if (!empty($filteredValues)) {
                            if ($fieldName === 'is_active') {
                                $offices->whereIn($fieldName, $filteredValues);
                            } else {
                                $offices->where($fieldName, 'like', '%' . implode('%', $filteredValues) . '%');
                            }
                        }
                    }
                }
            }

            return DataTables::of($offices)
                ->addIndexColumn()
                ->addColumn('edit', function ($office) {
                    return $office->id;
                })
                ->addColumn('is_active_badge', function ($office) {
                    return $office->is_active ? '<span class="badge bg-success">مفعل</span>' : '<span class="badge bg-danger">معطل</span>';
                })
                ->addColumn('delete', function ($office) {
                    return $office->id;
                })
                ->rawColumns(['is_active_badge'])
                ->make(true);
        }

        return view('dashboard.offices.index');
    }

    /**
     * Get filter options for a specific column
     */
    public function getFilterOptions(Request $request, $column)
    {
        $query = Office::query();

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
     * Show form for creating a new office
     */
    public function create(Request $request)
    {
        $this->authorize('create', Office::class);

        if ($request->ajax()) {
            return response()->json(new Office());
        }
    }

    /**
     * Store a newly created office
     */
    public function store(Request $request)
    {
        $this->authorize('create', Office::class);

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:offices',
            'location' => 'nullable|string|max:180',
            'notes' => 'nullable|string',
        ]);

        $office = Office::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'تم الإضافة بنجاح', 'office' => $office]);
        }

        return redirect()->route('dashboard.offices.index')->with('success', 'تمت عملية الإضافة بنجاح');
    }

    /**
     * Show office details for editing
     */
    public function edit(Request $request, Office $office)
    {
        $this->authorize('update', Office::class);

        if ($request->ajax()) {
            return response()->json($office);
        }
    }

    /**
     * Update office details
     */
    public function update(Request $request, Office $office)
    {
        $this->authorize('update', Office::class);

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:offices,name,' . $office->id,
            'location' => 'nullable|string|max:180',
            'notes' => 'nullable|string',
        ]);

        $office->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'تم التحديث بنجاح']);
        }

        return redirect()->route('dashboard.offices.index')->with('success', 'تمت عملية التعديل بنجاح');
    }

    /**
     * Delete an office
     */
    public function destroy(Request $request, Office $office)
    {
        $this->authorize('delete', Office::class);

        // Check if office has distributions
        if ($office->distributions()->exists()) {
            if ($request->ajax()) {
                return response()->json(['error' => 'لا يمكن حذف المكتب لأنه يحتوي على عمليات صرف'], 422);
            }
            return redirect()->route('dashboard.offices.index')->with('danger', 'لا يمكن حذف المكتب لأنه يحتوي على عمليات صرف');
        }

        $office->delete();

        if ($request->ajax()) {
            return response()->json(['success' => 'تمت عملية الحذف بنجاح']);
        }

        return redirect()->route('dashboard.offices.index')->with('success', 'تمت عملية الحذف بنجاح');
    }
}
