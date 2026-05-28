<?php

namespace App\Services\Import;

use App\Services\VideoUrlParser;
use Symfony\Component\DomCrawler\Crawler;

class CampaignPageParser
{
    public function __construct(
        protected CampaignCreditsExtractor $creditsExtractor,
        protected CampaignImportImageUrlResolver $urlResolver,
    ) {}

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     credits: ?string,
     *     canonical_url: ?string,
     *     og_image: ?string,
     *     thumbnail_url: ?string,
     *     brands: list<string>,
     *     agencies: list<string>,
     *     countries: list<string>,
     *     industries: list<string>,
     *     medium_types: list<string>,
     *     videos: list<array{type: string, url: string}>,
     *     image_urls: list<string>,
     *     direct_video_urls: list<string>,
     *     excluded_still_urls: list<string>,
     * }
     */
    public function parse(string $html, string $sourceUrl): array
    {
        $crawler = new Crawler($html, $sourceUrl);
        $host = parse_url($sourceUrl, PHP_URL_HOST) ?? '';

        $meta = $this->parseMetaTags($crawler, $sourceUrl);
        $jsonLd = $this->parseJsonLd($html, $sourceUrl);

        $isAotw = str_contains(strtolower($host), 'adsoftheworld.com');
        $aotw = $isAotw ? $this->parseAdsOfTheWorld($crawler, $sourceUrl) : [];

        $title = $this->firstNonEmpty([
            $aotw['title'] ?? null,
            $jsonLd['name'] ?? null,
            $meta['og:title'] ?? null,
            $meta['title'] ?? null,
        ]);

        $description = $this->firstNonEmpty([
            $aotw['description'] ?? null,
            $jsonLd['description'] ?? null,
            $meta['og:description'] ?? null,
            $meta['description'] ?? null,
        ]);

        if ($title === null && $description === null && empty($meta['og:image'])) {
            throw \App\Exceptions\CampaignImportException::noMetadata();
        }

        $videos = $this->extractVideos($crawler, $html, $jsonLd, $sourceUrl);
        $imageUrls = $isAotw
            ? ($aotw['image_urls'] ?? [])
            : $this->collectGenericGalleryUrls($crawler, $sourceUrl);
        $directVideos = $this->extractDirectVideoUrls($crawler, $html, $sourceUrl);
        $excludedStillUrls = $this->collectExcludedStillUrls($meta, $jsonLd, $crawler, $sourceUrl);

        $credits = $this->creditsExtractor->extract($crawler, $html, $sourceUrl, $isAotw);

        return [
            'title' => $title ?? 'Imported Campaign',
            'description' => $description ?? '',
            'credits' => $credits,
            'canonical_url' => $this->urlResolver->resolve($meta['canonical'] ?? null, $sourceUrl) ?? $sourceUrl,
            'og_image' => $meta['og:image'] ?? null,
            'thumbnail_url' => $jsonLd['thumbnailUrl'] ?? null,
            'brands' => array_values(array_unique(array_filter($aotw['brands'] ?? []))),
            'agencies' => array_values(array_unique(array_filter($aotw['agencies'] ?? []))),
            'countries' => array_values(array_unique(array_filter($aotw['countries'] ?? []))),
            'industries' => array_values(array_unique(array_filter($aotw['industries'] ?? []))),
            'medium_types' => array_values(array_unique(array_filter($aotw['medium_types'] ?? []))),
            'videos' => $videos,
            'image_urls' => $imageUrls,
            'direct_video_urls' => $directVideos,
            'excluded_still_urls' => $excludedStillUrls,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function parseMetaTags(Crawler $crawler, string $sourceUrl): array
    {
        $meta = [
            'og:title' => null,
            'og:description' => null,
            'og:image' => null,
            'description' => null,
            'title' => null,
            'canonical' => null,
        ];

        $crawler->filter('meta')->each(function (Crawler $node) use (&$meta) {
            $property = strtolower(trim($node->attr('property') ?? $node->attr('name') ?? ''));
            $content = trim($node->attr('content') ?? '');

            if ($content === '') {
                return;
            }

            if (in_array($property, ['og:title', 'og:description', 'og:image', 'description'], true)) {
                $meta[$property] = $content;
            }
        });

        $crawler->filter('title')->each(function (Crawler $node) use (&$meta) {
            $meta['title'] = trim($node->text());
        });

        $canonical = $crawler->filter('link[rel="canonical"]')->first();

        if ($canonical->count()) {
            $meta['canonical'] = trim($canonical->attr('href') ?? '') ?: null;
        }

        if (! empty($meta['og:title'])) {
            $meta['og:title'] = $this->cleanOgTitle($meta['og:title']);
        }

        $meta['og:image'] = $this->urlResolver->resolve($meta['og:image'] ?? null, $sourceUrl);

        return $meta;
    }

    /**
     * @return array{name: ?string, description: ?string, embedUrl: ?string, thumbnailUrl: ?string}
     */
    protected function parseJsonLd(string $html, string $sourceUrl): array
    {
        $result = [
            'name' => null,
            'description' => null,
            'embedUrl' => null,
            'thumbnailUrl' => null,
        ];

        if (! preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return $result;
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5), true);

            if (! is_array($decoded)) {
                continue;
            }

            $items = isset($decoded['@graph']) ? $decoded['@graph'] : [$decoded];

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $type = $item['@type'] ?? '';

                if (! in_array($type, ['VideoObject', 'Article', 'CreativeWork', 'ImageObject'], true)) {
                    continue;
                }

                $result['name'] ??= $this->normalizeText($item['name'] ?? null);
                $result['description'] ??= $this->normalizeText($item['description'] ?? null);
                $result['embedUrl'] ??= $item['embedUrl'] ?? $item['contentUrl'] ?? null;
                $result['thumbnailUrl'] ??= $this->urlResolver->resolve(
                    $item['thumbnailUrl'] ?? $item['image'] ?? $item['url'] ?? null,
                    $sourceUrl,
                );
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseAdsOfTheWorld(Crawler $crawler, string $sourceUrl): array
    {
        $data = [
            'title' => null,
            'description' => null,
            'brands' => [],
            'agencies' => [],
            'countries' => [],
            'industries' => [],
            'medium_types' => [],
            'image_urls' => [],
        ];

        $main = $crawler->filter('#main');

        if (! $main->count()) {
            return $data;
        }

        $main->filter('h1')->each(function (Crawler $node) use (&$data) {
            $text = $this->normalizeText($node->text());

            if ($text !== '') {
                $data['title'] = $text;
            }
        });

        $main->filter('a[href*="/brands/"]')->each(function (Crawler $node) use (&$data) {
            $name = $this->normalizeText($node->text());

            if ($name !== '') {
                $data['brands'][] = $name;
            }
        });

        $main->filter('a[href*="/agencies/"]')->each(function (Crawler $node) use (&$data) {
            $name = $this->normalizeText($node->text());

            if ($name !== '' && ! str_contains(strtolower($name), 'agency:')) {
                $data['agencies'][] = $name;
            }
        });

        $main->filter('a[href*="/countries/"]')->each(function (Crawler $node) use (&$data) {
            $name = $this->normalizeText($node->text());

            if ($name !== '') {
                $data['countries'][] = $name;
            }
        });

        $main->filter('a[href*="/medium_types/"]')->each(function (Crawler $node) use (&$data) {
            $name = $this->normalizeText($node->text());

            if ($name !== '') {
                $data['medium_types'][] = $name;
            }
        });

        $main->filter('a[href*="/industries/"]')->each(function (Crawler $node) use (&$data) {
            $name = $this->normalizeText($node->text());

            if ($name !== '') {
                $data['industries'][] = $name;
            }
        });

        $description = $this->extractAotwDescription($main);

        if ($description !== null) {
            $data['description'] = $description;
        }

        $data['image_urls'] = $this->extractAotwGalleryStills($main, $sourceUrl);

        $summary = $crawler->filter('#main p.text-sm')->first();

        if ($summary->count()) {
            $this->parseAotwSummaryLine($summary->text(), $data);
        }

        return $data;
    }

    /**
     * Uploaded campaign stills only: direct media blocks under #main (not og/meta/related cards).
     *
     * @return list<string>
     */
    protected function extractAotwGalleryStills(Crawler $main, string $sourceUrl): array
    {
        $urls = [];
        $seen = [];

        $add = function (?string $url) use (&$urls, &$seen, $sourceUrl) {
            if ($url === null || $url === '') {
                return;
            }

            $resolved = $this->urlResolver->resolve($url, $sourceUrl);

            if ($resolved === null || ! $this->isGalleryStillUrl($resolved) || isset($seen[$resolved])) {
                return;
            }

            $seen[$resolved] = true;
            $urls[] = $resolved;
        };

        $main->filter('> div.bg-white.my-3')->each(function (Crawler $block) use ($add, $sourceUrl) {
            if ($this->blockIsVideoMedia($block)) {
                return;
            }

            $block->filter('picture')->each(function (Crawler $picture) use ($add, $sourceUrl) {
                if ($this->nodeIsInsideRelatedCampaignLink($picture)) {
                    return;
                }

                $add($this->extractBestUrlFromPicture($picture, $sourceUrl));
            });

            $block->filter('img')->each(function (Crawler $img) use ($add, $sourceUrl) {
                if ($this->nodeIsInsidePicture($img) || $this->nodeIsInsideRelatedCampaignLink($img)) {
                    return;
                }

                if (! $this->imgLooksLikeUploadedStill($img)) {
                    return;
                }

                foreach ($this->imageAttributeValues($img) as $value) {
                    $resolved = str_contains($value, ',')
                        ? $this->urlResolver->bestFromSrcset($value, $sourceUrl)
                        : $this->urlResolver->resolve($value, $sourceUrl);

                    $add($resolved);
                }
            });
        });

        return $urls;
    }

    protected function blockIsVideoMedia(Crawler $block): bool
    {
        return $block->filter('iframe[src*="youtube"], iframe[src*="vimeo"], video')->count() > 0;
    }

    protected function nodeIsInsidePicture(Crawler $node): bool
    {
        return $node->ancestors()->filter('picture')->count() > 0;
    }

    protected function nodeIsInsideRelatedCampaignLink(Crawler $node): bool
    {
        return $node->ancestors()->filter('a[href*="/campaigns/"]')->count() > 0;
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

    /**
     * Non-AOTW pages: conservative img extraction (no og/json-ld/regex fallbacks).
     *
     * @return list<string>
     */
    protected function collectGenericGalleryUrls(Crawler $crawler, string $sourceUrl): array
    {
        $main = $crawler->filter('main, article, #content, .content').first();

        if (! $main->count()) {
            return [];
        }

        return $this->extractImagesFromScope($main, $sourceUrl, strictGallery: true);
    }

    /**
     * URLs that must never become campaign_assets (thumbnail/meta/video poster only).
     *
     * @param  array<string, string|null>  $meta
     * @param  array{name: ?string, description: ?string, embedUrl: ?string, thumbnailUrl: ?string}  $jsonLd
     * @return list<string>
     */
    protected function collectExcludedStillUrls(
        array $meta,
        array $jsonLd,
        Crawler $crawler,
        string $sourceUrl,
    ): array {
        $excluded = array_filter([
            $meta['og:image'] ?? null,
            $jsonLd['thumbnailUrl'] ?? null,
        ]);

        $main = $crawler->filter('#main');

        if (! $main->count()) {
            return array_values(array_unique(array_filter($excluded)));
        }

        $main->filter('> div.bg-white.my-3 video[poster]')->each(function (Crawler $video) use (&$excluded, $sourceUrl) {
            $poster = trim($video->attr('poster') ?? '');

            if ($poster !== '') {
                $resolved = $this->urlResolver->resolve($poster, $sourceUrl);

                if ($resolved !== null) {
                    $excluded[] = $resolved;
                }
            }
        });

        $unique = [];

        foreach ($excluded as $url) {
            if ($url && ! isset($unique[$url])) {
                $unique[$url] = true;
            }
        }

        return array_keys($unique);
    }

    /**
     * @return list<string>
     */
    protected function extractImagesFromScope(Crawler $scope, string $sourceUrl, bool $strictGallery = false): array
    {
        $urls = [];
        $seen = [];

        $add = function (?string $url) use (&$urls, &$seen, $strictGallery, $sourceUrl) {
            if ($url === null || $url === '') {
                return;
            }

            $resolved = $this->urlResolver->resolve($url, $sourceUrl);

            if ($resolved === null || isset($seen[$resolved])) {
                return;
            }

            if ($strictGallery && ! $this->isGalleryStillUrl($resolved)) {
                return;
            }

            $seen[$resolved] = true;
            $urls[] = $resolved;
        };

        $scope->filter('picture')->each(function (Crawler $picture) use ($add, $sourceUrl) {
            if ($this->nodeIsInsideRelatedCampaignLink($picture)) {
                return;
            }

            $add($this->extractBestUrlFromPicture($picture, $sourceUrl));
        });

        $scope->filter('img')->each(function (Crawler $img) use ($add, $sourceUrl, $strictGallery) {
            if ($this->nodeIsInsidePicture($img) || $this->nodeIsInsideRelatedCampaignLink($img)) {
                return;
            }

            if ($strictGallery && ! $this->imgLooksLikeUploadedStill($img)) {
                return;
            }

            foreach ($this->imageAttributeValues($img) as $value) {
                $resolved = str_contains($value, ',')
                    ? $this->urlResolver->bestFromSrcset($value, $sourceUrl)
                    : $this->urlResolver->resolve($value, $sourceUrl);

                $add($resolved);
            }
        });

        return $urls;
    }

    /**
     * @return list<string>
     */
    protected function imageAttributeValues(Crawler $node): array
    {
        $values = [];

        foreach (['src', 'data-src', 'data-lazy-src', 'data-original', 'data-lazy', 'data-image'] as $attr) {
            $value = trim($node->attr($attr) ?? '');

            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function parseAotwSummaryLine(string $text, array &$data): void
    {
        if (preg_match('/created for the brand:\s*([^,]+),/i', $text, $m)) {
            $data['brands'][] = trim($m[1]);
        }

        if (preg_match('/by ad(?:\s+school)?:\s*([^\.]+)\./i', $text, $m)) {
            $data['agencies'][] = trim($m[1]);
        }

        if (preg_match('/published in ([^,]+) in/i', $text, $m)) {
            $data['countries'][] = trim($m[1]);
        }

        if (preg_match('/This\s+([^\.]+?)\s+medium campaign/i', $text, $m)) {
            $data['medium_types'][] = trim($m[1]);
        }

        if (preg_match('/related to the\s+([^\.]+?)\s+industry/i', $text, $m)) {
            $data['industries'][] = trim($m[1]);
        }
    }

    protected function extractAotwDescription(Crawler $main): ?string
    {
        $paragraphs = [];

        $main->filter('p')->each(function (Crawler $node) use (&$paragraphs) {
            $prev = $node->getNode(0)?->previousSibling;

            while ($prev && $prev->nodeType !== XML_ELEMENT_NODE) {
                $prev = $prev->previousSibling;
            }

            if ($prev instanceof \DOMElement && trim($prev->textContent) === 'Description') {
                $text = $this->normalizeText($node->text());

                if ($text !== '' && ! str_starts_with($text, 'This ')) {
                    $paragraphs[] = $text;
                }
            }
        });

        if ($paragraphs !== []) {
            return implode("\n\n", $paragraphs);
        }

        $block = $main->filter('div.whitespace-pre-line')->first();

        if ($block->count()) {
            $parts = [];

            $block->filter('p')->each(function (Crawler $node) use (&$parts) {
                $text = $this->normalizeText($node->text());

                if ($text !== '' && ! str_starts_with($text, 'This ')) {
                    $parts[] = $text;
                }
            });

            if ($parts !== []) {
                return implode("\n\n", $parts);
            }
        }

        return null;
    }

    /**
     * @param  array{name: ?string, description: ?string, embedUrl: ?string, thumbnailUrl: ?string}  $jsonLd
     * @return list<array{type: string, url: string}>
     */
    protected function extractVideos(Crawler $crawler, string $html, array $jsonLd, string $sourceUrl): array
    {
        $videos = [];
        $seen = [];

        $add = function (?string $url) use (&$videos, &$seen, $sourceUrl) {
            if ($url === null || $url === '') {
                return;
            }

            $url = $this->normalizeVideoUrl($url, $sourceUrl);
            $parsed = VideoUrlParser::parse($url);

            if ($parsed === null) {
                return;
            }

            $key = $parsed['provider'].':'.$parsed['video_id'];

            if (isset($seen[$key])) {
                return;
            }

            $seen[$key] = true;
            $videos[] = [
                'type' => $parsed['provider'],
                'url' => $url,
            ];
        };

        if (! empty($jsonLd['embedUrl'])) {
            $add($jsonLd['embedUrl']);
        }

        $crawler->filter('#main iframe[src]')->each(function (Crawler $node) use ($add) {
            $add(trim($node->attr('src') ?? ''));
        });

        $crawler->filter('#main video source[src], #main video[src]')->each(function (Crawler $node) use ($add, $sourceUrl) {
            $src = trim($node->attr('src') ?? '');

            if ($src !== '') {
                $add($this->urlResolver->resolve($src, $sourceUrl));
            }
        });

        return $videos;
    }

    /**
     * @return list<string>
     */
    protected function extractDirectVideoUrls(Crawler $crawler, string $html, string $sourceUrl): array
    {
        $urls = [];

        $crawler->filter('#main video source[src], #main video[src]')->each(function (Crawler $node) use (&$urls, $sourceUrl) {
            $src = trim($node->attr('src') ?? '');

            if ($src !== '' && preg_match('/\.(mp4|webm|mov)(\?|$)/i', $src)) {
                $resolved = $this->urlResolver->resolve($src, $sourceUrl);

                if ($resolved) {
                    $urls[] = $resolved;
                }
            }
        });

        return array_values(array_unique($urls));
    }

    protected function isGalleryStillUrl(string $url): bool
    {
        $lower = strtolower($url);

        if (str_contains($lower, 'placeholder')
            || str_contains($lower, 'avatar')
            || str_contains($lower, '/logo')
            || str_contains($lower, '/static/')
            || str_contains($lower, 'favicon')
            || str_contains($lower, 'video.adsoftheworld.com')
        ) {
            return false;
        }

        if (str_contains($lower, 'image.adsoftheworld.com')) {
            return ! str_contains($lower, 'image.adsoftheworld.com/static/');
        }

        if (str_contains($lower, 'adsoftheworld.com/rails/active_storage')) {
            return true;
        }

        if (str_contains($lower, 'storage.googleapis.com')) {
            return true;
        }

        return (bool) preg_match('/\.(jpe?g|png|webp|gif|avif)(\?|#|$)/i', $lower);
    }

    protected function normalizeVideoUrl(string $url, string $sourceUrl): string
    {
        return $this->urlResolver->resolve($url, $sourceUrl) ?? $url;
    }

    protected function cleanOgTitle(string $title): string
    {
        $title = preg_replace('/\s*[•·|]\s*Ads of the World.*$/i', '', $title) ?? $title;

        return trim($title);
    }

    /**
     * @param  list<string|null>  $candidates
     */
    protected function firstNonEmpty(array $candidates): ?string
    {
        foreach ($candidates as $value) {
            $value = $this->normalizeText($value);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function normalizeText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return $value !== '' ? $value : null;
    }
}
