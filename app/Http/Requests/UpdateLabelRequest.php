<?php

namespace App\Http\Requests;

use App\Models\Label;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Label $label */
        $label = $this->route('label');

        return $this->user()?->can('update', $label) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Label $label */
        $label = $this->route('label');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('labels', 'name')
                    ->where('workspace_id', $label->workspace_id)
                    ->ignore($label),
            ],
            'color' => ['sometimes', 'required', 'string', 'regex:/^#[0-9A-F]{6}$/'],
            'workspace_id' => ['prohibited'],
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
