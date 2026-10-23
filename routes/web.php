<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('goals/{goal}', [GoalController::class, 'show'])->name('goals.show');
});

require __DIR__.'/settings.php';
