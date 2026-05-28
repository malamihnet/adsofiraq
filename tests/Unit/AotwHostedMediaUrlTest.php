<?php

namespace Tests\Unit;

use App\Services\Import\AotwHostedMediaUrl;
use PHPUnit\Framework\TestCase;

class AotwHostedMediaUrlTest extends TestCase
{
    public function test_s3_still_without_extension_is_accepted_in_verified_block(): void
    {
        $url = 'https://aor-images-prod.s3.amazonaws.com/iclrvsbxw6secztgtrbqro38s8pp';

        $this->assertTrue(AotwHostedMediaUrl::isGalleryStillUrl($url, inVerifiedMediaBlock: true));
        $this->assertTrue(AotwHostedMediaUrl::isHostedCampaignImageUrl($url));
    }

    public function test_direct_video_host_without_extension(): void
    {
        $url = 'https://video.adsoftheworld.com/1saexufqes8yx9awmmugfrhg0ph3';

        $this->assertTrue(AotwHostedMediaUrl::isDirectVideoUrl($url));
    }

    public function test_video_host_is_not_a_gallery_still(): void
    {
        $url = 'https://video.adsoftheworld.com/abc123';

        $this->assertFalse(AotwHostedMediaUrl::isGalleryStillUrl($url));
    }
}
