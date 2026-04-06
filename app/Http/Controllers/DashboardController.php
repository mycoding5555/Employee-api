<?php

namespace App\Http\Controllers;

use App\Models\CivilServant;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        // Single query instead of 4 separate COUNT queries
        $stats = CivilServant::where('status_type_id', 1)
            ->select(DB::raw("COUNT(*) as total"))
            ->selectRaw("SUM(CASE WHEN gender_id = 1 THEN 1 ELSE 0 END) as male_count")
            ->selectRaw("SUM(CASE WHEN gender_id = 2 THEN 1 ELSE 0 END) as female_count")
            ->selectRaw("SUM(CASE WHEN id IN (SELECT DISTINCT civil_servant_id FROM images) THEN 1 ELSE 0 END) as has_photo_count")
            ->first();

        $totalCivilServant = $stats->total;
        $maleCount = $stats->male_count;
        $femaleCount = $stats->female_count;
        $hasPhotoCount = $stats->has_photo_count;
        $noPhotoCount = $totalCivilServant - $hasPhotoCount;
        // អគ្គនាយកដ្ឋាន (parent_id = 1), ordered by sort
        $departments = Department::where('active', 1)
            ->where('parent_id', 1)
            ->orderBy('sort')
            ->get();
        $totalDepartments = $departments->count();

      // ថ្នាក់នាយកដ្ឋាន
        $childDepartments = Department::where('active', 1)
            ->where('parent_id', '!=', 1)
            ->orderBy('sort')
            ->get();
        $totalChildDepartments = $childDepartments->count();


        return view('Dashboard.index', compact(
            'totalCivilServant',
            'totalDepartments',
            'totalChildDepartments',
            'maleCount',
            'femaleCount',
            'hasPhotoCount',
            'noPhotoCount',
            'departments',
        ));
    }
}