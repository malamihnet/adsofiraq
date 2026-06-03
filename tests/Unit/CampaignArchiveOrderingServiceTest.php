<?php

namespace Tests\Unit;

use App\Services\CampaignArchiveOrderingService;
use PHPUnit\Framework\TestCase;

class CampaignArchiveOrderingServiceTest extends TestCase
{
    public function test_build_page_ids_places_campaign_at_exact_slot(): void
    {
        $service = new CampaignArchiveOrderingService;

        $ids = $service->buildPageIds(
            placementsByPage: [3 => [5 => 999]],
            automaticIds: range(1, 30),
            page: 3,
            perPage: 24,
        );

        $this->assertCount(24, $ids);
        $this->assertSame(999, $ids[4]);
    }

    public function test_build_page_ids_excludes_placed_from_automatic_fill(): void
    {
        $service = new CampaignArchiveOrderingService;

        $ids = $service->buildPageIds(
            placementsByPage: [1 => [1 => 50]],
            automaticIds: [10, 20, 30],
            page: 1,
            perPage: 3,
        );

        $this->assertSame([50, 10, 20], $ids);
    }

    public function test_build_page_two_uses_automatic_offset_from_page_one(): void
    {
        $service = new CampaignArchiveOrderingService;

        $ids = $service->buildPageIds(
            placementsByPage: [1 => [1 => 100]],
            automaticIds: [1, 2, 3, 4, 5],
            page: 2,
            perPage: 2,
        );

        $this->assertSame([3, 4], $ids);
    }

    public function test_page_one_slice_excludes_placements_on_other_pages(): void
    {
        $service = new CampaignArchiveOrderingService;

        $pageOne = $service->buildPageIds(
            placementsByPage: [
                1 => [1 => 900],
                3 => [1 => 999],
            ],
            automaticIds: range(1, 30),
            page: 1,
            perPage: 24,
        );

        $homepageSlice = array_slice($pageOne, 0, 16);

        $this->assertContains(900, $pageOne);
        $this->assertNotContains(999, $pageOne);
        $this->assertNotContains(999, $homepageSlice);
    }

    public function test_clear_cache_keys_defined(): void
    {
        $this->assertSame('archive_placement_map', CampaignArchiveOrderingService::PLACEMENTS_CACHE_KEY);
        $this->assertSame('archive_automatic_campaign_ids', CampaignArchiveOrderingService::AUTOMATIC_IDS_CACHE_KEY);
        $this->assertSame('homepage_latest_campaign_ids', CampaignArchiveOrderingService::HOMEPAGE_LATEST_CACHE_KEY);
    }
}
