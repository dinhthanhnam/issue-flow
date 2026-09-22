<?php

namespace App\Http\Requests;

use App\Enums\ProjectRole;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()?->can('manageMembers', $project) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        $allowedRoles = $project->isWorkspaceManager($this->user())
            ? ProjectRole::cases()
            : [ProjectRole::Member, ProjectRole::Viewer];

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('user_workspace', 'user_id')
                    ->where('workspace_id', $project->workspace_id),
                Rule::unique('project_user', 'user_id')
                    ->where('project_id', $project->id),
            ],
            'role' => [
                'required',
                Rule::enum(ProjectRole::class)->only($allowedRoles),
            ],
        ];
    }
}
