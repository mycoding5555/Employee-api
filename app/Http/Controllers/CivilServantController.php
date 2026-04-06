<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PhotoHelper;
use App\Models\CivilServant;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CivilServantController extends Controller
{
    use PhotoHelper;

    private const ALLOWED_SORTS = [
        'last_name_kh',
        'first_name_kh',
        'gender_id',
        'position_id',
        'department_id',
        'sort',
    ];

    public function index(Request $request): \Illuminate\View\View|JsonResponse
    {
        $filters = $request->only(['name_kh', 'department_id', 'parent_id', 'position_id', 'sort_by', 'sort_dir']);

        $query = CivilServant::with(['department', 'position', 'images'])
            ->select('civil_servants.*')
            ->leftJoin('departments as dept', 'dept.id', '=', 'civil_servants.department_id')
            ->leftJoin('departments as sub_dept', 'sub_dept.id', '=', 'dept.parent_id')
            ->leftJoin('departments as parent_dept', 'parent_dept.id', '=', 'sub_dept.parent_id')
            ->addSelect(
                'dept.name_kh as department_name',
                'sub_dept.id as sub_department_id',
                'sub_dept.name_kh as sub_department_name',
                'parent_dept.id as parent_department_id',
                'parent_dept.name_kh as parent_department_name',
            );
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

                $baseDepartments = Department::where(function ($q) {
                        $q->whereIn('parent_id', [1, 2])
                            ->orWhereIn('id', [1, 2]);
                })->where('active', 1);

        $departments = $baseDepartments
            ->orderByRaw('CASE WHEN id = ? OR parent_id = ? THEN 0 WHEN id = ? OR parent_id = ? THEN 2 ELSE 1 END ASC', [1, 1, 2, 2])
            ->orderByRaw('CASE WHEN id IN (?, ?) THEN 0 ELSE 1 END ASC', [1, 2])
            ->orderByRaw('COALESCE(`sort`, id) ASC')
            ->get();

        $childDepartments = [];
        if ($request->filled('department_id')) {
            $childDepartments = Department::where('parent_id', $request->input('department_id'))
                ->where('active', 1)
                ->orderBy('sort')
                ->get();
        }

        if ($request->boolean('departments_debug')) {
            return response()->json([
                'subDepartments' => $departments->map(fn ($d) => [
                    'id' => $d->id,
                    'name_kh' => $d->name_kh,
                    'parent_id' => $d->parent_id,
                    'sort' => $d->sort,
                ]),
                'childDepartments' => $childDepartments->map(fn ($d) => [
                    'id' => $d->id,
                    'name_kh' => $d->name_kh,
                    'parent_id' => $d->parent_id,
                    'sort' => $d->sort,
                ]),
            ]);
        }

        $allChildDepts = Department::whereIn('parent_id', $departments->pluck('id'))
            ->where('active', 1)
            ->orderBy('sort')
            ->get()
            ->groupBy('parent_id');

        return view('civil-servants.index', [
            'civilServants' => $query->paginate(20)->withQueryString(),
            'subDepartments' => $departments,
            'childDepartments' => $childDepartments,
            'allChildDepts' => $allChildDepts,
            'positions' => $this->getFilteredPositions($request),
            'filters' => $filters,
            'photoBaseUrl' => $this->photoBaseUrl(),
        ]);
    }

    /**
     * Alias kept for the named route `civil-servants.search`.
     */
    public function search(Request $request): \Illuminate\View\View|JsonResponse
    {
        return $this->index($request);
    }

    /**
     * AJAX search – returns full JSON collection (client-side pagination).
     */
    public function ajaxSearch(Request $request): JsonResponse
    {
        if ($request->boolean('echo')) {
            return response()->json(['input' => $request->all()]);
        }

        $query = CivilServant::with(['department', 'position', 'images'])
            ->select('civil_servants.*')
            ->leftJoin('departments as dept', 'dept.id', '=', 'civil_servants.department_id')
            ->leftJoin('departments as sub_dept', 'sub_dept.id', '=', 'dept.parent_id')
            ->leftJoin('departments as parent_dept', 'parent_dept.id', '=', 'sub_dept.parent_id')
            ->addSelect(
                'dept.name_kh as department_name',
                'sub_dept.id as sub_department_id',
                'sub_dept.name_kh as sub_department_name',
                'parent_dept.id as parent_department_id',
                'parent_dept.name_kh as parent_department_name',
            );
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $items = $query->get()->map(function ($cs) {
            $cs->images = $cs->images->filter(function ($img) {
                return $this->isValidImageName($img->name ?? null);
            })->values();
            return $cs;
        });

        return response()->json($items);
    }

    // ──────────────────────────────────────────────
    //  Private – query helpers
    // ──────────────────────────────────────────────

    private function applyFilters(Builder $query, Request $request): void
    {
        $query->where('civil_servants.status_type_id', 1);

        if ($request->filled('name_kh')) {
            $search = $request->input('name_kh');
            $query->where(function (Builder $q) use ($search) {
                $q->where('last_name_kh', 'like', "%{$search}%")
                  ->orWhere('first_name_kh', 'like', "%{$search}%");
            });
        }

        if ($request->filled('parent_id')) {
            $query->whereIn('civil_servants.department_id', $this->departmentWithChildIds($request->input('parent_id')));
        } elseif ($request->filled('department_id')) {
            $query->whereIn('civil_servants.department_id', $this->departmentWithChildIds($request->input('department_id')));
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }
    }

    private function applySorting(Builder $query, Request $request): void
    {
        $sortBy = $request->input('sort_by', 'position_id');
        $sortDir = $request->input('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (! in_array($sortBy, self::ALLOWED_SORTS, true)) {
            $sortBy = 'position_id';
        }

        if ($sortBy === 'position_id') {
            $query->orderBy(
                Position::select('sort')->whereColumn('positions.id', 'civil_servants.position_id')->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'department_id') {
            $query->orderByRaw(
                'CASE WHEN (SELECT parent_id FROM departments WHERE id = civil_servants.department_id) IN (0,1) OR civil_servants.department_id = 1 THEN 0
                      WHEN (SELECT parent_id FROM departments WHERE id = civil_servants.department_id) = 2 OR civil_servants.department_id = 2 THEN 2
                      ELSE 1 END ASC'
            )->orderBy(
                Department::select('sort')->whereColumn('departments.id', 'civil_servants.department_id')->limit(1),
                $sortDir
            );
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
    }

    private function getFilteredPositions(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $posQuery = Position::where('active', 1)->whereHas('civilServants', function ($q) {
            $q->where('status_type_id', 1);
        });

        if ($request->filled('parent_id')) {
            $deptIds = $this->departmentWithChildIds($request->input('parent_id'));
            $posQuery->whereHas('civilServants', function ($q) use ($deptIds) {
                $q->where('status_type_id', 1)->whereIn('department_id', $deptIds);
            });
        } elseif ($request->filled('department_id')) {
            $deptIds = $this->departmentWithChildIds($request->input('department_id'));
            $posQuery->whereHas('civilServants', function ($q) use ($deptIds) {
                $q->where('status_type_id', 1)->whereIn('department_id', $deptIds);
            });
        }

        return $posQuery->orderBy('sort')->get();
    }
}
