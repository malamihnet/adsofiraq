<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InlineCampaignUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $field = $this->input('field');
        $booleanFields = ['is_hero', 'is_verified', 'is_featured'];

        return [
            'field' => ['required', 'string', Rule::in(array_merge(['status'], $booleanFields))],
            'value' => in_array($field, $booleanFields, true)
                ? ['present', 'boolean']
                : ['required', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $field = $this->input('field');

        if (! in_array($field, ['is_hero', 'is_verified', 'is_featured'], true)) {
            return;
        }

        $raw = $this->input('value');

        if (is_bool($raw)) {
            return;
        }

        if (is_string($raw) || is_int($raw)) {
            $parsed = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($parsed !== null) {
                $this->merge(['value' => $parsed]);
            }
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('field') === 'status'
                && ! in_array($this->input('value'), ['draft', 'pending', 'approved', 'needs_changes', 'rejected'], true)) {
                $validator->errors()->add('value', 'Invalid status.');
            }
        });
    }
}
