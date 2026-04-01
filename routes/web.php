<?php

use App\Http\Controllers\CivilServantPhotoController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

Route::get('/civil-servants/index', [CivilServantPhotoController::class, 'index'])->name('civil-servants.index');
Route::get('/civil-servants/search', [CivilServantPhotoController::class, 'search'])->name('civil-servants.search');
Route::get('/civil-servants/ajax-search', [CivilServantPhotoController::class, 'ajaxSearch'])->name('civil-servants.ajax-search');
Route::get('/civil-servants/photo/{civil_servant_id}', [CivilServantPhotoController::class, 'showPhoto'])->name('civil-servants.show-photo');
Route::get('/civil-servants/hrmis-photo/{id}', [CivilServantPhotoController::class, 'proxyHrmisPhotoById'])->name('civil-servants.hrmis-photo');
Route::get('/civil-servants/download-photo/{civil_servant_id}', [CivilServantPhotoController::class, 'downloadPhoto'])->name('civil-servants.download-photo');
Route::get('/civil-servants/download-department/{department_id}', [CivilServantPhotoController::class, 'downloadDepartment'])->name('civil-servants.download-department');
Route::get('/civil-servants/department-photo-list/{department_id}', [CivilServantPhotoController::class, 'departmentPhotoList'])->name('civil-servants.department-photo-list');
