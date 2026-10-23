<?php

declare(strict_types=1);

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\CompletionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\GoalStatusController;
use App\Http\Controllers\ReminderActiveController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReplanController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('goals', [GoalController::class, 'index'])->name('goals.index');
    Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
    Route::get('goals/{goal:slug}', [GoalController::class, 'show'])->name('goals.show');
    Route::patch('goals/{goal:slug}', [GoalController::class, 'update'])->name('goals.update');
    Route::delete('goals/{goal:slug}', [GoalController::class, 'destroy'])->name('goals.destroy');
    Route::patch('goals/{goal:slug}/status', [GoalStatusController::class, 'update'])->name('goals.status.update');

    Route::post('goals/{goal:slug}/tasks', [TaskController::class, 'store'])->name('goals.tasks.store');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('tasks/{task}/checklist', [ChecklistItemController::class, 'store'])->name('tasks.checklist.store');
    Route::patch('checklist/{item}', [ChecklistItemController::class, 'update'])->name('checklist.update');
    Route::delete('checklist/{item}', [ChecklistItemController::class, 'destroy'])->name('checklist.destroy');

    Route::post('tasks/{task}/completions', [CompletionController::class, 'store'])->name('tasks.completions.store');
    Route::delete('tasks/{task}/completions/today', [CompletionController::class, 'destroy'])->name('tasks.completions.destroy');
    Route::patch('tasks/{task}/status', [TaskStatusController::class, 'update'])->name('tasks.status.update');
    Route::post('reminders', [ReminderController::class, 'store'])->name('reminders.store');
    Route::patch('reminders/{reminder}', [ReminderController::class, 'update'])->name('reminders.update');
    Route::delete('reminders/{reminder}', [ReminderController::class, 'destroy'])->name('reminders.destroy');
    Route::patch('reminders/{reminder}/active', [ReminderActiveController::class, 'update'])->name('reminders.active.update');
    Route::post('replan', [ReplanController::class, 'store'])->name('replan.store');
    Route::post('check-in', [CheckInController::class, 'store'])->name('check-in.store');
});

require __DIR__.'/settings.php';
