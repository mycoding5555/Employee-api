<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class EmployeeController extends Controller
{
    // GET /api/employees?name=&department_id=
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'title']);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        return response()->json($query->get());
    }

    // GET /api/employees/{id}/photo  — download single photo
    public function downloadPhoto(Employee $employee)
    {
        if (!$employee->photo || !Storage::disk('public')->exists($employee->photo)) {
            return response()->json(['message' => 'Photo not found'], 404);
        }

        return Storage::disk('public')->download($employee->photo, $employee->name . '.jpg');
    }

    // GET /api/employees/download-by-department/{department_id}  — zip all photos in a department
    public function downloadByDepartment($departmentId)
    {
        $employees = Employee::where('department_id', $departmentId)
            ->whereNotNull('photo')
            ->get();

        if ($employees->isEmpty()) {
            return response()->json(['message' => 'No photos found for this department'], 404);
        }

        $zipFileName = 'department_' . $departmentId . '_photos.zip';
        $zipPath = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'Could not create zip'], 500);
        }

        foreach ($employees as $employee) {
            $filePath = Storage::disk('public')->path($employee->photo);
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $employee->name . '_' . $employee->id . '.jpg');
            }
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    // POST /api/employees  — create employee with photo upload
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'sex'           => 'required|string|in:Male,Female',
            'department_id' => 'required|exists:departments,id',
            'photo'         => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('photos', 'public');
        }

        $employee = Employee::create($validated);

        return response()->json($employee->load('department'), 201);
    }
}
