<?php

namespace Tests\Unit;

use App\Services\Import\CampaignCreditsExtractor;
use App\Services\Import\CampaignImportImageUrlResolver;
use App\Services\Import\CampaignPageParser;
use PHPUnit\Framework\TestCase;

class CampaignPageParserGalleryTest extends TestCase
{
    protected function parser(): CampaignPageParser
    {
        return new CampaignPageParser(
            new CampaignCreditsExtractor,
            new CampaignImportImageUrlResolver,
        );
    }

    public function test_video_only_campaign_has_no_gallery_stills(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/aotw-video-only.html'));
        $this->assertNotFalse($html);

        $parsed = $this->parser()->parse(
            $html,
            'https://www.adsoftheworld.com/campaigns/smarter-cooling-for-modern-living-seto-s-post-production',
        );

        $this->assertSame([], $parsed['image_urls']);
        $this->assertNotEmpty($parsed['videos']);
        $this->assertNotNull($parsed['og_image']);
        $this->assertContains($parsed['og_image'], $parsed['excluded_still_urls']);
        $this->assertStringContainsString('Smarter Cooling', $parsed['title']);
        $this->assertNotSame('', $parsed['description']);
    }

    public function test_invalid_child_selector_syntax_does_not_break_parse(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/aotw-video-only.html'));
        $this->assertNotFalse($html);

        $parser = $this->parser();

        $this->assertSame([], $parser->parse(
            $html,
            'https://www.adsoftheworld.com/campaigns/smarter-cooling-for-modern-living-seto-s-post-production',
        )['image_urls']);

        $this->assertNotEmpty($parser->parse(
            $html,
            'https://www.adsoftheworld.com/campaigns/smarter-cooling-for-modern-living-seto-s-post-production',
        )['videos']);
    }

    public function test_single_uploaded_still_is_extracted_without_og_image(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/aotw-single-still.html'));
        $this->assertNotFalse($html);

        $parsed = $this->parser()->parse(
            $html,
            'https://www.adsoftheworld.com/campaigns/ecoshield-vision',
        );

        $this->assertCount(1, $parsed['image_urls']);
        $this->assertStringContainsString('image.adsoftheworld.com/lnluwmkk20avv2610dr6dgd7mbl3', $parsed['image_urls'][0]);
        $this->assertNotContains($parsed['og_image'], $parsed['image_urls']);
    }

    public function test_video_poster_is_excluded_from_gallery_stills(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/aotw-video-poster.html'));
        $this->assertNotFalse($html);

        $parsed = $this->parser()->parse(
            $html,
            'https://www.adsoftheworld.com/campaigns/the-journey-of-money',
        );

        $this->assertSame([], $parsed['image_urls']);
        $poster = 'https://image.adsoftheworld.com/uvff0sucxo6et74ndt2krlad5w35';
        $this->assertContains($poster, $parsed['excluded_still_urls']);
    }
}
