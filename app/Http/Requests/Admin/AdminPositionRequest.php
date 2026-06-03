<?php

namespace App\Http\Requests\Admin;

use App\Enums\PositionCategory;
use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $positionId = $this->route('position')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => [
                Rule::requiredIf(fn () => Position::hasCategoryColumn()),
                'nullable',
                'string',
                Rule::in(array_column(PositionCategory::cases(), 'value')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
        ];
    }
}
