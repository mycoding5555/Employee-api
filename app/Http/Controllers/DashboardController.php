<?php

namespace App\Http\Controllers;

use App\Models\CivilServant;
use App\Models\Department;
use App\Models\Position;

class DashboardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $totalStaff = CivilServant::count();
        $totalDepartments = Department::where('id', 7)->orWhere('parent_id', 7)->count();
        $totalPositions = Position::whereHas('civilServants')->count();

        $maleCount = CivilServant::where('gender_id', 1)->count();
        $femaleCount = CivilServant::where('gender_id', 2)->count();

        $topDepartments = Department::where('id', 7)->orWhere('parent_id', 7)
            ->withCount('civilServants')
            ->orderByDesc('civil_servants_count')
            ->limit(8)
            ->get();

        $topPositions = Position::withCount('civilServants')
            ->having('civil_servants_count', '>', 0)
            ->orderByDesc('civil_servants_count')
            ->limit(6)
            ->get();

        $recentEmployees = CivilServant::with(['department', 'position'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $withPhoto = CivilServant::whereHas('images')->count();
        $withoutPhoto = $totalStaff - $withPhoto;

        $chartData = [
            'deptLabels' => $topDepartments->map(fn ($d) => $d->name_short ?? $d->abbreviation ?? $d->name_kh)->values(),
            'deptValues' => $topDepartments->pluck('civil_servants_count')->values(),
            'posLabels' => $topPositions->map(fn ($p) => $p->name_short ?? $p->name_kh)->values(),
            'posValues' => $topPositions->pluck('civil_servants_count')->values(),
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
