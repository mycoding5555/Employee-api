<?php

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

// Load Debugbar routes if the package is available and enabled
if (class_exists(\Barryvdh\Debugbar\ServiceProvider::class) && config('debugbar.enabled')) {
    if (file_exists(base_path('vendor/barryvdh/laravel-debugbar/src/debugbar-routes.php'))) {
        require base_path('vendor/barryvdh/laravel-debugbar/src/debugbar-routes.php');
    }
}
