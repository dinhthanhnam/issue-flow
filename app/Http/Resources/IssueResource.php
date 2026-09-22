<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'priority' => $this->priority->value,

            'project' => [
                'id' => $this->project->id,
                'key' => $this->project->key,
            ],

            'reporter' => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
            ],

            'assignee' => $this->assignee ? [
                'id' => $this->assignee->id,
                'name' => $this->assignee->name,
            ] : null,

            'labels' => LabelResource::collection($this->whenLoaded('labels')),

            'created_at' => $this->created_at,
        ];
    }
}
