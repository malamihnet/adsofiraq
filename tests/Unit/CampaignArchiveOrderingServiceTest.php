<?php

namespace Tests\Unit;

use App\Services\CampaignArchiveOrderingService;
use PHPUnit\Framework\TestCase;

class CampaignArchiveOrderingServiceTest extends TestCase
{
    public function test_merge_fills_gaps_with_automatic_campaigns(): void
    {
        $service = new CampaignArchiveOrderingService;

        $merged = $service->mergeOrderedIds(
            pinnedByPosition: [1 => 101, 50 => 150, 85 => 185],
            automaticIds: range(1, 100),
        );

        $this->assertSame(101, $merged[0]);
        $this->assertSame(1, $merged[1]);
        $this->assertSame(48, $merged[48]);
        $this->assertSame(150, $merged[49]);
        $this->assertSame(49, $merged[50]);
        $this->assertSame(82, $merged[83]);
        $this->assertSame(185, $merged[84]);
        $this->assertSame(83, $merged[85]);
    }

    public function test_merge_without_pinned_returns_automatic_order(): void
    {
        $service = new CampaignArchiveOrderingService;

        $merged = $service->mergeOrderedIds([], [10, 20, 30]);

        $this->assertSame([10, 20, 30], $merged);
    }

    public function test_merge_has_no_duplicates(): void
    {
        $service = new CampaignArchiveOrderingService;

        $merged = $service->mergeOrderedIds([1 => 5, 2 => 6], [5, 6, 7]);

        $this->assertSame(count($merged), count(array_unique($merged)));
    }
}
