<?php

namespace App\Services\Import;

use App\Services\VideoUrlParser;
use Symfony\Component\DomCrawler\Crawler;

class CampaignPageParser
{
    public function __construct(
        protected CampaignCreditsExtractor $creditsExtractor,
    ) {}

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     credits: ?string,
     *     canonical_url: ?string,
     *     og_image: ?string,
     *     brands: list<string>,
     *     agencies: list<string>,
     *     countries: list<string>,
     *     industries: list<string>,
     *     medium_types: list<string>,
     *     videos: list<array{type: string, url: string}>,
     *     image_urls: list<string>,
     *     direct_video_urls: list<string>,
     * }
     */
    public function parse(string $html, string $sourceUrl): array
    {
        $crawler = new Crawler($html, $sourceUrl);
        $host = parse_url($sourceUrl, PHP_URL_HOST) ?? '';

        $meta = $this->parseMetaTags($crawler);
        $jsonLd = $this->parseJsonLd($html);

        $isAotw = str_contains(strtolower($host), 'adsoftheworld.com');
        $aotw = $isAotw ? $this->parseAdsOfTheWorld($crawler) : [];

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

        $videos = $this->extractVideos($crawler, $html, $jsonLd);
        $imageUrls = array_values(array_unique(array_filter(array_merge(
            $aotw['image_urls'] ?? [],
            $meta['og:image'] ? [$meta['og:image']] : [],
            $this->extractImageUrls($crawler, $isAotw),
        ))));

        $directVideos = $this->extractDirectVideoUrls($crawler, $html);

        $credits = $this->creditsExtractor->extract($crawler, $html, $sourceUrl, $isAotw);

        return [
            'title' => $title ?? 'Imported Campaign',
            'description' => $description ?? '',
            'credits' => $credits,
            'canonical_url' => $meta['canonical'] ?? $sourceUrl,
            'og_image' => $meta['og:image'] ?? null,
            'brands' => array_values(array_unique(array_filter($aotw['brands'] ?? []))),
            'agencies' => array_values(array_unique(array_filter($aotw['agencies'] ?? []))),
            'countries' => array_values(array_unique(array_filter($aotw['countries'] ?? []))),
            'industries' => array_values(array_unique(array_filter($aotw['industries'] ?? []))),
            'medium_types' => array_values(array_unique(array_filter($aotw['medium_types'] ?? []))),
            'videos' => $videos,
            'image_urls' => $imageUrls,
            'direct_video_urls' => $directVideos,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    protected function parseMetaTags(Crawler $crawler): array
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

        return $meta;
    }

    /**
     * @return array{name: ?string, description: ?string, embedUrl: ?string, thumbnailUrl: ?string}
     */
    protected function parseJsonLd(string $html): array
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

                if (! in_array($type, ['VideoObject', 'Article', 'CreativeWork'], true)) {
                    continue;
                }

                $result['name'] ??= $this->normalizeText($item['name'] ?? null);
                $result['description'] ??= $this->normalizeText($item['description'] ?? null);
                $result['embedUrl'] ??= $item['embedUrl'] ?? $item['contentUrl'] ?? null;
                $result['thumbnailUrl'] ??= $item['thumbnailUrl'] ?? null;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseAdsOfTheWorld(Crawler $crawler): array
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

        if ($main->count()) {
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

            $main->filter('source[srcset]')->each(function (Crawler $node) use (&$data) {
                $srcset = trim($node->attr('srcset') ?? '');

                if ($srcset !== '' && $this->isCampaignImageUrl($srcset)) {
                    $data['image_urls'][] = $this->absoluteUrl($srcset);
                }
            });

            $main->filter('img[src]')->each(function (Crawler $node) use (&$data) {
                $src = trim($node->attr('src') ?? '');

                if ($src !== '' && $this->isCampaignImageUrl($src)) {
                    $data['image_urls'][] = $this->absoluteUrl($src);
                }
            });
        }

        $summary = $crawler->filter('#main p.text-sm')->first();

        if ($summary->count()) {
            $this->parseAotwSummaryLine($summary->text(), $data);
        }

        return $data;
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
    protected function extractVideos(Crawler $crawler, string $html, array $jsonLd): array
    {
        $videos = [];
        $seen = [];

        $add = function (?string $url) use (&$videos, &$seen) {
            if ($url === null || $url === '') {
                return;
            }

            $url = $this->normalizeVideoUrl($url);
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

        $crawler->filter('iframe[src]')->each(function (Crawler $node) use ($add) {
            $add(trim($node->attr('src') ?? ''));
        });

        if (preg_match_all('/https?:\/\/(?:www\.)?(?:youtube\.com\/[^\s"\'<>]+|youtu\.be\/[^\s"\'<>]+|vimeo\.com\/[^\s"\'<>]+)/i', $html, $matches)) {
            foreach ($matches[0] as $url) {
                $add($url);
            }
        }

        return $videos;
    }

    /**
     * @return list<string>
     */
    protected function extractDirectVideoUrls(Crawler $crawler, string $html): array
    {
        $urls = [];

        $pattern = '/https?:\/\/[^\s"\'<>]+\.(?:mp4|webm|mov)(?:\?[^\s"\'<>]*)?/i';

        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[0] as $url) {
                $urls[] = $url;
            }
        }

        $crawler->filter('video source[src], video[src]')->each(function (Crawler $node) use (&$urls) {
            $src = trim($node->attr('src') ?? '');

            if ($src !== '' && preg_match('/\.(mp4|webm|mov)(\?|$)/i', $src)) {
                $urls[] = $this->absoluteUrl($src);
            }
        });

        return array_values(array_unique($urls));
    }

    /**
     * @return list<string>
     */
    /**
     * @return list<string>
     */
    protected function extractImageUrls(Crawler $crawler, bool $restrictToMain): array
    {
        $urls = [];
        $scope = $restrictToMain && $crawler->filter('#main')->count()
            ? $crawler->filter('#main')
            : $crawler;

        $scope->filter('img[src]')->each(function (Crawler $node) use (&$urls) {
            $src = trim($node->attr('src') ?? '');

            if ($src !== '' && $this->looksLikeContentImage($src)) {
                $urls[] = $this->absoluteUrl($src);
            }
        });

        return $urls;
    }

    protected function isCampaignImageUrl(string $url): bool
    {
        $lower = strtolower($url);

        if (str_contains($lower, 'placeholder') || str_contains($lower, 'avatar') || str_contains($lower, 'logo')) {
            return false;
        }

        return str_contains($lower, 'image.adsoftheworld.com')
            || str_contains($lower, 'storage.googleapis.com')
            || str_contains($lower, 'adsoftheworld.com/rails/active_storage')
            || preg_match('/\.(jpe?g|png|webp|gif)(\?|$)/i', $lower);
    }

    protected function looksLikeContentImage(string $url): bool
    {
        $lower = strtolower($url);

        if (str_contains($lower, 'icon') || str_contains($lower, 'logo') || str_contains($lower, 'sprite')) {
            return false;
        }

        return (bool) preg_match('/\.(jpe?g|png|webp|gif)(\?|$)/i', $lower)
            || str_contains($lower, 'image.adsoftheworld.com');
    }

    protected function normalizeVideoUrl(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return $this->absoluteUrl($url);
    }

    protected function absoluteUrl(string $url): string
    {
        $url = trim($url);

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return $url;
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
