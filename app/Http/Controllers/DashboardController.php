<?php

namespace App\Http\Controllers;

use App\Models\CivilServant;
use App\Models\Department;

class DashboardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $totalCivilServant = CivilServant::where('status_type_id', 1)->count();
        $maleCount = CivilServant::where('status_type_id', 1)->where('gender_id', 1)->count();
        $femaleCount = CivilServant::where('status_type_id', 1)->where('gender_id', 2)->count();
        // Photo counts: those who have at least one image vs those who don't
        $hasPhotoCount = CivilServant::where('status_type_id', 1)->whereHas('images')->count();
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