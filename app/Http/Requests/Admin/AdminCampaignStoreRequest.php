<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidatesCampaignTaxonomies;
use App\Http\Requests\Concerns\ValidatesCampaignVideos;
use App\Models\Campaign;
use App\Services\CampaignArchivePlacementService;
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

    protected function prepareForValidation(): void
    {
        foreach (['agencies', 'production_houses', 'brands', 'industries', 'medium_types', 'countries'] as $field) {
            $values = $this->input($field);

            if (! is_array($values)) {
                continue;
            }

            $this->merge([
                $field => array_values(array_filter($values, fn ($value) => trim((string) $value) !== '')),
            ]);
        }
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
            'is_draft' => ['boolean'],
            'needs_changes' => ['boolean'],
            'is_made_by_iraq' => ['boolean'],
            'editorial_label' => ['nullable', 'string', 'max:64'],
            'ai_summary' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:draft,pending,approved,rejected,needs_changes'],
            'archive_placement_enabled' => ['sometimes', 'boolean'],
            'archive_page' => ['nullable', 'required_if:archive_placement_enabled,1,true', 'integer', 'min:1'],
            'archive_position' => ['nullable', 'required_if:archive_placement_enabled,1,true', 'integer', 'min:1', 'max:'.CampaignArchivePlacementService::MAX_POSITION],
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

            $this->validateCampaignTaxonomyItems($validator);

            if ($this->boolean('archive_placement_enabled') && $this->input('status') !== 'approved') {
                $validator->errors()->add(
                    'archive_placement_enabled',
                    'Only approved campaigns can use archive delay.',
                );
            }
        });
    }
}
