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
        return [
            'field' => ['required', 'string', Rule::in(['status', 'is_hero', 'is_verified', 'is_featured'])],
            'value' => ['required'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $field = $this->input('field');

        if (in_array($field, ['is_hero', 'is_verified', 'is_featured'], true)) {
            $this->merge([
                'value' => filter_var($this->input('value'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
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
