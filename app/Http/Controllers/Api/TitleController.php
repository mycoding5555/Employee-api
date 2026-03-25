<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Title;

class TitleController extends Controller
{
    // GET /api/titles
    public function index()
    {
        return response()->json(Title::all());
    }
}
