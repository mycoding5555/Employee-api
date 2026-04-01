<?php

namespace App\Http\Controllers;

use App\Models\Civil_servants_Photo;
use App\Models\Departments;
use App\Models\Positions;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStaff = Civil_servants_Photo::count();
        $totalDepartments = Departments::where('id', 7)->orWhere('parent_id', 7)->count();
        $totalPositions = Positions::whereHas('civilServants')->count();

        $maleCount = Civil_servants_Photo::where('gender_id', 1)->count();
        $femaleCount = Civil_servants_Photo::where('gender_id', 2)->count();

        // Sub-departments under អគ្គលេខាធិការដ្ឋាន by staff count
        $topDepartments = Departments::where('id', 7)->orWhere('parent_id', 7)
            ->withCount('civilServants')
            ->orderByDesc('civil_servants_count')
            ->limit(8)
            ->get();

        // Top 6 positions by staff count (scoped via global scope)
        $topPositions = Positions::withCount('civilServants')
            ->having('civil_servants_count', '>', 0)
            ->orderByDesc('civil_servants_count')
            ->limit(6)
            ->get();

        // Recent employees (latest 5)
        $recentEmployees = Civil_servants_Photo::with(['department', 'position'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Staff with photos vs without
        $withPhoto = Civil_servants_Photo::whereHas('images')->count();
        $withoutPhoto = $totalStaff - $withPhoto;

        // Pre-build chart data arrays for JS
        $chartData = [
            'deptLabels'  => $topDepartments->map(fn($d) => $d->name_short ?? $d->abbreviation ?? $d->name_kh)->values(),
            'deptValues'  => $topDepartments->pluck('civil_servants_count')->values(),
            'posLabels'   => $topPositions->map(fn($p) => $p->name_short ?? $p->name_kh)->values(),
            'posValues'   => $topPositions->pluck('civil_servants_count')->values(),
        ];

        return view('Dashboard.index', compact(
            'totalStaff',
            'totalDepartments',
            'totalPositions',
            'maleCount',
            'femaleCount',
            'topDepartments',
            'topPositions',
            'recentEmployees',
            'withPhoto',
            'withoutPhoto',
            'chartData',
        ));
    }
}
