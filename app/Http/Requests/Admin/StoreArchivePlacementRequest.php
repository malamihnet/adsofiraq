<?php

namespace App\Http\Requests\Admin;

use App\Services\CampaignArchivePlacementService;
use Illuminate\Foundation\Http\FormRequest;

class StoreArchivePlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
            'archive_page' => ['required', 'integer', 'min:1'],
            'archive_position' => ['required', 'integer', 'min:1', 'max:'.CampaignArchivePlacementService::MAX_POSITION],
        ];
    }
}
