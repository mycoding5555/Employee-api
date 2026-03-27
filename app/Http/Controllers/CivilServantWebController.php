<?php

namespace App\Http\Controllers;

use App\Models\Civil_servants;
use App\Models\Departments;
use App\Models\Positions;
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

class CivilServantWebController extends Controller
{
    private const ROOT_DEPARTMENT_ID = 7; 

    private const ALLOWED_SORTS = [
        'last_name_kh',
        'first_name_kh',
        'gender_id',
        'position_id',
        'department_id',
        'sort',
    ];

    // ──────────────────────────────────────────────
    //  Public – listing
    // ──────────────────────────────────────────────

    /**
     * Server-side paginated index page.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['name_kh', 'department_id', 'position_id', 'sort_by', 'sort_dir']);

        $query = Civil_servants::with(['department', 'position', 'images']);
        $this->applyFilters($query, $request);
        $this->applySorting($query, $request);

        return view('civil-servants.index', [
            'civilServants'  => $query->paginate(20)->withQueryString(),
            'department'     => Departments::find(self::ROOT_DEPARTMENT_ID),
            'subDepartments' => Departments::where('parent_id', self::ROOT_DEPARTMENT_ID)->orderBy('sort')->get(),
            'positions'      => Positions::where('active', 1)->whereHas('civilServants')->orderBy('sort')->get(),
            'filters'        => $filters,
            'photoBaseUrl'   => $this->photoBaseUrl(),
        ]);
    }

    /**
     * Alias kept for the named route `civil-servants.search`.
     */
    public function search(Request $request)
    {
        return $this->index($request);
    }

    /**
     * AJAX search – returns full JSON collection (client-side pagination).
     */
    public function ajaxSearch(Request $request): JsonResponse
    {
        $query = Civil_servants::with(['department', 'position', 'images']);
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
        $civilServant = Civil_servants::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $civilServant->images->first();

        abort_unless($image, 404);

        // Remote redirect
        $photoBaseUrl = $this->photoBaseUrl();
        if ($photoBaseUrl) {
            return redirect($photoBaseUrl . '/' . rawurlencode($image->name));
        }

        // Local storage
        $localPath = $this->resolveLocalPath($civilServant, $image);
        if ($localPath) {
            return response()->file($localPath);
        }

        // HRMIS fallback
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
        $civilServant = Civil_servants::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $civilServant->images->first();

        abort_unless($image, 404, 'Photo not found');

        $downloadName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh . '_' . $image->name;

        // Local storage
        $localPath = $this->resolveLocalPath($civilServant, $image);
        if ($localPath) {
            return response()->download($localPath, $downloadName);
        }

        // HRMIS by ID
        $body = $this->fetchRemotePhoto($civilServantId);
        if ($body) {
            return $this->downloadFromString($body['content'], $downloadName, $body['content_type']);
        }

        // Legacy: PHOTO_BASE_URL by image filename
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
        $departmentId = $departmentId ?: self::ROOT_DEPARTMENT_ID;

        @set_time_limit(0);

        $deptIds = $this->departmentWithChildIds($departmentId);

        $civilServants = Civil_servants::with(['images', 'position'])
            ->whereIn('department_id', $deptIds)
            ->whereHas('images')
            ->get();

        if (request()->boolean('debug')) {
            return $this->buildDebugResponse($departmentId, $deptIds, $civilServants);
        }

        if ($civilServants->isEmpty()) {
            return back()->with('error', 'No photos found for this department');
        }

        $dept = Departments::find($departmentId);
        $deptName = ($dept && !empty($dept->name_kh)) ? $dept->name_kh : 'department_' . $departmentId;
        $folderPrefix = $deptName . '/';

        $safeZipName = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $deptName) . '.zip';
        $zipPath = storage_path('app/temp/' . $safeZipName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create zip');
        }

        $photoBaseUrl = $this->photoBaseUrl();
        $tempFiles = [];
        $addedFiles = 0;

        foreach ($civilServants as $civilServant) {
            $image = $civilServant->images->first();
            if (!$image) {
                continue;
            }

            $baseName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh;
            $entryName = $baseName . '_' . $image->name;

            // 1) Local storage
            $localPath = $this->resolveLocalPath($civilServant, $image);
            if ($localPath) {
                $zip->addFile($localPath, $folderPrefix . $entryName);
                $addedFiles++;
                continue;
            }

            // 2) HRMIS by ID
            $body = $this->fetchRemotePhoto($civilServant->id);
            if ($body) {
                $ext = $this->extensionFromContentType($body['content_type']);
                $zipEntryName = $baseName . $ext;
                $tempFile = $this->writeTempFile($body['content']);
                $zip->addFile($tempFile, $folderPrefix . $zipEntryName);
                $tempFiles[] = $tempFile;
                $addedFiles++;
                continue;
            }

            // 3) PHOTO_BASE_URL by filename
            if ($photoBaseUrl) {
                $body = $this->fetchUrl($photoBaseUrl . '/' . rawurlencode($image->name));
                if ($body) {
                    $tempFile = $this->writeTempFile($body['content']);
                    $zip->addFile($tempFile, $folderPrefix . $entryName);
                    $tempFiles[] = $tempFile;
                    $addedFiles++;
                }
            }
        }

        $zip->close();

        foreach ($tempFiles as $tempFile) {
            @unlink($tempFile);
        }

        if ($addedFiles === 0) {
            @unlink($zipPath);
            return back()->with('error', 'No photo files found on disk');
        }

        return response()->download($zipPath, $deptName . '.zip')->deleteFileAfterSend(true);
    }

    /**
     * JSON list of civil servants + download URLs for a department.
     */
    public function departmentPhotoList(int $departmentId): JsonResponse
    {
        $departmentId = $departmentId ?: self::ROOT_DEPARTMENT_ID;

        $dept = Departments::find($departmentId);
        $deptName = ($dept && !empty($dept->name_kh)) ? $dept->name_kh : 'department_' . $departmentId;

        $deptIds = $this->departmentWithChildIds($departmentId);

        $civilServants = Civil_servants::with('images')
            ->whereIn('department_id', $deptIds)
            ->whereHas('images')
            ->get();

        $items = $civilServants->map(function (Civil_servants $cs) {
            $image = $cs->images->first();

            return $image ? [
                'id'          => $cs->id,
                'name'        => $cs->last_name_kh . ' ' . $cs->first_name_kh,
                'downloadUrl' => '/civil-servants/download-photo/' . $cs->id,
            ] : null;
        })->filter()->values();

        return response()->json([
            'folderName' => $deptName,
            'items'      => $items,
        ]);
    }

    // ──────────────────────────────────────────────
    //  Private – query helpers
    // ──────────────────────────────────────────────

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('name_kh')) {
            $search = $request->input('name_kh');
            $query->where(function (Builder $q) use ($search) {
                $q->where('last_name_kh', 'like', "%{$search}%")
                  ->orWhere('first_name_kh', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->whereIn('department_id', $this->departmentWithChildIds($request->input('department_id')));
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }
    }

    private function applySorting(Builder $query, Request $request): void
    {
        $sortBy = $request->input('sort_by', 'position_id');
        $sortDir = $request->input('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        if (!in_array($sortBy, self::ALLOWED_SORTS, true)) {
            $sortBy = 'position_id';
        }

        if ($sortBy === 'position_id') {
            $query->join('positions', 'civil_servants.position_id', '=', 'positions.id')
                  ->orderBy('positions.sort', $sortDir)
                  ->select('civil_servants.*');
        } elseif ($sortBy === 'department_id') {
            $query->join('departments as d', 'civil_servants.department_id', '=', 'd.id')
                  ->leftJoin('departments as parent_d', 'd.parent_id', '=', 'parent_d.id')
                  ->orderByRaw(
                      'CASE WHEN d.parent_id = ? THEN d.sort WHEN d.id = ? THEN 0 ELSE COALESCE(parent_d.sort, d.sort) END ' . $sortDir,
                      [self::ROOT_DEPARTMENT_ID, self::ROOT_DEPARTMENT_ID]
                  )
                  ->orderBy('d.sort', $sortDir)
                  ->select('civil_servants.*');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
    }

    private function departmentWithChildIds(int|string $departmentId): array
    {
        return Departments::where('id', $departmentId)
            ->orWhere('parent_id', $departmentId)
            ->pluck('id')
            ->toArray();
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

    /**
     * Build the list of candidate local storage paths for a photo.
     */
    private function localPhotoPaths(Civil_servants $civilServant, $image): array
    {
        $paths = [];
        $positionName = $civilServant->position->name_kh ?? null;

        if ($positionName) {
            $paths[] = 'photos/' . $positionName . '/' . $image->name;
        }
        $paths[] = 'photos/' . $image->name;
        $paths[] = $image->name;

        return $paths;
    }

    /**
     * Resolve the absolute local disk path for a photo, or null if not found.
     */
    private function resolveLocalPath(Civil_servants $civilServant, $image): ?string
    {
        foreach ($this->localPhotoPaths($civilServant, $image) as $path) {
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->path($path);
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
        if (!$base) {
            return null;
        }

        try {
            $response = Http::timeout(10)->get($base . '/' . rawurlencode($civilServantId));
        } catch (\Exception) {
            return null;
        }

        if (!$response->successful() || $response->body() === '') {
            return null;
        }

        return [
            'content'      => $response->body(),
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

        if (!$response->successful() || $response->body() === '') {
            return null;
        }

        return [
            'content'      => $response->body(),
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
            str_contains($contentType, 'png')  => '.png',
            str_contains($contentType, 'gif')  => '.gif',
            str_contains($contentType, 'webp') => '.webp',
            default                             => '.jpg',
        };
    }

    private function buildDebugResponse(int $departmentId, array $deptIds, $civilServants): JsonResponse
    {
        $photoBaseUrl = $this->photoBaseUrl();
        $hrmisBase = $this->hrmisApiBase();

        $debug = [
            'departmentId'      => $departmentId,
            'deptIds'           => $deptIds,
            'countCivilServants' => $civilServants->count(),
            'items'             => [],
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
                    'civilServantId'  => $cs->id,
                    'name'            => $cs->last_name_kh . ' ' . $cs->first_name_kh,
                    'image'           => $img->name,
                    'localExists'     => $localExists,
                    'hrmisUrl'        => $hrmisUrl,
                    'hrmisAccessible' => $hrmisOk,
                    'remoteUrl'       => $remoteUrl,
                    'remoteAccessible' => $remoteOk,
                    'paths'           => $paths,
                ];
            }
        }

        return response()->json($debug);
    }
}
