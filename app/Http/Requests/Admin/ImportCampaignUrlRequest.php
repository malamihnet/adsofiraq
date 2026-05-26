<?php

namespace App\Http\Requests\Admin;

use App\Services\Import\CampaignUrlNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class ImportCampaignUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $url = $this->input('url');

            if (! is_string($url)) {
                return;
            }

            if (! app(CampaignUrlNormalizer::class)->isValidHttpUrl($url)) {
                $validator->errors()->add('url', 'Please enter a valid campaign URL (http or https).');
            }
        });
    }
}
