<?php

use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Support\Facades\Route;

// Departments
Route::get('/departments', [DepartmentController::class, 'index']);

// Employees
Route::get('/employees', [EmployeeController::class, 'index']);
Route::post('/employees', [EmployeeController::class, 'store']);
Route::get('/employees/{employee}/photo', [EmployeeController::class, 'downloadPhoto']);
Route::get('/employees/download-by-department/{department_id}', [EmployeeController::class, 'downloadByDepartment']);
