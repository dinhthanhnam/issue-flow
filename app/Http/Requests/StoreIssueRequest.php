<?php

namespace App\Http\Requests;

use App\Enums\IssuePriority;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');
        return $this->user()?->can('createIssue', $project) ?? false;
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('user_workspace', 'user_id')
                    ->where('workspace_id', $project->workspace_id),
            ],            'priority' => [
                'sometimes',
                Rule::enum(IssuePriority::class),
            ]
        ];
    }
}
