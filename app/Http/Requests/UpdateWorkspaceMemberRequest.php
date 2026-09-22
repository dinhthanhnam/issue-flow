<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use LogicException;

class UpdateWorkspaceMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->workspace();
        $targetUser = $this->targetUser();

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
        $workspace = $this->workspace();
        $targetUser = $this->targetUser();
        $allowedRoles = $workspace->hasRole($this->user(), WorkspaceRole::Owner)
            ? [WorkspaceRole::Admin, WorkspaceRole::Member]
            : [WorkspaceRole::Member];

        return [
            'role' => [
                'required',
                Rule::prohibitedIf(
                    $workspace->hasRole($targetUser, WorkspaceRole::Owner),
                ),
                Rule::enum(WorkspaceRole::class)->only($allowedRoles),
            ],
        ];
    }

    private function workspace(): Workspace
    {
        $workspace = $this->route('workspace');

        if (! $workspace instanceof Workspace) {
            throw new LogicException('The workspace route binding is missing.');
        }

        return $workspace;
    }

    private function targetUser(): User
    {
        $user = $this->route('user');

        if (! $user instanceof User) {
            throw new LogicException('The user route binding is missing.');
        }

        return $user;
    }
}
