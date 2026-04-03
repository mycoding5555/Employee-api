<?php

namespace App\Http\Controllers;

use App\Models\CivilServant;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;
use ZipStream\ZipStream;

class CivilServantPhotoController extends Controller
{
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
        $filters = $request->only(['name_kh', 'department_id', 'position_id', 'sort_by', 'sort_dir']);

        $query = CivilServant::with(['department', 'position', 'images']);
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        $baseDepartments = Department::where(function ($q) {
            $q->whereIn('parent_id', [1, 2])
              ->orWhereIn('id', [1, 2]);
        });

        $departments = $baseDepartments
            ->orderByRaw('CASE WHEN id = ? OR parent_id = ? THEN 0 WHEN id = ? OR parent_id = ? THEN 2 ELSE 1 END ASC', [1, 1, 2, 2])
            ->orderByRaw('CASE WHEN id IN (?, ?) THEN 0 ELSE 1 END ASC', [1, 2])
            ->orderByRaw('COALESCE(`sort`, id) ASC')
            ->get();

        $childDepartments = [];
        if ($request->filled('department_id')) {
            $childDepartments = Department::where('parent_id', $request->input('department_id'))
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

        $query = CivilServant::with(['department', 'position', 'images']);
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        return response()->json($query->get());
    }

    // ──────────────────────────────────────────────
    //  Public – single photo
    // ──────────────────────────────────────────────

    /**
     * Show a civil servant's photo (inline).
     */
    public function showPhoto(string $civilServantId): Response|RedirectResponse|StreamedResponse|BinaryFileResponse
    {
        $civilServant = CivilServant::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $civilServant->images->first();

        abort_unless($image, 404);

        $photoBaseUrl = $this->photoBaseUrl();
        if ($photoBaseUrl) {
            return redirect($photoBaseUrl . '/' . rawurlencode($image->name));
        }

        $localPath = $this->resolveLocalPath($civilServant, $image);
        if ($localPath) {
            return response()->file($localPath);
        }

        $body = $this->fetchRemotePhoto($civilServantId);
        if ($body) {
            return response($body['content'], 200)
                ->header('Content-Type', $body['content_type'])
                ->header('Cache-Control', 'public, max-age=86400');
        }

        abort(404);
    }

    /**
     * Proxy an external HRMIS photo endpoint by civil servant ID.
     */
    public function proxyHrmisPhotoById(string $civilServantId): Response
    {
        $body = $this->fetchRemotePhoto($civilServantId);

        abort_unless($body, 404);

        return response($body['content'], 200)
            ->header('Content-Type', $body['content_type']);
    }

    // ──────────────────────────────────────────────
    //  Public – downloads
    // ──────────────────────────────────────────────

    /**
     * Download a single civil servant's photo.
     */
    public function downloadPhoto(string $civilServantId): BinaryFileResponse
    {
        $civilServant = CivilServant::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $civilServant->images->first();

        abort_unless($image, 404, 'Photo not found');

        $downloadName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh . '_' . $image->name;

        $localPath = $this->resolveLocalPath($civilServant, $image);
        if ($localPath) {
            return response()->download($localPath, $downloadName);
        }

        $body = $this->fetchRemotePhoto($civilServantId);
        if ($body) {
            return $this->downloadFromString($body['content'], $downloadName, $body['content_type']);
        }

        $photoBaseUrl = $this->photoBaseUrl();
        if ($photoBaseUrl) {
            $body = $this->fetchUrl($photoBaseUrl . '/' . rawurlencode($image->name));
            if ($body) {
                return $this->downloadFromString($body['content'], $downloadName, $body['content_type']);
            }
        }

        abort(404, 'Photo not found');
    }

    /**
     * Download a ZIP of all photos for a department (and its children).
     */
    public function downloadDepartment(int $departmentId): JsonResponse|BinaryFileResponse|RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $deptIds = $this->departmentWithChildIds($departmentId);

        $civilServants = CivilServant::with(['images', 'position'])
            ->whereIn('department_id', $deptIds)
            ->whereHas('images')
            ->get()
            ->sortBy(fn (CivilServant $cs) => $cs->position->sort ?? PHP_INT_MAX);

        if (request()->boolean('debug')) {
            return $this->buildDebugResponse($departmentId, $deptIds, $civilServants);
        }

        if ($civilServants->isEmpty()) {
            return back()->with('error', 'No photos found for this department');
        }

        $dept = Department::find($departmentId);
        $deptName = ($dept && ! empty($dept->name_kh)) ? $dept->name_kh : 'department_' . $departmentId;
        $folderPrefix = $deptName . '/';
        $safeZipName = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $deptName) . '.zip';
        $zipPath = storage_path('app/temp/' . $safeZipName);

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        // Write ZIP to temp file using ZipStream (produces Mac-compatible archives)
        $outputStream = fopen($zipPath, 'wb');
        $zip = new ZipStream(
            outputStream: $outputStream,
            sendHttpHeaders: false,
            defaultCompressionMethod: \ZipStream\CompressionMethod::STORE,
            enableZip64: false,
            defaultEnableZeroHeader: false,
        );

        $photoBaseUrl = $this->photoBaseUrl();
        $number = 1;

        foreach ($civilServants as $civilServant) {
            $image = $civilServant->images->first();
            if (! $image) {
                continue;
            }

            $baseName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh;
            $numberedPrefix = $number . '_';
            $entryName = $numberedPrefix . $baseName . '_' . $image->name;

            // 1) Local storage
            $localPath = $this->resolveLocalPath($civilServant, $image);
            if ($localPath) {
                $zip->addFileFromPath(fileName: $folderPrefix . $entryName, path: $localPath);
                $number++;

                continue;
            }

            // 2) HRMIS by ID
            $body = $this->fetchRemotePhoto($civilServant->id);
            if ($body) {
                $ext = $this->extensionFromContentType($body['content_type']);
                $zipEntryName = $numberedPrefix . $baseName . $ext;
                $zip->addFile(fileName: $folderPrefix . $zipEntryName, data: $body['content']);
                $number++;

                continue;
            }

            // 3) PHOTO_BASE_URL by filename
            if ($photoBaseUrl) {
                $body = $this->fetchUrl($photoBaseUrl . '/' . rawurlencode($image->name));
                if ($body) {
                    $zip->addFile(fileName: $folderPrefix . $entryName, data: $body['content']);
                    $number++;
                }
            }
        }

        $zip->finish();
        fclose($outputStream);

        if ($number === 1) {
            @unlink($zipPath);

            return back()->with('error', 'No photo files found on disk');
        }

        return response()->download($zipPath, $deptName . '.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * JSON list of civil servants + download URLs for a department.
     */
    public function departmentPhotoList(int $departmentId): JsonResponse
    {
        $dept = Department::find($departmentId);
        $deptName = ($dept && ! empty($dept->name_kh)) ? $dept->name_kh : 'department_' . $departmentId;

        $deptIds = $this->departmentWithChildIds($departmentId);

        $civilServants = CivilServant::with('images')
            ->whereIn('department_id', $deptIds)
            ->whereHas('images')
            ->get();

        $items = $civilServants->map(function (CivilServant $cs) {
            $image = $cs->images->first();

            return $image ? [
                'id' => $cs->id,
                'name' => $cs->last_name_kh . ' ' . $cs->first_name_kh,
                'downloadUrl' => '/civil-servants/download-photo/' . $cs->id,
            ] : null;
        })->filter()->values();

        return response()->json([
            'folderName' => $deptName,
            'items' => $items,
        ]);
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

        if ($request->filled('department_id')) {
            $query->whereIn('civil_servants.department_id', $this->departmentWithChildIds($request->input('department_id')));
        } elseif ($request->filled('general_department_id')) {
            $query->whereIn('civil_servants.department_id', $this->departmentWithChildIds($request->input('general_department_id')));
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

    private function departmentWithChildIds(int|string $departmentId): array
    {
        $ids = collect([(int) $departmentId]);
        $parentIds = $ids;

        for ($i = 0; $i < 5; $i++) {
            $childIds = Department::whereIn('parent_id', $parentIds)->pluck('id');
            if ($childIds->isEmpty()) {
                break;
            }
            $ids = $ids->merge($childIds);
            $parentIds = $childIds;
        }

        return $ids->unique()->toArray();
    }

    private function getFilteredPositions(Request $request): \Illuminate\Database\Eloquent\Collection
    {
        $posQuery = Position::where('active', 1)->whereHas('civilServants', function ($q) {
            $q->where('status_type_id', 1);
        });

        if ($request->filled('department_id')) {
            $deptIds = $this->departmentWithChildIds($request->input('department_id'));
            $posQuery->whereHas('civilServants', function ($q) use ($deptIds) {
                $q->where('status_type_id', 1)->whereIn('department_id', $deptIds);
            });
        } elseif ($request->filled('general_department_id')) {
            $deptIds = $this->departmentWithChildIds($request->input('general_department_id'));
            $posQuery->whereHas('civilServants', function ($q) use ($deptIds) {
                $q->where('status_type_id', 1)->whereIn('department_id', $deptIds);
            });
        }

        return $posQuery->orderBy('sort')->get();
    }

    // ──────────────────────────────────────────────
    //  Private – photo resolution
    // ──────────────────────────────────────────────

    private function photoBaseUrl(): string
    {
        return rtrim(config('services.hrmis.photo_base_url', ''), '/');
    }

    private function hrmisApiBase(): string
    {
        return rtrim(config('services.hrmis.photo_api_base', ''), '/');
    }

    private function localPhotoPaths(CivilServant $civilServant, $image): array
    {
        $paths = [];
        $positionName = $civilServant->position->name_kh ?? null;

        if ($positionName) {
            // Strip invisible Unicode whitespace (zero-width spaces, etc.) that Flysystem rejects
            $cleanName = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{00AD}]+/u', '', $positionName);
            $paths[] = 'photos/' . $cleanName . '/' . $image->name;
        }
        $paths[] = 'photos/' . $image->name;
        $paths[] = $image->name;

        return $paths;
    }

    private function resolveLocalPath(CivilServant $civilServant, $image): ?string
    {
        foreach ($this->localPhotoPaths($civilServant, $image) as $path) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    return Storage::disk('public')->path($path);
                }
            } catch (\Exception) {
                continue;
            }
        }

        return null;
    }

    /**
     * Fetch a photo from the HRMIS API by civil servant ID.
     *
     * @return array{content: string, content_type: string}|null
     */
    private function fetchRemotePhoto(string|int $civilServantId): ?array
    {
        $base = $this->hrmisApiBase();
        if (! $base) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get($base . '/' . rawurlencode($civilServantId));
        } catch (\Exception) {
            return null;
        }

        if (! $response->successful() || $response->body() === '') {
            return null;
        }

        return [
            'content' => $response->body(),
            'content_type' => $response->header('Content-Type', 'image/jpeg'),
        ];
    }

    /**
     * Generic URL fetch helper.
     *
     * @return array{content: string, content_type: string}|null
     */
    private function fetchUrl(string $url): ?array
    {
        try {
            $response = Http::timeout(10)->get($url);
        } catch (\Exception) {
            return null;
        }

        if (! $response->successful() || $response->body() === '') {
            return null;
        }

        return [
            'content' => $response->body(),
            'content_type' => $response->header('Content-Type', 'image/jpeg'),
        ];
    }

    // ──────────────────────────────────────────────
    //  Private – utilities
    // ──────────────────────────────────────────────

    private function downloadFromString(string $content, string $filename, string $contentType = 'image/jpeg'): BinaryFileResponse
    {
        $tempPath = $this->writeTempFile($content);
        $response = response()->download($tempPath, $filename);
        $response->deleteFileAfterSend(true);
        $response->headers->set('Content-Type', $contentType);

        return $response;
    }

    private function writeTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'photo_');
        file_put_contents($path, $content);

        return $path;
    }

    private function extensionFromContentType(string $contentType): string
    {
        return match (true) {
            str_contains($contentType, 'png') => '.png',
            str_contains($contentType, 'gif') => '.gif',
            str_contains($contentType, 'webp') => '.webp',
            default => '.jpg',
        };
    }

    private function buildDebugResponse(int $departmentId, array $deptIds, $civilServants): JsonResponse
    {
        $photoBaseUrl = $this->photoBaseUrl();
        $hrmisBase = $this->hrmisApiBase();

        $debug = [
            'departmentId' => $departmentId,
            'deptIds' => $deptIds,
            'countCivilServants' => $civilServants->count(),
            'items' => [],
        ];

        foreach ($civilServants->take(3) as $cs) {
            foreach ($cs->images as $img) {
                $paths = $this->localPhotoPaths($cs, $img);
                $localExists = $this->resolveLocalPath($cs, $img) !== null;

                $hrmisUrl = $hrmisBase ? $hrmisBase . '/' . rawurlencode($cs->id) : null;
                $hrmisOk = $hrmisUrl ? ($this->fetchRemotePhoto($cs->id) !== null) : null;

                $remoteUrl = $photoBaseUrl ? $photoBaseUrl . '/' . rawurlencode($img->name) : null;
                $remoteOk = $remoteUrl ? ($this->fetchUrl($remoteUrl) !== null) : null;

                $debug['items'][] = [
                    'civilServantId' => $cs->id,
                    'name' => $cs->last_name_kh . ' ' . $cs->first_name_kh,
                    'image' => $img->name,
                    'localExists' => $localExists,
                    'hrmisUrl' => $hrmisUrl,
                    'hrmisAccessible' => $hrmisOk,
                    'remoteUrl' => $remoteUrl,
                    'remoteAccessible' => $remoteOk,
                    'paths' => $paths,
                ];
            }
        }

        return response()->json($debug);
    }
}
