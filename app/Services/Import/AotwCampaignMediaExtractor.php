<?php

namespace App\Services\Import;

use App\Services\VideoUrlParser;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Walks Ads of the World campaign media blocks and extracts gallery stills + videos in page order.
 */
class AotwCampaignMediaExtractor
{
    public function __construct(
        protected CampaignImportImageUrlResolver $urlResolver,
    ) {}

    /**
     * @param  array{name: ?string, description: ?string, embedUrl: ?string, thumbnailUrl: ?string}  $jsonLd
     * @return array{
     *     image_urls: list<string>,
     *     videos: list<array{type: string, url: string}>,
     *     direct_video_urls: list<string>,
     *     debug: array<string, mixed>,
     * }
     */
    public function extract(Crawler $main, string $sourceUrl, ?string $heroImageUrl, array $jsonLd): array
    {
        $imageUrls = [];
        $videos = [];
        $directVideoUrls = [];
        $skippedUrls = [];
        $mediaBlocks = [];
        $seenStill = [];
        $seenVideoKeys = [];
        $seenDirect = [];

        $recordSkip = function (?string $url, string $reason) use (&$skippedUrls, $sourceUrl) {
            if ($url === null || $url === '') {
                return;
            }

            $resolved = $this->urlResolver->resolve($url, $sourceUrl) ?? $url;
            $skippedUrls[] = ['url' => $resolved, 'reason' => $reason];
        };

        $blocks = $this->collectMediaBlocks($main, $sourceUrl);

        foreach ($blocks as $index => $block) {
            $blockEntry = [
                'index' => $index,
                'type' => 'skipped',
                'urls' => [],
                'skipped' => [],
            ];

            if (! $this->blockContainsUploadedCampaignMedia($block)) {
                $blockEntry['type'] = 'skipped';
                $mediaBlocks[] = $blockEntry;

                continue;
            }

            $hasVideo = $this->blockIsVideoMedia($block);
            $hasStill = $this->blockHasGalleryStill($block);

            $blockVideos = $this->extractVideosFromBlock(
                $block,
                $sourceUrl,
                $videos,
                $directVideoUrls,
                $seenVideoKeys,
                $seenDirect,
                $recordSkip,
            );

            $blockStills = [];

            if ($hasStill) {
                $blockStills = $this->extractStillsFromBlock(
                    $block,
                    $sourceUrl,
                    $heroImageUrl,
                    $imageUrls,
                    $seenStill,
                    $recordSkip,
                );
            }

            $blockEntry['urls'] = array_merge($blockVideos, $blockStills);
            $blockEntry['type'] = match (true) {
                $hasVideo && $hasStill => 'mixed',
                $hasVideo => 'video',
                $hasStill => 'image',
                default => 'skipped',
            };
            $mediaBlocks[] = $blockEntry;
        }

        if (! empty($jsonLd['embedUrl'])) {
            $this->addEmbedVideo(
                $jsonLd['embedUrl'],
                $sourceUrl,
                $videos,
                $seenVideoKeys,
                $recordSkip,
            );
        }

        if ($imageUrls === []) {
            $this->extractGalleryStillsFallback(
                $main,
                $sourceUrl,
                $heroImageUrl,
                $imageUrls,
                $seenStill,
                $recordSkip,
                $mediaBlocks,
            );
        }

        $debug = [
            'hero_image_found' => $heroImageUrl !== null,
            'hero_image_url' => $heroImageUrl,
            'gallery_containers' => count($blocks),
            'gallery_still_count' => count($imageUrls),
            'gallery_video_count' => count($videos),
            'gallery_direct_video_count' => count($directVideoUrls),
            'skipped_non_gallery_images' => count(array_filter(
                $skippedUrls,
                fn (array $s) => ! in_array($s['reason'], ['duplicate', 'unsupported'], true),
            )),
            'skipped_urls' => $skippedUrls,
            'media_blocks' => $mediaBlocks,
        ];

        Log::info('AOTW campaign media extracted.', array_merge(['source_url' => $sourceUrl], $debug));

        return [
            'image_urls' => $imageUrls,
            'videos' => $videos,
            'direct_video_urls' => $directVideoUrls,
            'debug' => $debug,
        ];
    }

    /**
     * @return list<Crawler>
     */
    protected function collectMediaBlocks(Crawler $main, string $sourceUrl): array
    {
        if (! $main->count()) {
            return [];
        }

        $blocks = [];
        $seen = [];
        $baseUri = $main->getUri() ?: $sourceUrl;
        $mainNode = $main->getNode(0);

        $add = function (Crawler $block) use (&$blocks, &$seen, $main, $sourceUrl) {
            $node = $block->getNode(0);

            if ($node === null) {
                return;
            }

            $id = spl_object_id($node);

            if (isset($seen[$id]) || ! $this->isCampaignGalleryMediaBlock($block, $main)) {
                return;
            }

            $seen[$id] = true;
            $blocks[] = $block;
        };

        if ($mainNode instanceof \DOMElement) {
            foreach ($mainNode->childNodes as $child) {
                if (! ($child instanceof \DOMElement)) {
                    continue;
                }

                if ($this->isPostCampaignBoundary($child)) {
                    break;
                }

                if ($this->isCampaignMediaBlockElement($child)) {
                    $add(new Crawler($child, $baseUri));
                }
            }
        }

        $this->eachMediaContainerDiv($main, function (Crawler $block) use ($add) {
            $add($block);
        });

        return $blocks;
    }

    protected function isPostCampaignBoundary(\DOMElement $element): bool
    {
        $id = $element->getAttribute('id') ?? '';
        $class = $element->getAttribute('class') ?? '';

        if ($id === 'related' || str_starts_with($id, 'related_')) {
            return true;
        }

        if (str_contains($class, 'grid-cols') && str_contains($class, 'grid')) {
            return true;
        }

        if (str_contains($class, 'flex') && str_contains($class, 'mt-6') && $element->getElementsByTagName('a')->length > 0) {
            return true;
        }

        return strtolower($element->tagName) === 'footer';
    }

    protected function isCampaignMediaBlockElement(\DOMElement $element): bool
    {
        if (strtolower($element->tagName) !== 'div') {
            return false;
        }

        $id = $element->getAttribute('id') ?? '';

        if (str_starts_with($id, 'campaign_header')) {
            return false;
        }

        $class = $element->getAttribute('class') ?? '';

        return $this->classTokensContain($class, 'bg-white')
            && $this->classTokensContain($class, 'my-3');
    }

    /**
     * @param  callable(Crawler): void  $callback
     */
    protected function eachMediaContainerDiv(Crawler $scope, callable $callback): void
    {
        $scope->filter('div')->each(function (Crawler $div) use ($callback) {
            if ($this->hasClasses($div, ['bg-white', 'my-3'])) {
                $callback($div);
            }
        });
    }

    /**
     * @param  list<string>  $required
     */
    protected function hasClasses(Crawler $node, array $required): bool
    {
        return $this->classTokensContain($node->attr('class') ?? '', ...$required);
    }

    protected function classTokensContain(string $classAttr, string ...$required): bool
    {
        $tokens = preg_split('/\s+/', trim($classAttr), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($required as $token) {
            if (! in_array($token, $tokens, true)) {
                return false;
            }
        }

        return true;
    }

    protected function isCampaignGalleryMediaBlock(Crawler $block, Crawler $main): bool
    {
        $node = $block->getNode(0);

        if (! ($node instanceof \DOMElement) || ! $this->isCampaignMediaBlockElement($node)) {
            return false;
        }

        if (DomAncestorHelper::isInsideCampaignHeader($node)) {
            return false;
        }

        if (DomAncestorHelper::blockIsInRelatedCampaignsRegion($block)) {
            return false;
        }

        if (DomAncestorHelper::isInsideFooter($node)) {
            return false;
        }

        return $this->blockContainsUploadedCampaignMedia($block);
    }

    /**
     * @param  list<string>  $imageUrls
     * @param  array<string, true>  $seenStill
     * @param  callable(?string, string): void  $recordSkip
     * @param  list<array<string, mixed>>  $mediaBlocks
     */
    protected function extractGalleryStillsFallback(
        Crawler $main,
        string $sourceUrl,
        ?string $heroImageUrl,
        array &$imageUrls,
        array &$seenStill,
        callable $recordSkip,
        array &$mediaBlocks,
    ): void {
        $index = count($mediaBlocks);
        $blockEntry = [
            'index' => $index,
            'type' => 'image',
            'urls' => [],
            'skipped' => [],
            'source' => 'fallback_scan',
        ];

        $add = function (?string $url) use ($sourceUrl, $heroImageUrl, &$imageUrls, &$seenStill, $recordSkip, &$blockEntry) {
            if ($url === null || $url === '') {
                return;
            }

            $resolved = $this->urlResolver->resolve($url, $sourceUrl);

            if ($resolved === null || ! AotwHostedMediaUrl::isGalleryStillUrl($resolved, inVerifiedMediaBlock: true)) {
                $recordSkip($url, 'outside campaign media area');

                return;
            }

            if ($heroImageUrl !== null && $resolved === $heroImageUrl) {
                $recordSkip($resolved, 'thumbnail/hero');

                return;
            }

            if (isset($seenStill[$resolved])) {
                $recordSkip($resolved, 'duplicate');

                return;
            }

            $seenStill[$resolved] = true;
            $imageUrls[] = $resolved;
            $blockEntry['urls'][] = $resolved;
        };

        $main->filter('img')->each(function (Crawler $img) use ($add, $main, $sourceUrl, $recordSkip) {
            if ($this->nodeIsInsideRelatedCampaignLink($img)) {
                $recordSkip($this->extractPrimaryImageUrlFromImg($img, $sourceUrl), 'related campaign');

                return;
            }

            if (! $this->imgLooksLikeUploadedStill($img)) {
                return;
            }

            if (! $this->imgIsInCampaignContentRegion($img, $main)) {
                $recordSkip($this->extractPrimaryImageUrlFromImg($img, $sourceUrl), 'outside campaign media area');

                return;
            }

            $add($this->extractPrimaryImageUrlFromImg($img, $sourceUrl));
        });

        if ($blockEntry['urls'] !== []) {
            $mediaBlocks[] = $blockEntry;
        }
    }

    protected function imgIsInCampaignContentRegion(Crawler $img, Crawler $main): bool
    {
        $imgNode = $img->getNode(0);

        if (! $imgNode) {
            return false;
        }

        $boundary = $this->findFirstContentBoundary($main);

        if ($boundary === null) {
            return true;
        }

        $position = $boundary->compareDocumentPosition($imgNode);

        return (bool) ($position & \DOMNode::DOCUMENT_POSITION_FOLLOWING);
    }

    protected function findFirstContentBoundary(Crawler $main): ?\DOMElement
    {
        $mainNode = $main->getNode(0);

        if (! ($mainNode instanceof \DOMElement)) {
            return null;
        }

        foreach ($mainNode->childNodes as $child) {
            if ($child instanceof \DOMElement && $this->isPostCampaignBoundary($child)) {
                return $child;
            }
        }

        $related = $main->filter('#related')->first();

        if ($related->count()) {
            $node = $related->getNode(0);

            return $node instanceof \DOMElement ? $node : null;
        }

        return null;
    }

    protected function blockContainsUploadedCampaignMedia(Crawler $block): bool
    {
        return $this->blockIsVideoMedia($block) || $this->blockHasGalleryStill($block);
    }

    protected function blockHasGalleryStill(Crawler $block): bool
    {
        $hasStill = false;

        $block->filter('img')->each(function (Crawler $img) use (&$hasStill, $block) {
            if ($this->imgIsVideoPoster($img, $block)) {
                return;
            }

            if ($this->imgLooksLikeUploadedStill($img)) {
                $hasStill = true;
            }
        });

        return $hasStill;
    }

    protected function blockIsVideoMedia(Crawler $block): bool
    {
        if ($block->filter('iframe[src*="youtube"], iframe[src*="vimeo"], video')->count() > 0) {
            return true;
        }

        return $block->filter('a[href*="youtube.com"], a[href*="youtu.be"], a[href*="vimeo.com"]')->count() > 0;
    }

    /**
     * @param  list<array{type: string, url: string}>  $videos
     * @param  list<string>  $directVideoUrls
     * @param  array<string, true>  $seenVideoKeys
     * @param  array<string, true>  $seenDirect
     * @param  callable(?string, string): void  $recordSkip
     * @return list<string>
     */
    protected function extractVideosFromBlock(
        Crawler $block,
        string $sourceUrl,
        array &$videos,
        array &$directVideoUrls,
        array &$seenVideoKeys,
        array &$seenDirect,
        callable $recordSkip,
    ): array {
        $extracted = [];

        $collect = function (?string $rawUrl) use (
            $sourceUrl,
            &$videos,
            &$directVideoUrls,
            &$seenVideoKeys,
            &$seenDirect,
            $recordSkip,
            &$extracted,
        ) {
            if ($rawUrl === null || trim($rawUrl) === '') {
                return;
            }

            $resolved = $this->urlResolver->resolve(trim($rawUrl), $sourceUrl) ?? trim($rawUrl);

            if (AotwHostedMediaUrl::isDirectVideoUrl($resolved)) {
                if (! isset($seenDirect[$resolved])) {
                    $seenDirect[$resolved] = true;
                    $directVideoUrls[] = $resolved;
                    $extracted[] = $resolved;
                }

                return;
            }

            if (preg_match('/\.(mp4|webm|mov)(\?|#|$)/i', $resolved)) {
                if (! isset($seenDirect[$resolved])) {
                    $seenDirect[$resolved] = true;
                    $directVideoUrls[] = $resolved;
                    $extracted[] = $resolved;
                }

                return;
            }

            $parsed = VideoUrlParser::parse($resolved);

            if ($parsed === null) {
                $recordSkip($resolved, 'unsupported');

                return;
            }

            $embedUrl = $parsed['embed_url'];
            $key = $parsed['provider'].':'.$parsed['video_id'];

            if (isset($seenVideoKeys[$key])) {
                $recordSkip($resolved, 'duplicate');

                return;
            }

            $seenVideoKeys[$key] = true;
            $videos[] = [
                'type' => $parsed['provider'],
                'url' => $embedUrl,
            ];
            $extracted[] = $embedUrl;
        };

        $block->filter('iframe[src]')->each(function (Crawler $node) use ($collect) {
            $collect(trim($node->attr('src') ?? ''));
        });

        $block->filter('a[href*="youtube.com"], a[href*="youtu.be"], a[href*="vimeo.com"], a[href*="player.vimeo.com"]')
            ->each(function (Crawler $node) use ($collect) {
                $collect(trim($node->attr('href') ?? ''));
            });

        $block->filter('video')->each(function (Crawler $video) use ($collect, $sourceUrl) {
            $src = trim($video->attr('src') ?? '');

            if ($src !== '') {
                $collect($this->urlResolver->resolve($src, $sourceUrl));

                return;
            }

            $video->filter('source[src]')->each(function (Crawler $source) use ($collect, $sourceUrl) {
                $src = trim($source->attr('src') ?? '');

                if ($src !== '') {
                    $collect($this->urlResolver->resolve($src, $sourceUrl));
                }
            });
        });

        return $extracted;
    }

    /**
     * @param  list<string>  $imageUrls
     * @param  array<string, true>  $seenStill
     * @param  callable(?string, string): void  $recordSkip
     * @return list<string>
     */
    protected function extractStillsFromBlock(
        Crawler $block,
        string $sourceUrl,
        ?string $heroImageUrl,
        array &$imageUrls,
        array &$seenStill,
        callable $recordSkip,
    ): array {
        $extracted = [];

        $add = function (?string $url) use (
            $sourceUrl,
            $heroImageUrl,
            &$imageUrls,
            &$seenStill,
            $recordSkip,
            &$extracted,
        ) {
            if ($url === null || $url === '') {
                return;
            }

            $resolved = $this->urlResolver->resolve($url, $sourceUrl);

            if ($resolved === null || ! AotwHostedMediaUrl::isGalleryStillUrl($resolved, inVerifiedMediaBlock: true)) {
                $recordSkip($url, 'outside campaign media area');

                return;
            }

            if ($heroImageUrl !== null && $resolved === $heroImageUrl) {
                $recordSkip($resolved, 'thumbnail/hero');

                return;
            }

            if (isset($seenStill[$resolved])) {
                $recordSkip($resolved, 'duplicate');

                return;
            }

            $seenStill[$resolved] = true;
            $imageUrls[] = $resolved;
            $extracted[] = $resolved;
        };

        $block->filter('picture')->each(function (Crawler $picture) use ($add, $sourceUrl, $recordSkip) {
            if ($this->nodeIsInsideRelatedCampaignLink($picture)) {
                $recordSkip($this->extractBestUrlFromPicture($picture, $sourceUrl), 'related campaign');

                return;
            }

            $add($this->extractBestUrlFromPicture($picture, $sourceUrl));
        });

        $block->filter('img')->each(function (Crawler $img) use ($add, $sourceUrl, $block, $recordSkip) {
            if ($this->nodeIsInsidePicture($img) || $this->nodeIsInsideRelatedCampaignLink($img)) {
                $recordSkip($this->extractPrimaryImageUrlFromImg($img, $sourceUrl), 'related campaign');

                return;
            }

            if ($this->imgIsVideoPoster($img, $block)) {
                $recordSkip($this->extractPrimaryImageUrlFromImg($img, $sourceUrl), 'video poster');

                return;
            }

            if (! $this->imgLooksLikeUploadedStill($img)) {
                $recordSkip($this->extractPrimaryImageUrlFromImg($img, $sourceUrl), 'outside campaign media area');

                return;
            }

            $add($this->extractPrimaryImageUrlFromImg($img, $sourceUrl));
        });

        return $extracted;
    }

    /**
     * @param  list<array{type: string, url: string}>  $videos
     * @param  array<string, true>  $seenVideoKeys
     * @param  callable(?string, string): void  $recordSkip
     */
    protected function addEmbedVideo(
        string $url,
        string $sourceUrl,
        array &$videos,
        array &$seenVideoKeys,
        callable $recordSkip,
    ): void {
        $resolved = $this->urlResolver->resolve($url, $sourceUrl) ?? $url;
        $parsed = VideoUrlParser::parse($resolved);

        if ($parsed === null) {
            $recordSkip($resolved, 'unsupported');

            return;
        }

        $key = $parsed['provider'].':'.$parsed['video_id'];

        if (isset($seenVideoKeys[$key])) {
            $recordSkip($resolved, 'duplicate');

            return;
        }

        $seenVideoKeys[$key] = true;
        $videos[] = [
            'type' => $parsed['provider'],
            'url' => $parsed['embed_url'],
        ];
    }

    protected function imgIsVideoPoster(Crawler $img, Crawler $block): bool
    {
        if ($block->filter('video')->count() === 0) {
            return false;
        }

        $poster = trim($block->filter('video')->first()->attr('poster') ?? '');

        if ($poster === '') {
            return false;
        }

        $imgSrc = trim($img->attr('src') ?? '');

        return $imgSrc !== '' && $imgSrc === $poster;
    }

    protected function nodeIsInsidePicture(Crawler $node): bool
    {
        $domNode = $node->getNode(0);

        return $domNode !== null && DomAncestorHelper::isInsidePicture($domNode);
    }

    protected function nodeIsInsideRelatedCampaignLink(Crawler $node): bool
    {
        $domNode = $node->getNode(0);

        return $domNode !== null && DomAncestorHelper::isInsideCampaignLink($domNode);
    }

    protected function imgLooksLikeUploadedStill(Crawler $img): bool
    {
        $class = strtolower($img->attr('class') ?? '');

        if (str_contains($class, 'object-scale-down') || str_contains($class, 'max-h-screen')) {
            return true;
        }

        $width = (int) ($img->attr('width') ?? 0);
        $height = (int) ($img->attr('height') ?? 0);

        return $width >= 400 || $height >= 400;
    }

    protected function extractPrimaryImageUrlFromImg(Crawler $img, string $sourceUrl): ?string
    {
        foreach (['src', 'data-src', 'data-lazy-src', 'data-original', 'data-lazy', 'data-image'] as $attr) {
            $value = trim($img->attr($attr) ?? '');

            if ($value === '') {
                continue;
            }

            return str_contains($value, ',')
                ? $this->urlResolver->bestFromSrcset($value, $sourceUrl)
                : $this->urlResolver->resolve($value, $sourceUrl);
        }

        return null;
    }

    protected function extractBestUrlFromPicture(Crawler $picture, string $sourceUrl): ?string
    {
        $candidates = [];

        $picture->filter('source[srcset], source[src], img[src]')->each(function (Crawler $node) use (&$candidates, $sourceUrl) {
            foreach (['srcset', 'data-srcset', 'src', 'data-src'] as $attr) {
                $value = trim($node->attr($attr) ?? '');

                if ($value === '') {
                    continue;
                }

                $resolved = str_contains($attr, 'srcset')
                    ? $this->urlResolver->bestFromSrcset($value, $sourceUrl)
                    : $this->urlResolver->resolve($value, $sourceUrl);

                if ($resolved !== null) {
                    $candidates[] = $resolved;
                }
            }
        });

        return $this->urlResolver->preferOriginalFormat($candidates);
    }

    protected function isGalleryStillUrl(string $url, bool $inVerifiedMediaBlock = false): bool
    {
        return AotwHostedMediaUrl::isGalleryStillUrl($url, $inVerifiedMediaBlock);
    }
}
