<?php

namespace Tests\Unit;

use App\Services\Import\CampaignImportSlugService;
use PHPUnit\Framework\TestCase;

class CampaignImportSlugServiceTest extends TestCase
{
    public function test_preferred_slug_from_aotw_url(): void
    {
        $service = new CampaignImportSlugService;

        $this->assertSame(
            'ecoshield-vision',
            $service->preferredFromSourceUrl('https://adsoftheworld.com/campaigns/ecoshield-vision')
        );
    }

    public function test_preferred_slug_ignores_new_campaign_path(): void
    {
        $service = new CampaignImportSlugService;

        $this->assertNull($service->preferredFromSourceUrl('https://www.adsoftheworld.com/campaigns/new'));
    }
}
