<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class IssueLabelController extends Controller
{
    public function store(Issue $issue, Label $label): JsonResponse
    {
        Gate::authorize('manageLabel', [$issue, $label]);

        $issue->labels()->syncWithoutDetaching([$label->id]);

        return response()->json(status: 204);
    }

    public function destroy(Issue $issue, Label $label): JsonResponse
    {
        Gate::authorize('manageLabel', [$issue, $label]);

        $issue->labels()->detach($label);

        return response()->json(status: 204);
    }
}
