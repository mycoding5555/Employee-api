<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PhotoHelper;
use App\Models\CivilServant;
use App\Models\Department;
use App\Models\Position;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CivilservantIdController extends Controller
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

    public function index(Request $request)
    {
        $request->validate([
            'name_kh'       => 'nullable|string|max:255',
            'department_id' => 'nullable|integer',
            'parent_id'     => 'nullable|integer',
            'position_id'   => 'nullable|integer',
            'sort_by'       => 'nullable|string|in:' . implode(',', self::ALLOWED_SORTS),
            'sort_dir'      => 'nullable|string|in:asc,desc',
        ]);

        $filters = $request->only(['name_kh', 'department_id', 'parent_id', 'position_id', 'sort_by', 'sort_dir']);

        $query = CivilServant::with([
            'department.parent.parent',
            'position:id,name_kh,name_short,abb,sort',
        ])->select('civil_servants.*');

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $departments = Cache::remember('dept_tree_top', 300, function () {
            return Department::select('id', 'name_kh', 'parent_id', 'sort', 'active')
                ->where(function ($q) {
                    $q->whereIn('parent_id', [1, 2])
                        ->orWhereIn('id', [1, 2]);
                })->where('active', 1)
                ->orderByRaw('CASE WHEN id = ? OR parent_id = ? THEN 0 WHEN id = ? OR parent_id = ? THEN 2 ELSE 1 END ASC', [1, 1, 2, 2])
                ->orderByRaw('CASE WHEN id IN (?, ?) THEN 0 ELSE 1 END ASC', [1, 2])
                ->orderByRaw('COALESCE(`sort`, id) ASC')
                ->get()->toArray();
        });
        $departments = collect($departments)->map(fn($d) => (object) $d);

        $childDepartments = collect();
        if ($request->filled('department_id')) {
            $deptId = $request->input('department_id');
            $childDepartments = Cache::remember('dept_children_list_' . $deptId, 300, function () use ($deptId) {
                return Department::select('id', 'name_kh', 'parent_id', 'sort')
                    ->where('parent_id', $deptId)
                    ->where('active', 1)
                    ->orderBy('sort')
                    ->get()->toArray();
            });
            $childDepartments = collect($childDepartments)->map(fn($d) => (object) $d);
        }

        $allChildDepts = Cache::remember('dept_tree_all_children', 300, function () use ($departments) {
            return Department::select('id', 'name_kh', 'parent_id', 'sort')
                ->whereIn('parent_id', $departments->pluck('id'))
                ->where('active', 1)
                ->orderBy('sort')
                ->get()->toArray();
        });
        $allChildDepts = collect($allChildDepts)->map(fn($d) => (object) $d)->groupBy('parent_id');

        $deptGroupMap = [];
        foreach ($departments as $d) {
            $deptGroupMap[$d->id] = $d->name_kh;
            if (isset($allChildDepts[$d->id])) {
                foreach ($allChildDepts[$d->id] as $child) {
                    $deptGroupMap[$child->id] = $d->name_kh;
                }
            }
        }

        $idCardCivilServantIds = $this->getCivilServantIdsWithIdCard();

        $paginated = $query->paginate(20)->withQueryString();
        $paginated->getCollection()->transform(function ($cs) use ($idCardCivilServantIds) {
            $dept   = $cs->department;
            $sub    = $dept?->parent;
            $parent = $sub?->parent;

            $cs->department_name        = $dept->name_kh ?? null;
            $cs->sub_department_id      = $sub->id ?? null;
            $cs->sub_department_name    = $sub->name_kh ?? null;
            $cs->parent_department_id   = $parent->id ?? null;
            $cs->parent_department_name = $parent->name_kh ?? null;
            $cs->has_id_card            = $idCardCivilServantIds->contains($cs->id);

            return $cs;
        });

        return view('civil-servants-id.index', [
            'civilServants'    => $paginated,
            'subDepartments'   => $departments,
            'childDepartments' => $childDepartments,
            'allChildDepts'    => $allChildDepts,
            'positions'        => $this->getFilteredPositions($request),
            'filters'          => $filters,
            'deptGroupMap'     => $deptGroupMap,
        ]);
    }

    public function ajaxSearch(Request $request): JsonResponse
    {
        $request->validate([
            'name_kh'       => 'nullable|string|max:255',
            'department_id' => 'nullable|integer',
            'parent_id'     => 'nullable|integer',
            'position_id'   => 'nullable|integer',
            'sort_by'       => 'nullable|string|in:' . implode(',', self::ALLOWED_SORTS),
            'sort_dir'      => 'nullable|string|in:asc,desc',
            'per_page'      => 'nullable|integer|min:1|max:100',
        ]);

        $query = CivilServant::with([
            'department.parent.parent',
            'position:id,name_kh,name_short,abb,sort',
        ])->select('civil_servants.*');

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $idCardCivilServantIds = $this->getCivilServantIdsWithIdCard();

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginated = $query->paginate($perPage)->withQueryString();

        $paginated->getCollection()->transform(function ($cs) use ($idCardCivilServantIds) {
            $dept   = $cs->department;
            $sub    = $dept?->parent;
            $parent = $sub?->parent;

            $cs->department_name        = $dept->name_kh ?? null;
            $cs->sub_department_id      = $sub->id ?? null;
            $cs->sub_department_name    = $sub->name_kh ?? null;
            $cs->parent_department_id   = $parent->id ?? null;
            $cs->parent_department_name = $parent->name_kh ?? null;
            $cs->has_id_card            = $idCardCivilServantIds->contains($cs->id);

            return $cs;
        });

        return response()->json($paginated);
    }

    public function downloadPdf(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);

        $request->validate([
            'name_kh'       => 'nullable|string|max:255',
            'department_id' => 'nullable|integer',
            'parent_id'     => 'nullable|integer',
            'position_id'   => 'nullable|integer',
            'sort_by'       => 'nullable|string|in:' . implode(',', self::ALLOWED_SORTS),
            'sort_dir'      => 'nullable|string|in:asc,desc',
        ]);

        $query = CivilServant::with([
            'department.parent.parent',
            'position:id,name_kh,name_short,abb,sort',
        ])->select('civil_servants.*');

        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $idCardCivilServantIds = $this->getCivilServantIdsWithIdCard();
        $total = (clone $query)->count();
        $chunkSize = 500;
        $filename = 'civil-servants-id-card-list-' . now()->format('Y-m-d') . '.pdf';

        // Small dataset: render directly
        if ($total <= $chunkSize) {
            $civilServants = $query->get()->map(fn ($cs) => $this->transformForPdf($cs, $idCardCivilServantIds));

            $pdf = Pdf::loadView('civil-servants-id.pdf', [
                'civilServants' => $civilServants,
                'total'         => $total,
                'offset'        => 0,
            ])
                ->setOption('chroot', [base_path(), storage_path('fonts')])
                ->setOption('defaultFont', 'khmeros')
                ->setPaper('a4', 'landscape');

            return $pdf->download($filename);
        }

        // Large dataset: render in chunks, then merge with FPDI
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempFiles = [];
        $offset = 0;

        try {
            while ($offset < $total) {
                $chunk = (clone $query)
                    ->offset($offset)
                    ->limit($chunkSize)
                    ->get()
                    ->map(fn ($cs) => $this->transformForPdf($cs, $idCardCivilServantIds));

                $pdf = Pdf::loadView('civil-servants-id.pdf', [
                    'civilServants' => $chunk,
                    'total'         => $total,
                    'offset'        => $offset,
                ])
                    ->setOption('chroot', [base_path(), storage_path('fonts')])
                    ->setOption('defaultFont', 'khmeros')
                    ->setPaper('a4', 'landscape');

                $tempFile = $tempDir . '/pdf-chunk-' . $offset . '-' . uniqid() . '.pdf';
                file_put_contents($tempFile, $pdf->output());
                $tempFiles[] = $tempFile;

                unset($chunk, $pdf);
                gc_collect_cycles();

                $offset += $chunkSize;
            }

            // Merge all chunk PDFs using FPDI
            $merger = new \setasign\Fpdi\Fpdi();
            foreach ($tempFiles as $file) {
                $pageCount = $merger->setSourceFile($file);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl = $merger->importPage($i);
                    $size = $merger->getTemplateSize($tpl);
                    $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $merger->useTemplate($tpl);
                }
            }

            $content = $merger->Output('S', '');

            return response($content, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } finally {
            // Clean up temp files
            foreach ($tempFiles as $file) {
                @unlink($file);
            }
        }
    }

    private function transformForPdf($cs, $idCardCivilServantIds)
    {
        $dept = $cs->department;
        $sub  = $dept?->parent;

        $cs->department_name     = $dept->name_kh ?? null;
        $cs->sub_department_name = $sub->name_kh ?? null;
        $cs->has_id_card         = $idCardCivilServantIds->contains($cs->id);

        return $cs;
    }

    // ──────────────────────────────────────────────
    //  Private helpers
    // ──────────────────────────────────────────────

    private function getCivilServantIdsWithIdCard(): \Illuminate\Support\Collection
    {
        $ids = Cache::remember('civil_servant_ids_with_id_card', 300, function () {
            return DB::table('document_deltas')
                ->join('documents', 'documents.id', '=', 'document_deltas.document_id')
                ->where('documents.document_type_id', 10)
                ->distinct()
                ->pluck('document_deltas.civil_servant_id')
                ->all();
        });

        return collect($ids);
    }

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
        $sortBy  = $request->input('sort_by', 'position_id');
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
        $posQuery = Position::select('id', 'name_kh', 'name_short', 'abb', 'sort')
            ->where('active', 1);

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
        } else {
            $posQuery->whereHas('civilServants', function ($q) {
                $q->where('status_type_id', 1);
            });
        }

        return $posQuery->orderBy('sort')->get();
    }
}
