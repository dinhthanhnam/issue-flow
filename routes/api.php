<?php

use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\WorkspaceMemberController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WorkspaceController;
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
        ->name('workspaces.update');
    //    Route::delete('workspaces/{workspace}', [WorkspaceController::class, 'destroy'])
    //        ->name('workspaces.destroy');

    // Workspace archive / restore
    Route::post('workspaces/{workspace}/archive', [WorkspaceController::class, 'archive'])
        ->name('workspaces.archive');
    Route::post('workspaces/{workspace}/restore', [WorkspaceController::class, 'restore'])
        ->name('workspaces.restore');

    // Workspace members api routes
    Route::get('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'index'])
        ->name('workspaces.members.index');
    Route::post('workspaces/{workspace}/members', [WorkspaceMemberController::class, 'store'])
        ->name('workspaces.members.store');
    Route::patch('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'update'])
        ->name('workspaces.members.update');
    Route::delete('workspaces/{workspace}/members/{user}', [WorkspaceMemberController::class, 'destroy'])
        ->name('workspaces.members.destroy');
    // Project api routes
    Route::get('projects/{project}', [ProjectController::class, 'show'])
        ->name('projects.show');
    Route::patch('projects/{project}', [ProjectController::class, 'update'])
        ->name('projects.update');
    Route::get('workspaces/{workspace}/projects', [ProjectController::class, 'index'])
        ->name('workspaces.projects.index');
    Route::post('workspaces/{workspace}/projects', [ProjectController::class, 'store'])
        ->name('workspaces.projects.store');

    // Project members api routes
    Route::get('projects/{project}/members', [ProjectMemberController::class, 'index'])
        ->name('projects.members.index');
    Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])
        ->name('projects.members.store');
    Route::patch('projects/{project}/members/{user}', [ProjectMemberController::class, 'update'])
        ->name('projects.members.update');
    Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])
        ->name('projects.members.destroy');

    // Issues routes
    Route::get('projects/{project}/issues', [IssueController::class, 'index'])
        ->name('projects.issues.index');

    Route::post('projects/{project}/issues', [IssueController::class, 'store'])
        ->middleware(['project.workspace.active'])
        ->name('projects.issues.store');

    Route::patch('issues/{issue}', [IssueController::class, 'update'])
        ->name('issues.update');

    // Comments routes
    Route::get('issues/{issue}/comments', [CommentController::class, 'index'])
        ->name('issues.comments.index');

    Route::post('issues/{issue}/comments', [CommentController::class, 'store'])
        ->name('issues.comments.store');
});
