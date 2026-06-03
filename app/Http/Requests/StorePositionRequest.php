<?php

namespace App\Http\Requests;

use App\Enums\PositionCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', Rule::in(array_column(PositionCategory::cases(), 'value'))],
        ];
    }
}
