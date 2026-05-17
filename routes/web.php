<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('tasks', TaskController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('projects', ProjectController::class)
        ->only(['store', 'update']);

    Route::patch('tasks/{task}/start', [TaskController::class, 'start'])
        ->name('tasks.start');

    Route::patch('tasks/{task}/title', [TaskController::class, 'updateTitle'])
        ->name('tasks.updateTitle');

    Route::patch('tasks/{task}/due-date', [TaskController::class, 'updateDueDate'])
        ->name('tasks.updateDueDate');

    Route::patch('tasks/{task}/priority', [TaskController::class, 'updatePriority'])
        ->name('tasks.updatePriority');

    Route::post('tasks/{task}/restore', [TaskController::class, 'restore'])
        ->name('tasks.restore');

    Route::delete('tasks/{task}/force', [TaskController::class, 'forceDestroy'])
        ->name('tasks.forceDestroy');
});

require __DIR__.'/settings.php';
