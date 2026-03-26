<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/app/{app}', [DashboardController::class, 'show'])->name('app.show');
Route::get('/export/csv', [DashboardController::class, 'exportCsv'])->name('export.csv');
Route::post('/app/{app}/folder', [DashboardController::class, 'setFolder'])->name('app.folder');
