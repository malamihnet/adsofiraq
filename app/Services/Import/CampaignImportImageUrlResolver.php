<?php

namespace App\Services\Import;

class CampaignImportImageUrlResolver
{
    /**
     * Resolve a possibly relative or protocol-relative image URL against a campaign page URL.
     */
    public function resolve(?string $url, ?string $pageUrl = null): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));

        if ($url === '' || str_starts_with(strtolower($url), 'data:')) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return $this->cleanUrl('https:'.$url);
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $this->cleanUrl($url);
        }

        $base = $this->baseFromPageUrl($pageUrl);

        if (str_starts_with($url, '/')) {
            return $this->cleanUrl(rtrim($base, '/').$url);
        }

        return $this->cleanUrl(rtrim($base, '/').'/'.$url);
    }

    /**
     * Pick the widest candidate from an HTML srcset attribute value.
     */
    public function bestFromSrcset(string $srcset, ?string $pageUrl = null): ?string
    {
        $srcset = trim($srcset);

        if ($srcset === '') {
            return null;
        }

        $candidates = [];

        foreach (preg_split('/\s*,\s*/', $srcset) as $part) {
            $part = trim($part);

            if ($part === '') {
                continue;
            }

            if (preg_match('/^(\S+)\s+(\d+)w$/i', $part, $matches)) {
                $candidates[(int) $matches[2]] = $matches[1];

                continue;
            }

            if (preg_match('/^(\S+)\s+([\d.]+)x$/i', $part, $matches)) {
                $candidates[(int) round((float) $matches[2] * 1000)] = $matches[1];

                continue;
            }

            if (preg_match('/^(\S+)/', $part, $matches)) {
                $candidates[0] = $matches[1];
            }
        }

        if ($candidates === []) {
            return $this->resolve($srcset, $pageUrl);
        }

        ksort($candidates);

        $resolved = [];

        foreach ($candidates as $candidate) {
            $url = $this->resolve((string) $candidate, $pageUrl);

            if ($url !== null) {
                $resolved[] = $url;
            }
        }

        return $this->preferOriginalFormat($resolved);
    }

    /**
     * Prefer jpg/png over webp when multiple formats exist for the same asset.
     *
     * @param  list<string>  $urls
     */
    public function preferOriginalFormat(array $urls): ?string
    {
        $preferred = [];
        $fallback = [];

        foreach ($urls as $url) {
            if (preg_match('/\.webp(\?|#|$)/i', $url)) {
                $fallback[] = $url;
            } else {
                $preferred[] = $url;
            }
        }

        if ($preferred !== []) {
            return $preferred[array_key_last($preferred)];
        }

        return $fallback !== [] ? $fallback[array_key_last($fallback)] : null;
    }

    protected function baseFromPageUrl(?string $pageUrl): string
    {
        if ($pageUrl) {
            $scheme = parse_url($pageUrl, PHP_URL_SCHEME);
            $host = parse_url($pageUrl, PHP_URL_HOST);

            if ($scheme && $host) {
                $port = parse_url($pageUrl, PHP_URL_PORT);

                return $scheme.'://'.$host.($port ? ':'.$port : '');
            }
        }

        return 'https://www.adsoftheworld.com';
    }

    protected function cleanUrl(string $url): string
    {
        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return $url;
        }

        if (empty($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $query);

        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid'] as $key) {
            unset($query[$key]);
        }

        $rebuilt = ($parts['scheme'] ?? 'https').'://'.$parts['host'];
        $rebuilt .= isset($parts['port']) ? ':'.$parts['port'] : '';
        $rebuilt .= $parts['path'] ?? '';

        if ($query !== []) {
            $rebuilt .= '?'.http_build_query($query);
        }

        if (! empty($parts['fragment'])) {
            $rebuilt .= '#'.$parts['fragment'];
        }

        return $rebuilt;
    }
}
