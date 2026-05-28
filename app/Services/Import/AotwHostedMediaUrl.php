<?php

namespace App\Services\Import;

/**
 * URL rules for Ads of the World hosted campaign media (stills + direct video).
 */
final class AotwHostedMediaUrl
{
    public static function isExcludedStillUrl(string $url): bool
    {
        $lower = strtolower($url);

        return str_contains($lower, 'placeholder')
            || str_contains($lower, 'avatar')
            || str_contains($lower, '/logo')
            || str_contains($lower, '/static/')
            || str_contains($lower, 'favicon')
            || str_contains($lower, 'default_image')
            || str_contains($lower, 'video.adsoftheworld.com');
    }

    /**
     * Gallery still URL check. When $inVerifiedMediaBlock is true, any image inside an
     * accepted div.bg-white.my-3 block is treated as campaign media (not "outside area").
     */
    public static function isGalleryStillUrl(string $url, bool $inVerifiedMediaBlock = false): bool
    {
        if (self::isExcludedStillUrl($url)) {
            return false;
        }

        if (self::isHostedCampaignImageUrl($url)) {
            return true;
        }

        if ($inVerifiedMediaBlock && preg_match('#^https?://#i', $url)) {
            return true;
        }

        return (bool) preg_match('/\.(jpe?g|png|webp|gif|avif)(\?|#|$)/i', $url);
    }

    public static function isHostedCampaignImageUrl(string $url): bool
    {
        $lower = strtolower($url);

        if (str_contains($lower, 'image.adsoftheworld.com')) {
            return ! str_contains($lower, 'image.adsoftheworld.com/static/');
        }

        if (str_contains($lower, 'aor-images-prod.s3.amazonaws.com')) {
            return true;
        }

        if (str_contains($lower, 'adsoftheworld.com/rails/active_storage')) {
            return true;
        }

        if (str_contains($lower, 'storage.googleapis.com')) {
            return true;
        }

        return false;
    }

    /**
     * Direct uploaded video on AOTW CDN (no file extension required).
     */
    public static function isDirectVideoUrl(string $url): bool
    {
        return (bool) preg_match(
            '~^https?://video\.adsoftheworld\.com/[a-z0-9]+(?:[/?#]|$)~i',
            trim($url),
        );
    }
}
