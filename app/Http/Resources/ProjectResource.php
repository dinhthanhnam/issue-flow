<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'workspace_id' => $this->workspace_id,
            'name' => $this->name,
            'key' => $this->key,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'can_create_issue' => $request->user()?->can('createIssue', $this->resource) ?? false,
            'can_manage_members' => $request->user()?->can('manageMembers', $this->resource) ?? false,
        ];
    }
}
