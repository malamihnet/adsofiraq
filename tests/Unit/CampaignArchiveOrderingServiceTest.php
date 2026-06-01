<?php

namespace Tests\Unit;

use App\Services\CampaignArchiveOrderingService;
use PHPUnit\Framework\TestCase;

class CampaignArchiveOrderingServiceTest extends TestCase
{
    public function test_merge_block_order_puts_pinned_campaigns_first(): void
    {
        $service = new CampaignArchiveOrderingService;

        $merged = $service->mergeBlockOrder(
            pinnedIds: [101, 150, 185],
            automaticIds: range(1, 10),
        );

        $this->assertSame([101, 150, 185, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $merged);
    }

    public function test_merge_without_pinned_returns_automatic_order(): void
    {
        $service = new CampaignArchiveOrderingService;

        $merged = $service->mergeBlockOrder([], [10, 20, 30]);

        $this->assertSame([10, 20, 30], $merged);
    }

    public function test_merge_has_no_duplicates(): void
    {
        $service = new CampaignArchiveOrderingService;

        $merged = $service->mergeBlockOrder([5, 6], [5, 6, 7]);

        $this->assertSame(count($merged), count(array_unique($merged)));
        $this->assertSame([5, 6, 7], $merged);
    }

    public function test_clear_cache_forgets_archive_and_homepage_keys(): void
    {
        $service = new CampaignArchiveOrderingService;

        $this->assertSame('archive_campaign_order_ids', CampaignArchiveOrderingService::CACHE_KEY);
        $this->assertSame('homepage_latest_campaign_ids', CampaignArchiveOrderingService::HOMEPAGE_LATEST_CACHE_KEY);
    }
}
