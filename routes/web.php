<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\IssueController;
use App\Http\Middleware\EnsureWorkspaceIsActive;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::post('/projects/{project}/issues', [IssueController::class, 'store'])->middleware('auth', EnsureWorkspaceIsActive::class)->name('projects.issues.store');
Route::get('/projects/{project}/issues', [IssueController::class, 'index'])->middleware('auth')->name('projects.issues.index');
Route::patch('/issues/{issue}', [IssueController::class, 'update'])
    ->middleware('auth')
    ->name('issues.update');
Route::get('/issues/{issue}/comments', [CommentController::class, 'index'])->middleware('auth')->name('issues.comments.index');
Route::post('/issues/{issue}/comments', [CommentController::class, 'store'])->middleware('auth')->name('issues.comments.store');

require __DIR__.'/settings.php';
