<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesCampaignTaxonomies;
use App\Http\Requests\Concerns\ValidatesCampaignVideos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminCampaignStoreRequest extends FormRequest
{
    use ValidatesCampaignTaxonomies;
    use ValidatesCampaignVideos;

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $maxThumb = config('upload.max_thumbnail_kb');
        $maxAsset = config('upload.max_asset_kb');
        $mimes = config('upload.allowed_mimes');

        return array_merge($this->campaignTaxonomyRules(), $this->campaignVideoRules(), [
            'user_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'description' => ['required', 'string'],
            'credits' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:'.implode(',', $mimes), 'max:'.$maxThumb],
            'assets' => ['nullable', 'array'],
            'assets.*' => ['image', 'mimes:'.implode(',', $mimes), 'max:'.$maxAsset],
            'submission_notes' => ['nullable', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string'],
            'is_student' => ['boolean'],
            'is_nsfw' => ['boolean'],
            'is_featured' => ['boolean'],
            'is_verified' => ['boolean'],
            'is_hero' => ['boolean'],
            'hero_order' => ['nullable', 'integer', 'min:1', 'max:99'],
            'enable_manual_archive_position' => ['boolean'],
            'manual_order' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:pending,approved,rejected'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateCampaignVideos($validator);

            if ($this->boolean('is_hero') && $this->input('status') !== 'approved') {
                $validator->errors()->add(
                    'is_hero',
                    'Only approved campaigns can be featured in the homepage slider.'
                );
            }

            if ($this->boolean('enable_manual_archive_position')) {
                if ($this->input('status') !== 'approved') {
                    $validator->errors()->add(
                        'manual_order',
                        'Only approved campaigns can use a custom archive position.'
                    );
                } elseif (! $this->filled('manual_order')) {
                    $validator->errors()->add(
                        'manual_order',
                        'Enter a custom archive position or disable custom positioning.'
                    );
                }
            }

            $this->validateCampaignTaxonomyItems($validator);
        });
    }
}
