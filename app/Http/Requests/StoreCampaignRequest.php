<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesCampaignTaxonomies;
use App\Http\Requests\Concerns\ValidatesCampaignVideos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCampaignRequest extends FormRequest
{
    use ValidatesCampaignTaxonomies;
    use ValidatesCampaignVideos;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maxThumb = config('upload.max_thumbnail_kb');
        $maxAsset = config('upload.max_asset_kb');
        $mimes = config('upload.allowed_mimes');

        return array_merge($this->campaignTaxonomyRules(), $this->campaignVideoRules(), [
            'title' => ['required', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'description' => ['required', 'string'],
            'credits' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'image', 'mimes:'.implode(',', $mimes), 'max:'.$maxThumb],
            'assets' => ['nullable', 'array'],
            'assets.*' => ['image', 'mimes:'.implode(',', $mimes), 'max:'.$maxAsset],
            'submission_notes' => ['nullable', 'string', 'max:1000'],
            'is_student' => ['boolean'],
            'is_nsfw' => ['boolean'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateCampaignVideos($validator);
            $this->validateCampaignTaxonomyItems($validator);
        });
    }
}
