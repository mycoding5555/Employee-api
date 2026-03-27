<?php

use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\CivilServantController;
use App\Http\Controllers\Api\PositionController;
use Illuminate\Support\Facades\Route;

// Departments
Route::get('/departments', [DepartmentController::class, 'index']);

// Positions
Route::get('/positions', [PositionController::class, 'index']);

// Civil Servants
Route::get('/civil-servants', [CivilServantController::class, 'index']);
Route::post('/civil-servants', [CivilServantController::class, 'store']);
Route::get('/civil-servants/{civil_servant}/download-photo', [CivilServantController::class, 'downloadPhoto']);
Route::get('/civil-servants/download-by-department/{department_id}', [CivilServantController::class, 'downloadByDepartment']);
