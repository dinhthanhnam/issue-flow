<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLabelRequest;
use App\Http\Requests\UpdateLabelRequest;
use App\Http\Resources\LabelResource;
use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class LabelController extends Controller
{
    public function index(Workspace $workspace): JsonResponse
    {
        Gate::authorize('viewAny', [Label::class, $workspace]);

        $labels = $workspace->labels()
            ->orderBy('name')
            ->get();

        return LabelResource::collection($labels)->response();
    }

    public function store(
        StoreLabelRequest $request,
        Workspace $workspace,
    ): JsonResponse {
        $label = Label::query()->create([
            ...$request->validated(),
            'workspace_id' => $workspace->id,
        ]);

        return new LabelResource($label)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateLabelRequest $request,
        Label $label,
    ): JsonResponse {
        $label->update($request->validated());

        return new LabelResource($label)->response();
    }

    public function destroy(Label $label): JsonResponse
    {
        Gate::authorize('delete', $label);

        $label->delete();

        return response()->json(status: 204);
    }
}
