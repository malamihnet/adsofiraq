<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCampaignTaxonomies;
use App\Http\Requests\Concerns\ValidatesCampaignVideos;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StoreCampaignRequest extends FormRequest
{
    use ValidatesCampaignTaxonomies;
    use ValidatesCampaignVideos;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->published_at === '') {
            $this->merge(['published_at' => null]);
        }

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
            'is_student' => ['sometimes', 'boolean'],
            'is_nsfw' => ['sometimes', 'boolean'],
        ]);
    }

    public function messages(): array
    {
        $maxMb = max(1, (int) round((int) config('upload.max_video_kb', 51200) / 1024));

        return [
            'title.required' => 'Please enter a campaign title.',
            'thumbnail.max' => 'The thumbnail is too large.',
            'thumbnail.image' => 'The thumbnail must be an image file.',
            'assets.*.max' => 'One or more stills are too large.',
            'assets.*.image' => 'Stills must be image files (JPG, PNG, or WebP).',
            'videos.*.file.max' => "Video file is too large. Please upload under {$maxMb}MB or use a video link.",
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateCampaignVideos($validator);
            $this->validateCampaignTaxonomyItems($validator);
            $this->validatePublicSubmissionRequirements($validator);
        });
    }

    protected function validatePublicSubmissionRequirements(Validator $validator): void
    {
        $brands = $this->input('brands', []);

        if (! is_array($brands) || count($brands) < 1) {
            $validator->errors()->add('brands', 'Please add at least one brand or client name.');
        }

        if ($this->hasInvalidVideoFileUpload()) {
            return;
        }

        if ($this->hasMedia()) {
            return;
        }

        if ($validator->errors()->has('media')) {
            return;
        }

        $validator->errors()->add('media', 'Please add at least one still, thumbnail, or video (upload or YouTube/Vimeo link).');
    }

    protected function hasMedia(): bool
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

        return false;
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('Campaign submit validation failed', [
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray(),
        ]);

        throw new ValidationException($validator);
    }
}
