<?php

namespace App\Http\Requests;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Project $project */
        $project = $this->route('project');

        return $this->user()?->can('viewIssues', $project) ?? false;
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
            'status' => [
                'sometimes',
                Rule::enum(IssueStatus::class),
            ],
            'assignee_id' => [
                'sometimes',
                'integer',
            ],
            'priority' => [
                'sometimes',
                Rule::enum(IssuePriority::class),
            ],
            'reporter_id' => ['sometimes', 'integer'],
            'label_ids' => ['sometimes', 'array', 'max:20'],
            'label_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('labels', 'id')
                    ->where(fn (Builder $query) => $query
                        ->where('workspace_id', $project->workspace_id)),
            ],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort_by' => [
                'sometimes',
                Rule::in(['number', 'title', 'created_at', 'updated_at']),
            ],
            'sort_direction' => ['sometimes', Rule::in(['asc', 'desc'])],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
