<?php

namespace App\Http\Controllers;

use App\Models\Civil_servants;
use App\Models\Departments;
use App\Models\Positions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            return redirect($photoBaseUrl . '/' . $image->name);
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

        abort(404);
    }

    public function downloadPhoto($civilServantId)
    {
        $civilServant = Civil_servants::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $civilServant->images->first();

        if (!$image) {
            abort(404, 'Photo not found');
        }

        $downloadName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh . '_' . $image->name;

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
                $filePath = Storage::disk('public')->path($path);
                return response()->download($filePath, $downloadName);
            }
        }

        // If remote photo URL is configured, stream download from there
        $photoBaseUrl = rtrim(env('PHOTO_BASE_URL', ''), '/');
        if ($photoBaseUrl) {
            $remoteUrl = $photoBaseUrl . '/' . $image->name;
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
        $deptIds = Departments::where('id', $departmentId)
            ->orWhere('parent_id', $departmentId)
            ->pluck('id')
            ->toArray();

        $civilServants = Civil_servants::with(['images', 'position'])
            ->whereIn('department_id', $deptIds)
            ->whereHas('images')
            ->get();

        if ($civilServants->isEmpty()) {
            return back()->with('error', 'No photos found for this department');
        }

        $zipFileName = 'department_' . $departmentId . '_photos.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create zip');
        }

        $photoBaseUrl = rtrim(env('PHOTO_BASE_URL', ''), '/');
        $tempFiles = [];
        $addedFiles = 0;
        foreach ($civilServants as $civilServant) {
            foreach ($civilServant->images as $image) {
                $entryName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh . '_' . $image->name;
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
                        $zip->addFile(Storage::disk('public')->path($path), $entryName);
                        $addedFiles++;
                        $added = true;
                        break;
                    }
                }

                // Try remote if local not found
                if (!$added && $photoBaseUrl) {
                    $contents = @file_get_contents($photoBaseUrl . '/' . $image->name);
                    if ($contents !== false) {
                        $tempFile = tempnam(sys_get_temp_dir(), 'dept_photo_');
                        file_put_contents($tempFile, $contents);
                        $zip->addFile($tempFile, $entryName);
                        $tempFiles[] = $tempFile;
                        $addedFiles++;
                    }
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
}
