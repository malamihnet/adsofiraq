<?php

namespace Tests\Unit;

use App\Services\CampaignArchiveOrderingService;
use PHPUnit\Framework\TestCase;

class CampaignArchiveOrderingServiceTest extends TestCase
{
    public function test_compute_start_index_for_page_four_position_two(): void
    {
        $this->assertSame(74, CampaignArchiveOrderingService::computeStartIndex(4, 2, 24));
    }

    public function test_delayed_campaign_starts_at_minimum_index(): void
    {
        $service = new CampaignArchiveOrderingService;

        $automatic = range(1, 80);
        $delayed = [
            ['id' => 999, 'start_index' => 74, 'approved_at' => 1000, 'sort_id' => 999],
        ];

        $ordered = $service->mergeDelayedIntoArchiveOrder($automatic, $delayed);

        $this->assertSame(999, $ordered[73]);
        $this->assertNotContains(999, array_slice($ordered, 0, 73));
    }

    public function test_newer_automatic_campaigns_push_delayed_campaign_down(): void
    {
        $service = new CampaignArchiveOrderingService;

        $baseAutomatic = range(1, 79);
        $delayed = [
            ['id' => 900, 'start_index' => 74, 'approved_at' => 500, 'sort_id' => 900],
        ];

        $withOneNewer = array_merge([1000], $baseAutomatic);
        $ordered = $service->mergeDelayedIntoArchiveOrder($withOneNewer, $delayed);

        $this->assertSame(900, $ordered[74]);

        $withTenNewer = array_merge(range(2001, 2010), $baseAutomatic);
        $orderedTen = $service->mergeDelayedIntoArchiveOrder($withTenNewer, $delayed);

        $this->assertSame(900, $orderedTen[83]);
    }

    public function test_delayed_campaign_not_duplicated_in_order(): void
    {
        $service = new CampaignArchiveOrderingService;

        $automatic = [900, 1, 2, 3, 4, 5];
        $delayed = [
            ['id' => 900, 'start_index' => 3, 'approved_at' => 100, 'sort_id' => 900],
        ];

        $ordered = $service->mergeDelayedIntoArchiveOrder($automatic, $delayed);

        $this->assertSame(1, array_count_values($ordered)[900] ?? 0);
    }

    public function test_homepage_slice_excludes_delayed_below_page_one(): void
    {
        $service = new CampaignArchiveOrderingService;

        $automatic = range(1, 100);
        $delayed = [
            ['id' => 500, 'start_index' => 74, 'approved_at' => 2000, 'sort_id' => 500],
        ];

        $ordered = $service->mergeDelayedIntoArchiveOrder($automatic, $delayed);
        $homepage = array_slice($ordered, 0, 16);

        $this->assertNotContains(500, $homepage);
    }

    public function test_multiple_delayed_at_same_start_index_order_by_approved_at_desc(): void
    {
        $service = new CampaignArchiveOrderingService;

        $automatic = range(1, 70);
        $delayed = [
            ['id' => 10, 'start_index' => 74, 'approved_at' => 200, 'sort_id' => 10],
            ['id' => 20, 'start_index' => 74, 'approved_at' => 300, 'sort_id' => 20],
        ];

        $ordered = $service->mergeDelayedIntoArchiveOrder($automatic, $delayed);

        $this->assertSame(20, $ordered[73]);
        $this->assertSame(10, $ordered[74]);
    }

    public function test_clear_cache_keys_defined(): void
    {
        $this->assertSame('archive_delayed_campaigns', CampaignArchiveOrderingService::DELAYED_CACHE_KEY);
        $this->assertSame('archive_delayed_campaigns', CampaignArchiveOrderingService::PLACEMENTS_CACHE_KEY);
        $this->assertSame('archive_automatic_campaign_ids', CampaignArchiveOrderingService::AUTOMATIC_IDS_CACHE_KEY);
        $this->assertSame('homepage_latest_campaign_ids', CampaignArchiveOrderingService::HOMEPAGE_LATEST_CACHE_KEY);
    }
}
