<?php

namespace App\Services\Import;

class CampaignListingParser
{
    /**
     * Extract unique campaign paths from an Ads of the World listing page.
     *
     * @return list<string> Paths like /campaigns/some-slug
     */
    public function extractCampaignPaths(string $html): array
    {
        $paths = [];

        $patterns = [
            '#href=["\'](/campaigns/[^"\']+)#i',
            '#href=["\'](?:https?:)?//(?:www\.)?adsoftheworld\.com(/campaigns/[^"\']+)#i',
            '#href=["\'](?:https?:)?//adsoftheworld\.com(/campaigns/[^"\']+)#i',
            '#data-href=["\'](/campaigns/[^"\']+)#i',
            '#data-href=["\'](?:https?:)?//(?:www\.)?adsoftheworld\.com(/campaigns/[^"\']+)#i',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match_all($pattern, $html, $matches)) {
                continue;
            }

            foreach ($matches[1] as $path) {
                $normalized = $this->normalizeCampaignPath($path);

                if ($normalized !== null) {
                    $paths[] = $normalized;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    public function detectMaxPage(string $html): int
    {
        $max = 1;

        if (preg_match_all('#[?&]page=(\d+)#', $html, $matches)) {
            foreach ($matches[1] as $page) {
                $max = max($max, (int) $page);
            }
        }

        if (preg_match_all('#/countries/[^"\']+\?page=(\d+)#', $html, $matches)) {
            foreach ($matches[1] as $page) {
                $max = max($max, (int) $page);
            }
        }

        if (preg_match('#href=["\']([^"\']+\?page=(\d+))["\'][^>]*>\s*Last#i', $html, $match)) {
            $max = max($max, (int) $match[2]);
        }

        return max(1, min($max, (int) config('import.max_country_pages', 500)));
    }

    protected function normalizeCampaignPath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (! str_starts_with($path, '/')) {
            $path = '/'.ltrim($path, '/');
        }

        $path = strtok($path, '?') ?: $path;
        $path = strtok($path, '#') ?: $path;
        $path = rtrim($path, '/') ?: $path;

        if ($path === '/campaigns/new' || str_starts_with($path, '/campaigns/new/')) {
            return null;
        }

        if (preg_match('#^/campaigns/(?:countries|tags|medium|search|brands|agencies)(?:/|$)#i', $path)) {
            return null;
        }

        if (preg_match('#^/campaigns/[a-z0-9\-]+(?:/[a-z0-9\-]+)?$#i', $path)) {
            return $path;
        }

        return null;
    }
}
