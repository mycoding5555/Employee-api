<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departments;  

class DepartmentController extends Controller
{
    // GET /api/departments
    public function index()
    {
        return response()->json(Departments::all());
    }
}
