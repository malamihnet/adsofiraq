<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CampaignArchiveReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:campaigns,id'],
            'pinned' => ['nullable', 'array'],
            'pinned.*' => ['integer', 'exists:campaigns,id'],
        ];
    }
}
