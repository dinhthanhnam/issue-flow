<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');
        $targetUser = $this->route('user');

        if (! $this->user()->can('manageMembers', $workspace)) {
            return false;
        }

        if ($workspace->hasRole($this->user(), WorkspaceRole::Owner)) {
            return true;
        }

        return $workspace->hasRole($targetUser, WorkspaceRole::Member);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = $this->route('workspace');
        $allowedRoles = $workspace->hasRole($this->user(), WorkspaceRole::Owner)
            ? [WorkspaceRole::Admin, WorkspaceRole::Member]
            : [WorkspaceRole::Member];

        return [
            'role' => [
                'required',
                Rule::enum(WorkspaceRole::class)->only($allowedRoles),
            ],
        ];
    }
}
