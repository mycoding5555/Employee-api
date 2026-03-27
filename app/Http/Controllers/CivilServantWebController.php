<?php

namespace App\Http\Controllers;

use App\Models\Civil_servants;
use App\Models\Departments;
use App\Models\Positions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class CivilServantWebController extends Controller
{
    public function index(Request $request)
    {
        $department = Departments::find(7);
     
        $subDepartments = Departments::where('parent_id', 7)   
            ->orderBy('sort')
            ->get();
        $positions = Positions::where('active', 1)
            ->whereHas('civilServants')
            ->orderBy('sort')
            ->get();
        $filters = $request->only(['name_kh', 'department_id', 'position_id', 'sort_by', 'sort_dir']);

        $query = Civil_servants::with(['department', 'position', 'images']);

        if ($request->filled('name_kh')) {
            $search = $request->input('name_kh');
            $query->where(function ($q) use ($search) {
                $q->where('last_name_kh', 'like', '%' . $search . '%')
                  ->orWhere('first_name_kh', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('department_id')) {
            $deptId = $request->input('department_id');
            $deptIds = Departments::where('id', $deptId)
                ->orWhere('parent_id', $deptId)
                ->pluck('id')
                ->toArray();
            $query->whereIn('department_id', $deptIds);
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'position_id');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['last_name_kh', 'first_name_kh', 'gender_id', 'position_id', 'department_id', 'sort'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'position_id';
        }
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';
        if ($sortBy === 'position_id') {
            $query->join('positions', 'civil_servants.position_id', '=', 'positions.id')
                  ->orderBy('positions.sort', $sortDir)
                  ->select('civil_servants.*');
        } elseif ($sortBy === 'department_id') {
            $query->join('departments as d', 'civil_servants.department_id', '=', 'd.id')
                  ->leftJoin('departments as parent_d', 'd.parent_id', '=', 'parent_d.id')
                  ->orderByRaw('CASE WHEN d.parent_id = 7 THEN d.sort WHEN d.id = 7 THEN 0 ELSE COALESCE(parent_d.sort, d.sort) END ' . $sortDir)
                  ->orderBy('d.sort', $sortDir)
                  ->select('civil_servants.*');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $civilServants = $query->paginate(20)->withQueryString();

        return view('civil-servants.index', [
            'civilServants'  => $civilServants,
            'department'     => $department,
            'subDepartments' => $subDepartments,
            'positions'      => $positions,
            'filters'        => $filters,
            'photoBaseUrl'   => rtrim(env('PHOTO_BASE_URL', ''), '/'),
            
        ]);
    }

    public function search(Request $request)
    {
        $filters = $request->only(['name_kh', 'department_id', 'position_id', 'sort_by', 'sort_dir']);
        $query = Civil_servants::with(['department', 'position', 'images']);

        if ($request->filled('name_kh')) {
            $search = $request->input('name_kh');
            $query->where(function ($q) use ($search) {
                $q->where('last_name_kh', 'like', '%' . $search . '%')
                  ->orWhere('first_name_kh', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('department_id')) {
            $deptId = $request->input('department_id');
            $deptIds = Departments::where('id', $deptId)
                ->orWhere('parent_id', $deptId)
                ->pluck('id')
                ->toArray();
            $query->whereIn('department_id', $deptIds);
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'position_id');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['last_name_kh', 'first_name_kh', 'gender_id', 'position_id', 'department_id', 'sort'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'position_id';
        }
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';
        if ($sortBy === 'position_id') {
            $query->join('positions', 'civil_servants.position_id', '=', 'positions.id')
                  ->orderBy('positions.sort', $sortDir)
                  ->select('civil_servants.*');
        } elseif ($sortBy === 'department_id') {
            $query->join('departments as d', 'civil_servants.department_id', '=', 'd.id')
                  ->leftJoin('departments as parent_d', 'd.parent_id', '=', 'parent_d.id')
                  ->orderByRaw('CASE WHEN d.parent_id = 7 THEN d.sort WHEN d.id = 7 THEN 0 ELSE COALESCE(parent_d.sort, d.sort) END ' . $sortDir)
                  ->orderBy('d.sort', $sortDir)
                  ->select('civil_servants.*');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return view('civil-servants.index', [
            'civilServants'  => $query->paginate(20)->withQueryString(),
            'department'     => Departments::find(7),
            'subDepartments' => Departments::where('parent_id', 7)
                ->orWhereIn('parent_id', Departments::where('parent_id', 7)->pluck('id'))
                ->orderBy('sort')
                ->get(),
            'positions'      => Positions::where('active', 1)->whereHas('civilServants')->orderBy('sort')->get(),
            'filters'        => $filters,
            'photoBaseUrl'   => rtrim(env('PHOTO_BASE_URL', ''), '/'),
            
        ]);
    }

    public function ajaxSearch(Request $request)
    {
        $query = Civil_servants::with(['department', 'position', 'images']);

        if ($request->filled('name_kh')) {
            $search = $request->input('name_kh');
            $query->where(function ($q) use ($search) {
                $q->where('last_name_kh', 'like', '%' . $search . '%')
                  ->orWhere('first_name_kh', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('department_id')) {
            $deptId = $request->input('department_id');
            $deptIds = Departments::where('id', $deptId)
                ->orWhere('parent_id', $deptId)
                ->pluck('id')
                ->toArray();
            $query->whereIn('department_id', $deptIds);
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'position_id');
        $sortDir = $request->input('sort_dir', 'asc');
        $allowedSorts = ['last_name_kh', 'first_name_kh', 'gender_id', 'position_id', 'department_id', 'sort'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'position_id';
        }
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';
        if ($sortBy === 'position_id') {
            $query->join('positions', 'civil_servants.position_id', '=', 'positions.id')
                  ->orderBy('positions.sort', $sortDir)
                  ->select('civil_servants.*');
        } elseif ($sortBy === 'department_id') {
            $query->join('departments as d', 'civil_servants.department_id', '=', 'd.id')
                  ->leftJoin('departments as parent_d', 'd.parent_id', '=', 'parent_d.id')
                  ->orderByRaw('CASE WHEN d.parent_id = 7 THEN d.sort WHEN d.id = 7 THEN 0 ELSE COALESCE(parent_d.sort, d.sort) END ' . $sortDir)
                  ->orderBy('d.sort', $sortDir)
                  ->select('civil_servants.*');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return response()->json($query->get());
    }

    public function showPhoto($civilServantId)
    {
        $civilServant = Civil_servants::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $civilServant->images->first();

        if (!$image) {
            abort(404);
        }

        // If remote photo URL is configured, redirect there
        $photoBaseUrl = rtrim(env('PHOTO_BASE_URL', ''), '/');
        if ($photoBaseUrl) {
            return redirect($photoBaseUrl . '/' . rawurlencode($image->name));
        }

        // Try local storage (position subfolder first, then flat)
        $positionName = $civilServant->position->name_kh ?? null;
        $possiblePaths = [];
        if ($positionName) {
            $possiblePaths[] = 'photos/' . $positionName . '/' . $image->name;
        }
        $possiblePaths[] = 'photos/' . $image->name;
        $possiblePaths[] = $image->name;

        foreach ($possiblePaths as $path) {
            if (Storage::disk('public')->exists($path)) {
                $filePath = Storage::disk('public')->path($path);
                return response()->file($filePath);
            }
        }

        // Try HRMIS endpoint by civil servant ID
        $hrmisBase = rtrim(env('HRMIS_PHOTO_BASE', 'https://mef-pd.net/hrmis/api/profile_image'), '/');
        if ($hrmisBase) {
            try {
                $remote = Http::timeout(10)->get($hrmisBase . '/' . rawurlencode($civilServantId));
            } catch (\Exception $e) {
                $remote = null;
            }

            if ($remote && $remote->successful() && $remote->body() !== '') {
                $contentType = $remote->header('Content-Type', 'image/jpeg');
                return response($remote->body(), 200)
                    ->header('Content-Type', $contentType)
                    ->header('Cache-Control', 'public, max-age=86400');
            }
        }

        abort(404);
    }

    /**
     * Proxy an external HRMIS photo endpoint that expects a civil servant ID.
     * Example external URL: https://mef-pd.net/hrmis/api/profile_image/{id}
     */
    public function proxyHrmisPhotoById($civilServantId)
    {
        $externalBase = rtrim(env('HRMIS_PHOTO_BASE', 'https://mef-pd.net/hrmis/api/profile_image'), '/');
        $url = $externalBase . '/' . rawurlencode($civilServantId);

        try {
            $res = Http::timeout(10)->get($url);
        } catch (\Exception $e) {
            abort(404);
        }

        if (!$res->successful()) {
            abort(404);
        }

        $contentType = $res->header('Content-Type', 'image/jpeg');
        return response($res->body(), 200)->header('Content-Type', $contentType);
    }

    public function downloadPhoto($civilServantId)
    {
        $civilServant = Civil_servants::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $civilServant->images->first();

        if (!$image) {
            abort(404, 'Photo not found');
        }

        $downloadName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh . '_' . $image->name;

        //local storage 
        $positionName = $civilServant->position->name_kh ?? null;
        $possiblePaths = [];
        if ($positionName) {
            $possiblePaths[] = 'photos/' . $positionName . '/' . $image->name;
        }
        $possiblePaths[] = 'photos/' . $image->name;
        $possiblePaths[] = $image->name;

        foreach ($possiblePaths as $path) {
            if (Storage::disk('public')->exists($path)) {
                $filePath = Storage::disk('public')->path($path);
                return response()->download($filePath, $downloadName);
            }
        }

        // Try HRMIS endpoint by civil servant ID first (some HRMIS APIs expect an ID)
        $hrmisBase = rtrim(env('HRMIS_PHOTO_BASE', 'https://mef-pd.net/hrmis/api/profile_image'), '/');
        if ($hrmisBase) {
            try {
                $remote = Http::timeout(10)->get($hrmisBase . '/' . rawurlencode($civilServantId));
            } catch (\Exception $e) {
                $remote = null;
            }

            if ($remote && $remote->successful() && $remote->body() !== '') {
                $tempPath = tempnam(sys_get_temp_dir(), 'photo_');
                file_put_contents($tempPath, $remote->body());
                $contentType = $remote->header('Content-Type', 'image/jpeg');
                $response = response()->download($tempPath, $downloadName);
                $response->deleteFileAfterSend(true);
                $response->headers->set('Content-Type', $contentType);
                return $response;
            }
        }

        // Fallback: try configured PHOTO_BASE_URL using image filename (legacy behavior)
        $photoBaseUrl = rtrim(env('PHOTO_BASE_URL', 'https://mef-pd.net/hrmis/api/civilservant/getImage'), '/');
        if ($photoBaseUrl) {
            $remoteUrl = $photoBaseUrl . '/' . rawurlencode($image->name);
            $tempPath = tempnam(sys_get_temp_dir(), 'photo_');
            $contents = @file_get_contents($remoteUrl);
            if ($contents !== false) {
                file_put_contents($tempPath, $contents);
                return response()->download($tempPath, $downloadName)->deleteFileAfterSend(true);
            }
        }

        abort(404, 'Photo not found');
    }

    public function downloadDepartment($departmentId)
    {
        // default to root department (7) when missing
        $departmentId = $departmentId ?: 7;
        // Allow longer processing for bulk photo downloads
        @set_time_limit(0);
        $deptIds = Departments::where('id', $departmentId)
            ->orWhere('parent_id', $departmentId)
            ->pluck('id')
            ->toArray();

        $civilServants = Civil_servants::with(['images', 'position'])
            ->whereIn('department_id', $deptIds)
            ->whereHas('images')
            ->get();

        // If debugging via query param, return details to help debug missing photos
        if (request()->boolean('debug')) {
            $photoBaseUrl = rtrim(env('PHOTO_BASE_URL', ''), '/');
            $hrmisBase = rtrim(env('HRMIS_PHOTO_BASE', 'https://mef-pd.net/hrmis/api/profile_image'), '/');
            $debug = ['departmentId' => $departmentId, 'deptIds' => $deptIds, 'countCivilServants' => $civilServants->count(), 'items' => []];
            foreach ($civilServants->take(3) as $cs) {
                foreach ($cs->images as $img) {
                    $paths = [];
                    $positionName = $cs->position->name_kh ?? null;
                    if ($positionName) $paths[] = 'photos/' . $positionName . '/' . $img->name;
                    $paths[] = 'photos/' . $img->name;
                    $paths[] = $img->name;
                    $localExists = false;
                    foreach ($paths as $p) {
                        if (Storage::disk('public')->exists($p)) { $localExists = true; break; }
                    }
                    // Check HRMIS by civil servant ID
                    $hrmisUrl = $hrmisBase ? $hrmisBase . '/' . rawurlencode($cs->id) : null;
                    $hrmisOk = null;
                    if ($hrmisUrl) {
                        try { $res = Http::timeout(6)->get($hrmisUrl); $hrmisOk = $res->successful() && $res->body() !== ''; } catch (\Exception $e) { $hrmisOk = false; }
                    }
                    // Check PHOTO_BASE_URL by image name
                    $remoteUrl = $photoBaseUrl ? $photoBaseUrl . '/' . rawurlencode($img->name) : null;
                    $remoteHead = null;
                    if ($remoteUrl) {
                        try { $res = Http::timeout(6)->get($remoteUrl); $remoteHead = $res->successful(); } catch (\Exception $e) { $remoteHead = false; }
                    }
                    $debug['items'][] = [
                        'civilServantId' => $cs->id,
                        'name' => $cs->last_name_kh . ' ' . $cs->first_name_kh,
                        'image' => $img->name,
                        'localExists' => $localExists,
                        'hrmisUrl' => $hrmisUrl,
                        'hrmisAccessible' => $hrmisOk,
                        'remoteUrl' => $remoteUrl,
                        'remoteAccessible' => $remoteHead,
                        'paths' => $paths,
                    ];
                }
            }
            return response()->json($debug);
        }

        if ($civilServants->isEmpty()) {
            return back()->with('error', 'No photos found for this department');
        }

        // Use department name for zip and inner folder
        $dept = Departments::find($departmentId);
        $deptName = ($dept && !empty($dept->name_kh)) ? $dept->name_kh : 'department_' . $departmentId;
        $zipFileName = $deptName . '.zip';
        $folderPrefix = $deptName . '/';
        // Safe filename for storage on disk
        $safeZipName = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $deptName) . '.zip';
        $zipPath = storage_path('app/temp/' . $safeZipName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create zip');
        }

        $photoBaseUrl = rtrim(env('PHOTO_BASE_URL', ''), '/');
        $hrmisBase = rtrim(env('HRMIS_PHOTO_BASE', 'https://mef-pd.net/hrmis/api/profile_image'), '/');
        $tempFiles = [];
        $addedFiles = 0;
        foreach ($civilServants as $civilServant) {
            $image = $civilServant->images->first();
            if (!$image) continue;

            $baseName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh;
            $entryName = $baseName . '_' . $image->name;
            $added = false;

            // Try local storage first (position subfolder first, then flat)
            $positionName = $civilServant->position->name_kh ?? null;
            $possiblePaths = [];
            if ($positionName) {
                $possiblePaths[] = 'photos/' . $positionName . '/' . $image->name;
            }
            $possiblePaths[] = 'photos/' . $image->name;
            $possiblePaths[] = $image->name;
            foreach ($possiblePaths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    $zip->addFile(Storage::disk('public')->path($path), $folderPrefix . $entryName);
                    $addedFiles++;
                    $added = true;
                    break;
                }
            }

            // Try HRMIS endpoint by civil servant ID (same as individual download)
            if (!$added && $hrmisBase) {
                try {
                    $remote = Http::timeout(10)->get($hrmisBase . '/' . rawurlencode($civilServant->id));
                } catch (\Exception $e) {
                    $remote = null;
                }

                if ($remote && $remote->successful() && $remote->body() !== '') {
                    // Determine file extension from Content-Type
                    $ct = $remote->header('Content-Type', 'image/jpeg');
                    $ext = match (true) {
                        str_contains($ct, 'png')  => '.png',
                        str_contains($ct, 'gif')  => '.gif',
                        str_contains($ct, 'webp') => '.webp',
                        default                    => '.jpg',
                    };
                    $zipEntryName = $baseName . $ext;

                    $tempFile = tempnam(sys_get_temp_dir(), 'dept_photo_');
                    file_put_contents($tempFile, $remote->body());
                    $zip->addFile($tempFile, $folderPrefix . $zipEntryName);
                    $tempFiles[] = $tempFile;
                    $addedFiles++;
                    $added = true;
                }
            }

            // Fallback: try PHOTO_BASE_URL with image filename
            if (!$added && $photoBaseUrl) {
                try {
                    $remote = Http::timeout(10)->get($photoBaseUrl . '/' . rawurlencode($image->name));
                } catch (\Exception $e) {
                    $remote = null;
                }

                if ($remote && $remote->successful() && $remote->body() !== '') {
                    $tempFile = tempnam(sys_get_temp_dir(), 'dept_photo_');
                    file_put_contents($tempFile, $remote->body());
                    $zip->addFile($tempFile, $folderPrefix . $entryName);
                    $tempFiles[] = $tempFile;
                    $addedFiles++;
                }
            }
        }

        $zip->close();

        // Clean up temp files from remote downloads
        foreach ($tempFiles as $tempFile) {
            @unlink($tempFile);
        }

        if ($addedFiles === 0) {
            @unlink($zipPath);
            return back()->with('error', 'No photo files found on disk');
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Return JSON list of civil servants + download URLs for a department.
     * Used by the frontend File System Access API to save photos into a folder.
     */
    public function departmentPhotoList($departmentId)
    {
        $departmentId = $departmentId ?: 7;

        $dept = Departments::find($departmentId);
        $deptName = ($dept && !empty($dept->name_kh)) ? $dept->name_kh : 'department_' . $departmentId;

        $deptIds = Departments::where('id', $departmentId)
            ->orWhere('parent_id', $departmentId)
            ->pluck('id')
            ->toArray();

        $civilServants = Civil_servants::with(['images', 'position'])
            ->whereIn('department_id', $deptIds)
            ->whereHas('images')
            ->get();

        $items = [];
        foreach ($civilServants as $cs) {
            $image = $cs->images->first();
            if (!$image) continue;
            $items[] = [
                'id'          => $cs->id,
                'name'        => $cs->last_name_kh . ' ' . $cs->first_name_kh,
                'downloadUrl' => '/civil-servants/download-photo/' . $cs->id,
            ];
        }

        return response()->json([
            'folderName' => $deptName,
            'items'      => $items,
        ]);
    }
}
