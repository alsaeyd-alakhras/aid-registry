<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class InstitutionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view', Institution::class);

        if ($request->ajax()) {
            $institutions = Institution::query()
                ->withCount('distributions')
                ->orderBy('name', 'asc');

            if ($request->column_filters) {
                foreach ($request->column_filters as $fieldName => $values) {
                    if (empty($values)) {
                        continue;
                    }

                    $filteredValues = array_filter($values, function ($value) {
                        return !in_array($value, ['الكل', 'all', 'All']);
                    });

                    if (empty($filteredValues)) {
                        continue;
                    }

                    if ($fieldName === 'is_active') {
                        $institutions->whereIn($fieldName, $filteredValues);
                    } elseif ($fieldName === 'distributions_count') {
                        $institutions->whereIn('distributions_count', $filteredValues);
                    } else {
                        $institutions->where($fieldName, 'like', '%' . implode('%', $filteredValues) . '%');
                    }
                }
            }

            return DataTables::of($institutions)
                ->addIndexColumn()
                ->addColumn('edit', function ($institution) {
                    return $institution->id;
                })
                ->addColumn('is_active_badge', function ($institution) {
                    return $institution->is_active
                        ? '<span class="badge bg-success">مفعل</span>'
                        : '<span class="badge bg-danger">معطل</span>';
                })
                ->addColumn('delete', function ($institution) {
                    return $institution->id;
                })
                ->rawColumns(['is_active_badge'])
                ->make(true);
        }

        return view('dashboard.institutions.index');
    }

    public function getFilterOptions(Request $request, $column)
    {
        $query = Institution::query()->withCount('distributions');

        if ($request->active_filters) {
            foreach ($request->active_filters as $fieldName => $values) {
                $filteredValues = array_filter($values, function ($value) {
                    return !in_array($value, ['الكل', 'all', 'All']);
                });

                if (empty($filteredValues)) {
                    continue;
                }

                if ($fieldName === 'is_active') {
                    $query->whereIn($fieldName, $filteredValues);
                } elseif ($fieldName === 'distributions_count') {
                    $query->whereIn('distributions_count', $filteredValues);
                } else {
                    $query->where($fieldName, 'like', '%' . implode('%', $filteredValues) . '%');
                }
            }
        }

        $uniqueValues = [];
        if ($column === 'is_active') {
            $uniqueValues = ['1' => 'مفعل', '0' => 'معطل'];
        } elseif ($column === 'distributions_count') {
            $uniqueValues = $query->pluck('distributions_count')->filter()->unique()->values()->toArray();
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

    public function create(Request $request)
    {
        $this->authorize('create', Institution::class);

        if ($request->ajax()) {
            return response()->json(new Institution());
        }
    }

    public function store(Request $request)
    {
        $this->authorize('create', Institution::class);

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:institutions',
            'notes' => 'nullable|string',
        ]);

        $institution = Institution::create($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'تم الإضافة بنجاح', 'institution' => $institution]);
        }

        return redirect()->route('dashboard.institutions.index')->with('success', 'تمت عملية الإضافة بنجاح');
    }

    public function edit(Request $request, Institution $institution)
    {
        $this->authorize('update', Institution::class);

        if ($request->ajax()) {
            return response()->json($institution);
        }
    }

    public function update(Request $request, Institution $institution)
    {
        $this->authorize('update', Institution::class);

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:institutions,name,' . $institution->id,
            'notes' => 'nullable|string',
        ]);

        $institution->update($validated);

        if ($request->ajax()) {
            return response()->json(['message' => 'تم التحديث بنجاح']);
        }

        return redirect()->route('dashboard.institutions.index')->with('success', 'تمت عملية التعديل بنجاح');
    }

    public function destroy(Request $request, Institution $institution)
    {
        $this->authorize('delete', Institution::class);

        if ($institution->distributions()->exists()) {
            $institution->update(['is_active' => false]);

            $message = 'لا يمكن حذف المؤسسة لأنها مرتبطة بعمليات صرف، تم تعطيلها بدلاً من الحذف';

            if ($request->ajax()) {
                return response()->json(['error' => $message], 422);
            }

            return redirect()->route('dashboard.institutions.index')->with('danger', $message);
        }

        $institution->delete();

        if ($request->ajax()) {
            return response()->json(['success' => 'تمت عملية الحذف بنجاح']);
        }

        return redirect()->route('dashboard.institutions.index')->with('success', 'تمت عملية الحذف بنجاح');
    }
}
