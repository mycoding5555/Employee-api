<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Civil_servants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CivilServantController extends Controller
{

    public function index(Request $request)
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
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->input('position_id'));
        }

        return response()->json($query->paginate($request->input('per_page', 20)));
    }

    public function downloadPhoto($civilServantId)
    {
        $civilServant = Civil_servants::with(['images', 'position'])->findOrFail($civilServantId);
        $image = $civilServant->images->first();

        if (!$image) {
            return response()->json(['message' => 'Photo not found'], 404);
        }

        // Position subfolder first, then flat
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
                $downloadName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh . '_' . $image->name;
                return response()->download($filePath, $downloadName);
            }
        }

        // If remote photo URL is configured, stream download from there
        $photoBaseUrl = rtrim(env('PHOTO_BASE_URL', ''), '/');
        if ($photoBaseUrl) {
            $downloadName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh . '_' . $image->name;
            $remoteUrl = $photoBaseUrl . '/' . $image->name;
            $tempPath = tempnam(sys_get_temp_dir(), 'photo_');
            $contents = @file_get_contents($remoteUrl);
            if ($contents !== false) {
                file_put_contents($tempPath, $contents);
                return response()->download($tempPath, $downloadName)->deleteFileAfterSend(true);
            }
        }

        return response()->json(['message' => 'Photo not found'], 404);
    }

    public function downloadByDepartment($departmentId)
    {
        $civilServants = Civil_servants::with(['images', 'position'])
            ->where('department_id', $departmentId)
            ->whereHas('images')
            ->get();

        if ($civilServants->isEmpty()) {
            return response()->json(['message' => 'No photos found for this department'], 404);
        }

        $zipFileName = 'department_' . $departmentId . '_photos.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'Could not create zip'], 500);
        }

        $photoBaseUrl = rtrim(env('PHOTO_BASE_URL', ''), '/');
        $tempFiles = [];
        $addedFiles = 0;
        foreach ($civilServants as $civilServant) {
            foreach ($civilServant->images as $image) {
                $entryName = $civilServant->last_name_kh . '_' . $civilServant->first_name_kh . '_' . $image->name;
                $added = false;

                // Position subfolder first, then flat
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

        foreach ($tempFiles as $tempFile) {
            @unlink($tempFile);
        }

        if ($addedFiles === 0) {
            @unlink($zipPath);
            return response()->json(['message' => 'No photo files found on disk'], 404);
        }

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entity_id'        => 'required|string|max:36',
            'title_id'         => 'nullable|integer',
            'entity_type'      => 'required|integer',
            'org_type'         => 'nullable|integer',
            'org_code'         => 'nullable|string|max:4',
            'mef_code'         => 'nullable|string|max:6',
            'gov_code'         => 'nullable|string|max:12',
            'last_name_kh'     => 'required|string|max:100',
            'first_name_kh'    => 'required|string|max:100',
            'last_name_en'     => 'nullable|string|max:20',
            'first_name_en'    => 'nullable|string|max:50',
            'dob'              => 'nullable|date',
            'gender_id'        => 'required|integer',
            'department_id'    => 'nullable|exists:departments,id',
            'position_id'      => 'nullable|integer',
            'equal_position_id'=> 'nullable|integer',
            'base_salary_id'   => 'nullable|integer',
            'status_type_id'   => 'required|integer',
            'status_type_date' => 'nullable|date',
            'degree_id'        => 'nullable|integer',
            'marital_status_id'=> 'nullable|integer',
            'sort'             => 'nullable|integer',
        ]);

        $civilServant = Civil_servants::create($validated);

        return response()->json($civilServant->load(['department', 'position']), 201);
    }
}
