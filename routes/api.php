<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    // Workspace api routes
    Route::apiResource('workspaces', WorkspaceController::class)
        ->only(['index', 'store', 'show', 'update']);
    Route::post('workspaces/{workspace}/archive', [WorkspaceController::class, 'archive'])
        ->name('workspaces.archive');
    Route::post('workspaces/{workspace}/restore', [WorkspaceController::class, 'restore'])
        ->name('workspaces.restore');

    // Issues routes
    Route::get('/projects/{project}/issues', [IssueController::class, 'index'])
        ->name('projects.issues.index');

    Route::post('/projects/{project}/issues', [IssueController::class, 'store'])
        ->middleware(['workspace.active'])
        ->name('projects.issues.store');

    Route::patch('/issues/{issue}', [IssueController::class, 'update'])
        ->name('issues.update');

    // Comments routes
    Route::get('/issues/{issue}/comments', [CommentController::class, 'index'])
        ->name('issues.comments.index');

    Route::post('/issues/{issue}/comments', [CommentController::class, 'store'])
        ->name('issues.comments.store');
});
