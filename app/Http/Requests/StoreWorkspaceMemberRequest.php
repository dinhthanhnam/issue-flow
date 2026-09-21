<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $this->user()->can('manageMembers', $workspace);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('user_workspace', 'user_id')
                    ->where('workspace_id', $workspace->id),
            ],
            'role' => [
                'required',
                Rule::in([
                    WorkspaceRole::Admin->value,
                    WorkspaceRole::Member->value,
                ]),
            ],
        ];
    }
}
