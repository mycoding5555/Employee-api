<?php

namespace App\Http\Controllers;

use App\Models\Civilservant_Id;
use Illuminate\Http\Request;

class CivilservantIdController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $query = Civilservant_Id::query()->orderByDesc('ref_date');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('ref_number', 'like', "%{$search}%")
                  ->orWhere('ref_note', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->paginate(20)->withQueryString();

        return view('civil-servants-id.index', [
            'documents' => $documents,
            'search' => $request->input('search'),
        ]);
    }
}
