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

// Civil servant listing & search
Route::get('/civil-servants/index', [CivilServantController::class, 'index'])->name('civil-servants.index');
Route::get('/civil-servants/search', [CivilServantController::class, 'search'])->name('civil-servants.search');
Route::get('/civil-servants/ajax-search', [CivilServantController::class, 'ajaxSearch'])->name('civil-servants.ajax-search');

// Individual photo operations
Route::get('/civil-servants/photo/{civil_servant_id}', [CivilServantPhotoController::class, 'showPhoto'])->name('civil-servants.show-photo');
Route::get('/civil-servants/hrmis-photo/{id}', [CivilServantPhotoController::class, 'proxyHrmisPhotoById'])->name('civil-servants.hrmis-photo');
Route::get('/civil-servants/download-photo/{civil_servant_id}', [CivilServantPhotoController::class, 'downloadPhoto'])->name('civil-servants.download-photo');

// Department-level operations
Route::get('/civil-servants/download-department/{department_id}', [DepartmentController::class, 'downloadDepartment'])->name('civil-servants.download-department');
Route::get('/civil-servants/department-photo-list/{department_id}', [DepartmentController::class, 'departmentPhotoList'])->name('civil-servants.department-photo-list');

// Load Debugbar routes if the package is available and enabled (ensures assets and handlers are registered)
if (file_exists(base_path('vendor/barryvdh/laravel-debugbar/src/debugbar-routes.php')) && config('debugbar.enabled')) {
    require base_path('vendor/barryvdh/laravel-debugbar/src/debugbar-routes.php');
}

// Fallback: explicitly register essential Debugbar routes in case the package didn't register them
if (class_exists(\Fruitcake\LaravelDebugbar\Controllers\AssetController::class) && config('debugbar.enabled')) {
    Route::prefix(config('debugbar.route_prefix', '_debugbar'))->group(function () {
        Route::get('open', [\Fruitcake\LaravelDebugbar\Controllers\OpenHandlerController::class, 'handle'])->name('debugbar.openhandler');
        Route::delete('cache/{key}', [\Fruitcake\LaravelDebugbar\Controllers\CacheController::class, 'delete'])->where('key', '.*')->name('debugbar.cache.delete');
        Route::post('queries/explain', [\Fruitcake\LaravelDebugbar\Controllers\QueriesController::class, 'explain'])->name('debugbar.queries.explain');
        Route::get('clockwork/{id}', [\Fruitcake\LaravelDebugbar\Controllers\OpenHandlerController::class, 'clockwork'])->name('debugbar.clockwork');
        Route::get('assets', [\Fruitcake\LaravelDebugbar\Controllers\AssetController::class, 'getAssets'])->name('debugbar.assets');
    });
}
