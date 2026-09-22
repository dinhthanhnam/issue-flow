<?php

namespace App\Http\Requests;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        /** @var User $target */
        $target = $this->route('user');

        return $this->user()?->can('manageMember', [$project, $target]) ?? false;
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
            'role' => [
                'required',
                Rule::enum(ProjectRole::class)->only($allowedRoles),
            ],
        ];
    }
}
