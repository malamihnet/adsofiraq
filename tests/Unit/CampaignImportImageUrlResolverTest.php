<?php

namespace Tests\Unit;

use App\Services\Import\CampaignImportImageUrlResolver;
use PHPUnit\Framework\TestCase;

class CampaignImportImageUrlResolverTest extends TestCase
{
    public function test_resolves_relative_path_against_page_url(): void
    {
        $resolver = new CampaignImportImageUrlResolver;

        $resolved = $resolver->resolve(
            '/rails/active_storage/blobs/sample.jpg',
            'https://www.adsoftheworld.com/campaigns/example',
        );

        $this->assertSame(
            'https://www.adsoftheworld.com/rails/active_storage/blobs/sample.jpg',
            $resolved,
        );
    }

    public function test_picks_largest_srcset_candidate(): void
    {
        $resolver = new CampaignImportImageUrlResolver;

        $resolved = $resolver->bestFromSrcset(
            '/small.jpg 400w, /large.jpg 1200w',
            'https://www.adsoftheworld.com/campaigns/example',
        );

        $this->assertSame(
            'https://www.adsoftheworld.com/large.jpg',
            $resolved,
        );
    }

    public function test_prefers_jpg_over_webp_for_same_picture(): void
    {
        $resolver = new CampaignImportImageUrlResolver;

        $resolved = $resolver->preferOriginalFormat([
            'https://image.adsoftheworld.com/abc123',
            'https://image.adsoftheworld.com/xyz789.jpg',
        ]);

        $this->assertSame('https://image.adsoftheworld.com/xyz789.jpg', $resolved);
    }
}
