<?php

namespace App\Http\Requests;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\ProjectRole;
use App\Models\Issue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Issue $issue */
        $issue = $this->route('issue');

        return $this->user()?->can('update', $issue) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Issue $issue */
        $issue = $this->route('issue');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', Rule::enum(IssueStatus::class)],
            'priority' => ['sometimes', Rule::enum(IssuePriority::class)],
            'assignee_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('user_workspace', 'user_id')
                    ->where(
                        'workspace_id',
                        $issue->project->workspace_id,
                    ),
                Rule::exists('project_user', 'user_id')
                    ->where(fn (Builder $query) => $query
                        ->where('project_id', $issue->project_id)
                        ->whereIn('role', [
                            ProjectRole::Lead->value,
                            ProjectRole::Member->value,
                        ])),
            ],
        ];
    }
}
