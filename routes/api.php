<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\IssueLabelController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceMemberController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

    // Workspace api routes
    Route::get('workspaces', [WorkspaceController::class, 'index'])
        ->name('workspaces.index');
    Route::post('workspaces', [WorkspaceController::class, 'store'])
        ->name('workspaces.store');
    Route::get('workspaces/{workspace}', [WorkspaceController::class, 'show'])
        ->name('workspaces.show');
    Route::patch('workspaces/{workspace}', [WorkspaceController::class, 'update'])
        ->middleware('workspace.active')
        ->name('workspaces.update');

    // Workspace archive / restore
    Route::post('workspaces/{workspace}/archive', [WorkspaceController::class, 'archive'])
        ->middleware('workspace.active')
        ->name('workspaces.archive');
    Route::post('workspaces/{workspace}/restore', [WorkspaceController::class, 'restore'])
        ->name('workspaces.restore');

    // Workspace members api routes
    Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index'])
        ->name('workspaces.members.index');
    Route::post('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store'])
        ->middleware('workspace.active')
        ->name('workspaces.members.store');
    Route::patch('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'update'])
        ->middleware('workspace.active')
        ->name('workspaces.members.update');
    Route::delete('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy'])
        ->middleware('workspace.active')
        ->name('workspaces.members.destroy');
    // Project api routes
    Route::get('projects/{project}', [ProjectController::class, 'show'])
        ->name('projects.show');
    Route::patch('projects/{project}', [ProjectController::class, 'update'])
        ->middleware('workspace.active')
        ->name('projects.update');
    Route::get('workspaces/{workspace}/projects', [ProjectController::class, 'index'])
        ->name('workspaces.projects.index');
    Route::post('workspaces/{workspace}/projects', [ProjectController::class, 'store'])
        ->middleware('workspace.active')
        ->name('workspaces.projects.store');

    // Project members api routes
    Route::get('projects/{project}/members', [ProjectMemberController::class, 'index'])
        ->name('projects.members.index');
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])
        ->middleware('workspace.active')
        ->name('projects.members.store');
    Route::patch('projects/{project}/members/{user}', [ProjectMemberController::class, 'update'])
        ->middleware('workspace.active')
        ->name('projects.members.update');
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])
        ->middleware('workspace.active')
        ->name('projects.members.destroy');

    // Label routes
    Route::get('workspaces/{workspace}/labels', [LabelController::class, 'index'])
        ->name('workspaces.labels.index');
    Route::post('workspaces/{workspace}/labels', [LabelController::class, 'store'])
        ->middleware('workspace.active')
        ->name('workspaces.labels.store');
    Route::patch('labels/{label}', [LabelController::class, 'update'])
        ->middleware('workspace.active')
        ->name('labels.update');
    Route::delete('labels/{label}', [LabelController::class, 'destroy'])
        ->middleware('workspace.active')
        ->name('labels.destroy');

    Route::post('issues/{issue}/labels/{label}', [IssueLabelController::class, 'store'])
        ->middleware('workspace.active')
        ->name('issues.labels.store');
    Route::delete('issues/{issue}/labels/{label}', [IssueLabelController::class, 'destroy'])
        ->middleware('workspace.active')
        ->name('issues.labels.destroy');

    // Issues routes
    Route::get('projects/{project}/issues', [IssueController::class, 'index'])
        ->name('projects.issues.index');

    Route::post('projects/{project}/issues', [IssueController::class, 'store'])
        ->middleware('workspace.active')
        ->name('projects.issues.store');

    Route::patch('issues/{issue}', [IssueController::class, 'update'])
        ->middleware('workspace.active')
        ->name('issues.update');

    // Comments routes
    Route::get('issues/{issue}/comments', [CommentController::class, 'index'])
        ->name('issues.comments.index');

    Route::post('issues/{issue}/comments', [CommentController::class, 'store'])
        ->middleware('workspace.active')
        ->name('issues.comments.store');
});
