<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCampaignTaxonomies;
use App\Http\Requests\Concerns\ValidatesCampaignVideos;
use App\Models\Campaign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCampaignRequest extends FormRequest
{
    use ValidatesCampaignTaxonomies;
    use ValidatesCampaignVideos;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('campaign'));
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
            'title' => ['required', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'credits' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:'.implode(',', $mimes), 'max:'.$maxThumb],
            'assets' => ['nullable', 'array'],
            'assets.*' => ['image', 'mimes:'.implode(',', $mimes), 'max:'.$maxAsset],
            'submission_notes' => ['nullable', 'string', 'max:1000'],
            'is_student' => ['boolean'],
            'is_nsfw' => ['boolean'],
            'status' => ['sometimes', 'in:pending,approved,rejected'],
            'is_featured' => ['sometimes', 'boolean'],
            'admin_notes' => ['nullable', 'string'],
            'people_credits' => ['nullable', 'array'],
            'people_credits.*.person_id' => ['required_with:people_credits', 'integer', 'exists:people,id'],
            'people_credits.*.role' => ['required_with:people_credits', 'string', 'max:255'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateCampaignVideos($validator);
            $this->validateCampaignTaxonomyItems($validator);
            $this->validateUpdateRequirements($validator);
        });
    }

    protected function validateUpdateRequirements(Validator $validator): void
    {
        /** @var Campaign|null $campaign */
        $campaign = $this->route('campaign');

        if (! $campaign) {
            return;
        }

        // Approved campaigns are handled via revisions (for non-admin users),
        // so we don't require media again when submitting an update for review.
        if ($campaign->status === 'approved' && ! $this->user()?->isAdmin()) {
            return;
        }

        if ($this->hasInvalidVideoFileUpload()) {
            return;
        }

        if ($this->hasMediaIncludingExistingCampaign($campaign)) {
            return;
        }

        if ($validator->errors()->has('media')) {
            return;
        }

        $validator->errors()->add('media', 'Please add at least one still, thumbnail, or video (upload or YouTube/Vimeo link).');
    }

    protected function hasMediaIncludingExistingCampaign(Campaign $campaign): bool
    {
        if ($this->hasFile('thumbnail') && $this->file('thumbnail')?->isValid()) {
            return true;
        }

        if ($this->hasFile('assets')) {
            foreach ($this->file('assets', []) as $file) {
                if ($file?->isValid()) {
                    return true;
                }
            }
        }

        if ($this->hasValidVideoFileUpload() || $this->hasValidVideoUrl()) {
            return true;
        }

        $campaign->loadMissing(['assets', 'videos']);

        if (! empty($campaign->thumbnail_path)) {
            return true;
        }

        if ($campaign->assets->isNotEmpty()) {
            return true;
        }

        if ($campaign->videos->isNotEmpty()) {
            return true;
        }

        return false;
    }
}
