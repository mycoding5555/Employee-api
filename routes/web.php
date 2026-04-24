<?php

use App\Http\Controllers\CivilservantIdController;
use App\Http\Controllers\CivilServantController;
use App\Http\Controllers\CivilServantPhotoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

// Civil servant listing & search
Route::get('/civil-servants/index', [CivilServantController::class, 'index'])->name('civil-servants.index');
Route::get('/civil-servants/search', [CivilServantController::class, 'search'])->name('civil-servants.search');
Route::get('/civil-servants/ajax-search', [CivilServantController::class, 'ajaxSearch'])->middleware('throttle:60,1')->name('civil-servants.ajax-search');

// Individual photo operations
Route::get('/civil-servants/photo/{civil_servant_id}', [CivilServantPhotoController::class, 'showPhoto'])->name('civil-servants.show-photo');
Route::get('/civil-servants/hrmis-photo/{id}', [CivilServantPhotoController::class, 'proxyHrmisPhotoById'])->name('civil-servants.hrmis-photo');
Route::get('/civil-servants/download-photo/{civil_servant_id}', [CivilServantPhotoController::class, 'downloadPhoto'])->name('civil-servants.download-photo');

// Department-level operations
Route::get('/civil-servants/download-department/{department_id}', [DepartmentController::class, 'downloadDepartment'])->name('civil-servants.download-department');
Route::get('/civil-servants/department-photo-list/{department_id}', [DepartmentController::class, 'departmentPhotoList'])->name('civil-servants.department-photo-list');

// Civil servant ID documents (អត្តសញ្ញណប័ណ្ណ)
Route::get('/civilservant-id', [CivilservantIdController::class, 'index'])->name('civilservant-id.index');
Route::get('/civilservant-id/ajax-search', [CivilservantIdController::class, 'ajaxSearch'])->middleware('throttle:60,1')->name('civilservant-id.ajax-search');
Route::get('/civilservant-id/download-pdf', [CivilservantIdController::class, 'downloadPdf'])->name('civilservant-id.download-pdf');
Route::get('/civilservant-id/download/{id}', [CivilservantIdController::class, 'downloadSinglePdf'])->name('civilservant-id.download-single');
Route::get('/civilservant-id/{id}/download-delta-doc', [CivilservantIdController::class, 'downloadDeltaDocPdf'])->name('civilservant-id.download-delta-doc');
Route::get('/civilservant-id/{id}/download-id-card-doc', [CivilservantIdController::class, 'downloadIdCardDocPdf'])->name('civilservant-id.download-id-card-doc');
// Department-level ID card downloads
Route::get('/civilservant-id/download-department/{department_id}', [CivilservantIdController::class, 'downloadDepartment'])->name('civilservant-id.download-department');

// Debugbar registers its own routes via its service provider; no manual include needed.
