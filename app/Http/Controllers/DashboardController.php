<?php

namespace App\Http\Controllers;

use App\Models\CivilServant;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('Dashboard.index');
    }

    public function stats(): JsonResponse
    {
        $stats = CivilServant::where('status_type_id', 1)
            ->select(DB::raw("COUNT(*) as total"))
            ->selectRaw("SUM(CASE WHEN gender_id = 1 THEN 1 ELSE 0 END) as male_count")
            ->selectRaw("SUM(CASE WHEN gender_id = 2 THEN 1 ELSE 0 END) as female_count")
            ->selectRaw("SUM(CASE WHEN id IN (SELECT DISTINCT civil_servant_id FROM images) THEN 1 ELSE 0 END) as has_photo_count")
            ->first();

        $hasIdCardCount = DB::table('document_deltas')
            ->join('documents', 'documents.id', '=', 'document_deltas.document_id')
            ->where('documents.document_type_id', 10)
            ->whereIn('document_deltas.civil_servant_id', function ($q) {
                $q->select('id')->from('civil_servants')->where('status_type_id', 1);
            })
            ->distinct('document_deltas.civil_servant_id')
            ->count('document_deltas.civil_servant_id');

        $totalDepartments = Department::where('active', 1)
            ->where('parent_id', 1)
            ->count();

        $totalChildDepartments = Department::where('active', 1)
            ->where('parent_id', '!=', 1)
            ->count();

        return response()->json([
            'totalCivilServant' => (int) $stats->total,
            'maleCount' => (int) $stats->male_count,
            'femaleCount' => (int) $stats->female_count,
            'hasPhotoCount' => (int) $stats->has_photo_count,
            'noPhotoCount' => (int) $stats->total - (int) $stats->has_photo_count,
            'hasIdCardCount' => $hasIdCardCount,
            'noIdCardCount' => (int) $stats->total - $hasIdCardCount,
            'totalDepartments' => $totalDepartments,
            'totalChildDepartments' => $totalChildDepartments,
        ]);
    }
}