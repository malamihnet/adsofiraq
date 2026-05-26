<?php

namespace App\Http\Requests\Admin;

use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCampaignHeroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'is_hero' => ['sometimes', 'boolean'],
            'hero_order' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Campaign $campaign */
            $campaign = $this->route('campaign');

            if ($this->boolean('is_hero') && $campaign->status !== 'approved') {
                $validator->errors()->add(
                    'is_hero',
                    'Only approved campaigns can be featured in the homepage slider.'
                );
            }
        });
    }
}
