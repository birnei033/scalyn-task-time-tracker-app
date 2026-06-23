<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskImportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\TimesheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('clients/archives', [ClientController::class, 'archives'])->name('clients.archives');
    Route::patch('clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::delete('clients/{client}/force', [ClientController::class, 'forceDelete'])->name('clients.force-delete');
    Route::resource('clients', ClientController::class);
    Route::resource('users', UserController::class);
    Route::get('tasks/import', [TaskImportController::class, 'create'])->name('tasks.import.create');
    Route::post('tasks/import', [TaskImportController::class, 'store'])->name('tasks.import.store');
    Route::get('tasks/import/{importToken}', [TaskImportController::class, 'show'])->name('tasks.import.show');
    Route::post('tasks/import/{importToken}', [TaskImportController::class, 'process'])->name('tasks.import.process');
    Route::resource('tasks', TaskController::class);
    Route::scopeBindings()->group(function () {
        Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('tasks.comments.store');
        Route::patch('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'update'])->name('tasks.comments.update');
        Route::delete('tasks/{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('tasks.comments.destroy');
    });
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status.update');
    Route::patch('tasks/{task}/priority', [TaskController::class, 'updatePriority'])->name('tasks.priority.update');
    Route::post('tasks/bulk-status', [TaskController::class, 'bulkStatus'])->name('tasks.bulk-status.update');
    Route::post('tasks/log-time', [TaskController::class, 'logTime'])->name('tasks.log-time');
    Route::scopeBindings()->group(function () {
        Route::get('tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'show'])->name('tasks.attachments.show');
        Route::get('tasks/{task}/attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->name('tasks.attachments.download');
        Route::delete('tasks/{task}/attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('tasks.attachments.destroy');
        Route::post('tasks/{task}/attachments/{attachment}/replace', [TaskAttachmentController::class, 'replace'])->name('tasks.attachments.replace');
        Route::post('tasks/{task}/attachments/{attachment}/versions/{version}/restore', [TaskAttachmentController::class, 'restore'])->name('tasks.attachments.versions.restore');
    });
    Route::resource('time-entries', TimeEntryController::class);
    Route::get('timesheets', [TimesheetController::class, 'index'])->name('timesheets.index');
    Route::get('timesheets/export', [TimesheetController::class, 'export'])->name('timesheets.export');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::patch('team/users/{user}', [TeamController::class, 'updateUser'])->name('team.users.update');
});

require __DIR__.'/auth.php';
