<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Issue;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function index(Issue $issue): JsonResponse
    {
        Gate::authorize('view', $issue);

        return CommentResource::collection(
            $issue->comments()
                ->with('user')
                ->oldest()
                ->get()
        )->response();
    }

    public function store(
        StoreCommentRequest $request,
        Issue $issue,
    ): JsonResponse {
        $data = $request->validated();
        $comment = $issue->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $comment->load('user');

        return new CommentResource($comment)
            ->response()
            ->setStatusCode(201);
    }
}
