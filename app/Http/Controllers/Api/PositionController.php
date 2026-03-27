<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Positions;

class PositionController extends Controller
{
    // GET /api/positions
    public function index()
    {
        return response()->json(Positions::all());
    }
}
