<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Log;

class AdsOfTheWorldCountryCrawler
{
    public function __construct(
        protected CampaignPageFetcher $fetcher,
        protected CampaignUrlNormalizer $urlNormalizer,
    ) {}

    /**
     * Discover campaigns oldest-first: last page → first page, last card → first card per page.
     *
     * @return array{
     *     entries: list<array{url: string, page: int, sort_order: int}>,
     *     max_page: int,
     *     pages_crawled: int
     * }
     */
    public function discoverCampaignUrls(string $countryPageUrl): array
    {
        $baseUrl = $this->normalizeCountryUrl($countryPageUrl);
        $firstHtml = $this->fetchCountryPage($baseUrl);
        $maxPage = $this->detectMaxPage($firstHtml, $baseUrl);

        $entries = [];
        $seen = [];
        $sortOrder = 0;

        for ($page = $maxPage; $page >= 1; $page--) {
            $pageUrl = $page === 1 ? $baseUrl : $baseUrl.'?page='.$page;
            $html = $page === 1 ? $firstHtml : $this->fetchCountryPage($pageUrl);

            $paths = array_reverse($this->extractCampaignPaths($html));
            $pageCount = 0;

            foreach ($paths as $path) {
                $url = $this->urlNormalizer->normalize('https://www.adsoftheworld.com'.$path);

                if (isset($seen[$url])) {
                    continue;
                }

                $seen[$url] = true;
                $entries[] = [
                    'url' => $url,
                    'page' => $page,
                    'sort_order' => $sortOrder++,
                ];
                $pageCount++;
            }

            Log::info('AOTW country crawl: page processed.', [
                'country_url' => $baseUrl,
                'page' => $page,
                'max_page' => $maxPage,
                'urls_on_page' => $pageCount,
                'queued_total' => count($entries),
            ]);
        }

        Log::info('AOTW country crawl finished.', [
            'country_url' => $baseUrl,
            'pages' => $maxPage,
            'campaigns' => count($entries),
            'order' => 'oldest_first',
        ]);

        return [
            'entries' => $entries,
            'max_page' => $maxPage,
            'pages_crawled' => $maxPage,
        ];
    }

    protected function fetchCountryPage(string $url): string
    {
        return $this->fetcher->fetch(
            $url,
            (int) config('import.country_page_timeout', 15),
            (int) config('import.country_page_retries', 3),
        );
    }

    protected function normalizeCountryUrl(string $url): string
    {
        $url = trim($url);

        if (! str_starts_with($url, 'http')) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            throw new \InvalidArgumentException('Invalid country page URL.');
        }

        $path = $parts['path'] ?? '/countries/iraq';

        if (! str_contains(strtolower($path), '/countries/')) {
            throw new \InvalidArgumentException('URL must be an Ads of the World country listing page.');
        }

        return 'https://www.adsoftheworld.com'.rtrim($path, '/');
    }

    protected function detectMaxPage(string $html, string $baseUrl): int
    {
        $max = 1;

        if (preg_match_all('#/countries/[^"\']+\?page=(\d+)#', $html, $matches)) {
            foreach ($matches[1] as $page) {
                $max = max($max, (int) $page);
            }
        }

        if (preg_match('#href=["\']([^"\']+\?page=(\d+))["\'][^>]*>Last#i', $html, $match)) {
            $max = max($max, (int) $match[2]);
        }

        return max(1, min($max, (int) config('import.max_country_pages', 500)));
    }

    /**
     * @return list<string>
     */
    protected function extractCampaignPaths(string $html): array
    {
        $paths = [];

        if (! preg_match_all('#href=["\'](/campaigns/[^"\']+)#i', $html, $matches)) {
            return [];
        }

        foreach ($matches[1] as $path) {
            $path = strtok($path, '?') ?: $path;

            if ($path === '/campaigns/new' || str_starts_with($path, '/campaigns/new/')) {
                continue;
            }

            if (preg_match('#^/campaigns/[a-z0-9\-]+#i', $path)) {
                $paths[] = $path;
            }
        }

        return array_values(array_unique($paths));
    }
}
