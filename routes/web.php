<?php

use App\Http\Controllers\CivilServantWebController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

Route::get('/civil-servants/index', [CivilServantWebController::class, 'index'])->name('civil-servants.index');
Route::get('/civil-servants/search', [CivilServantWebController::class, 'search'])->name('civil-servants.search');
Route::get('/civil-servants/ajax-search', [CivilServantWebController::class, 'ajaxSearch'])->name('civil-servants.ajax-search');
Route::get('/civil-servants/photo/{civil_servant_id}', [CivilServantWebController::class, 'showPhoto'])->name('civil-servants.show-photo');
Route::get('/civil-servants/hrmis-photo/{id}', [CivilServantWebController::class, 'proxyHrmisPhotoById'])->name('civil-servants.hrmis-photo');
Route::get('/civil-servants/download-photo/{civil_servant_id}', [CivilServantWebController::class, 'downloadPhoto'])->name('civil-servants.download-photo');
Route::get('/civil-servants/download-department/{department_id}', [CivilServantWebController::class, 'downloadDepartment'])->name('civil-servants.download-department');
Route::get('/civil-servants/department-photo-list/{department_id}', [CivilServantWebController::class, 'departmentPhotoList'])->name('civil-servants.department-photo-list');
