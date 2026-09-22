<?php

namespace App\Http\Requests;

use App\Models\Label;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLabelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return $this->user()?->can('create', [Label::class, $workspace]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('labels', 'name')
                    ->where('workspace_id', $workspace->id),
            ],
            'color' => ['required', 'string', 'regex:/^#[0-9A-F]{6}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $color = $this->input('color');

        if (is_string($color)) {
            $this->merge(['color' => strtoupper($color)]);
        }
    }
}
