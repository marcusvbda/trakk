<?php

declare(strict_types=1);

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CompletionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\ReminderActiveController;
use App\Http\Controllers\ReplanController;
use App\Http\Controllers\TaskStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('goals/{goal}', [GoalController::class, 'show'])->name('goals.show');

    Route::post('tasks/{task}/completions', [CompletionController::class, 'store'])->name('tasks.completions.store');
    Route::delete('tasks/{task}/completions/today', [CompletionController::class, 'destroy'])->name('tasks.completions.destroy');
    Route::patch('tasks/{task}/status', [TaskStatusController::class, 'update'])->name('tasks.status.update');
    Route::patch('reminders/{reminder}/active', [ReminderActiveController::class, 'update'])->name('reminders.active.update');
    Route::post('replan', [ReplanController::class, 'store'])->name('replan.store');
    Route::post('check-in', [CheckInController::class, 'store'])->name('check-in.store');
});

require __DIR__.'/settings.php';
