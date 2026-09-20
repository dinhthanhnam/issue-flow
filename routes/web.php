<?php

use App\Http\Controllers\IssueController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::post('/projects/{project}/issues', [IssueController::class, 'store'])->middleware('auth')->name('projects.issues.store');
Route::get('/projects/{project}/issues', [IssueController::class, 'index'])->middleware('auth')->name('projects.issues.index');
Route::patch('/issues/{issue}', [IssueController::class, 'update'])
    ->middleware('auth')
    ->name('issues.update');

require __DIR__.'/settings.php';
