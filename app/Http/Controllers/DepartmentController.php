<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\PhotoHelper;
use App\Models\CivilServant;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipStream\ZipStream;

class DepartmentController extends Controller
{
    use PhotoHelper;

    /**
     * Download a ZIP of all photos for a department (and its children).
     */
    public function downloadDepartment(int $departmentId): JsonResponse|BinaryFileResponse|RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $deptIds = $this->departmentWithChildIds($departmentId);

        $civilServants = CivilServant::with(['images', 'position', 'department'])
            ->whereIn('department_id', $deptIds)
            ->where('status_type_id', 1)
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

        // Load all departments in the tree keyed by id
        $allDepts = Department::whereIn('id', $deptIds)->get()->keyBy('id');

        // Build folder path for each department by walking up to the root
        $deptFolderPaths = [];
        foreach ($allDepts as $d) {
            $deptFolderPaths[$d->id] = $this->buildDeptFolderPath($d, $departmentId, $allDepts);
        }

        // Group civil servants by their department_id
        $grouped = $civilServants->groupBy('department_id');

        $safeZipName = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $deptName) . '.zip';
        $zipPath = storage_path('app/temp/' . $safeZipName);

        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $outputStream = fopen($zipPath, 'wb');
        $zip = new ZipStream(
            outputStream: $outputStream,
            sendHttpHeaders: false,
            defaultCompressionMethod: \ZipStream\CompressionMethod::STORE,
            enableZip64: false,
            defaultEnableZeroHeader: false,
        );

        $photoBaseUrl = $this->photoBaseUrl();
        $totalAdded = 0;

        foreach ($grouped as $deptId => $members) {
            $folderPrefix = ($deptFolderPaths[$deptId] ?? $deptName) . '/';

            $number = 1;
            foreach ($members as $civilServant) {
                $image = $this->firstValidImageFromCollection($civilServant->images);
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
                    $totalAdded++;
                    continue;
                }

                // 2) HRMIS by ID
                $body = $this->fetchRemotePhoto($civilServant->id);
                if ($body) {
                    $ext = $this->extensionFromContentType($body['content_type']);
                    $zipEntryName = $numberedPrefix . $baseName . $ext;
                    $zip->addFile(fileName: $folderPrefix . $zipEntryName, data: $body['content']);
                    $number++;
                    $totalAdded++;
                    continue;
                }

                // 3) PHOTO_BASE_URL by filename
                if ($photoBaseUrl) {
                    $body = $this->fetchUrl($photoBaseUrl . '/' . rawurlencode($image->name));
                    if ($body) {
                        $zip->addFile(fileName: $folderPrefix . $entryName, data: $body['content']);
                        $number++;
                        $totalAdded++;
                    }
                }
            }
        }

        $zip->finish();
        fclose($outputStream);

        if ($totalAdded === 0) {
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
            ->where('status_type_id', 1)
            ->whereHas('images')
            ->get();

        $items = $civilServants->map(function (CivilServant $cs) {
            $image = $this->firstValidImageFromCollection($cs->images);

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
    //  Private helpers
    // ──────────────────────────────────────────────

    /**
     * Build the folder path for a department by walking up to the root department.
     */
    private function buildDeptFolderPath(Department $dept, int $rootDeptId, $allDepts): string
    {
        $segments = [];
        $current = $dept;

        while ($current) {
            $segments[] = $current->name_kh ?: ('dept_' . $current->id);
            if ($current->id == $rootDeptId) {
                break;
            }
            $current = $allDepts->get($current->parent_id);
        }

        return implode('/', array_reverse($segments));
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
