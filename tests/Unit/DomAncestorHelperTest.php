<?php

namespace Tests\Unit;

use App\Services\Import\DomAncestorHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

class DomAncestorHelperTest extends TestCase
{
    public function test_ancestors_filter_does_not_false_positive_under_main(): void
    {
        $html = <<<'HTML'
        <div id="main">
            <div id="campaign_header_1"></div>
            <div class="bg-white my-3">
                <img class="max-h-screen" width="800" height="600" src="https://image.adsoftheworld.com/gallery-still" />
            </div>
            <div id="related">
                <a href="/campaigns/other"><img class="w-36" src="https://image.adsoftheworld.com/related-thumb" /></a>
            </div>
        </div>
        HTML;

        $crawler = new Crawler($html, 'https://www.adsoftheworld.com/campaigns/test');
        $galleryBlock = $crawler->filter('div.bg-white.my-3')->first();

        $this->assertFalse(DomAncestorHelper::blockIsInRelatedCampaignsRegion($galleryBlock));
        $this->assertFalse(DomAncestorHelper::isInsideCampaignHeader($galleryBlock->getNode(0)));
        $this->assertFalse(DomAncestorHelper::isInsideCampaignLink($galleryBlock->getNode(0)));

        $relatedImg = $crawler->filter('#related img')->first();
        $this->assertTrue(DomAncestorHelper::isInsideCampaignLink($relatedImg->getNode(0)));
    }
}
