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

    public function test_video_and_multiple_stills_extract_all_media(): void
    {
        $html = <<<'HTML'
        <html><head>
        <meta property="og:title" content="Mixed Media Campaign">
        <meta property="og:image" content="https://www.adsoftheworld.com/rails/active_storage/blobs/redirect/og.png">
        </head><body>
        <div id="main">
            <div id="campaign_header_1" class="bg-white"></div>
            <div class="bg-white my-3">
                <div class="aspect-w-16 aspect-h-9">
                    <iframe src="https://player.vimeo.com/video/1174674945"></iframe>
                </div>
            </div>
            <div class="bg-white my-3">
                <div class="overflow-hidden">
                    <img class="object-scale-down w-full max-h-screen" width="800" height="600" src="https://image.adsoftheworld.com/stillone" />
                </div>
            </div>
            <div class="bg-white my-3">
                <div class="overflow-hidden">
                    <img class="object-scale-down w-full max-h-screen" width="800" height="600" src="https://image.adsoftheworld.com/stilltwo" />
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 mt-3"></div>
        </div>
        </body></html>
        HTML;

        $parsed = $this->parser()->parse(
            $html,
            'https://www.adsoftheworld.com/campaigns/mixed-media-test',
        );

        $this->assertCount(2, $parsed['image_urls']);
        $this->assertCount(1, $parsed['videos']);
        $this->assertSame('vimeo', $parsed['videos'][0]['type']);
        $this->assertNotContains('https://image.adsoftheworld.com/stillone', $parsed['excluded_still_urls']);
    }

    public function test_multi_still_campaign_extracts_all_gallery_images(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/aotw-three-stills.html'));
        $this->assertNotFalse($html);

        $parsed = $this->parser()->parse(
            $html,
            'https://www.adsoftheworld.com/campaigns/every-blend-tells-a-story',
        );

        $this->assertCount(3, $parsed['image_urls']);
        $this->assertContains('https://image.adsoftheworld.com/a8ikvoee53fulpc0prco4zexjpyd', $parsed['image_urls']);
        $this->assertContains('https://image.adsoftheworld.com/qkxk9ldw36jiwei0xwiln6gr2gpx', $parsed['image_urls']);
        $this->assertContains('https://image.adsoftheworld.com/yhh9l2puveb1wj8gpwm7axf6d8nq', $parsed['image_urls']);
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

    public function test_nowruz_campaign_extracts_two_gallery_stills_and_separate_thumbnail(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/aotw-nowruz-two-stills.html'));
        $this->assertNotFalse($html);

        $parsed = $this->parser()->parse(
            $html,
            'https://www.adsoftheworld.com/campaigns/speaking-native-kurdish-for-nowruz',
        );

        $this->assertCount(2, $parsed['image_urls']);
        $this->assertContains('https://image.adsoftheworld.com/nf4qdxt8tla5utbz8j9s4k0ms2mk', $parsed['image_urls']);
        $this->assertContains('https://image.adsoftheworld.com/qheyz8sow8wkary047s0449hbckb', $parsed['image_urls']);
        $this->assertStringContainsString('active_storage', $parsed['hero_image_url'] ?? '');
        $this->assertNotContains($parsed['hero_image_url'], $parsed['image_urls']);
        $this->assertNotContains('https://image.adsoftheworld.com/o5o8q4uz2bo5d6mz47jh7sadalt0', $parsed['image_urls']);
        $this->assertSame(2, $parsed['aotw_parse_debug']['gallery_containers'] ?? null);
        $this->assertSame(2, $parsed['aotw_parse_debug']['gallery_still_count'] ?? null);

        $preview = $this->parser()->parsePreview(
            $html,
            'https://www.adsoftheworld.com/campaigns/speaking-native-kurdish-for-nowruz',
        );

        $this->assertCount(2, $preview['still_urls']);
        $this->assertStringContainsString('active_storage', $preview['thumbnail_url'] ?? '');
    }
}
