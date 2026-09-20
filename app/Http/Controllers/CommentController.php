<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Issue;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(
        StoreCommentRequest $request,
        Issue $issue,
    ): CommentResource {
        $data = $request->validated();
        $comment = $issue->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $comment->load('user');

        return new CommentResource($comment);
    }
}
