<?php

namespace App\Services\Import;

class CampaignUrlNormalizer
{
    public function normalize(string $url): string
    {
        $url = trim($url);

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            throw new \InvalidArgumentException('Invalid URL');
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host']);
        $host = preg_replace('/^www\./', '', $host);
        $path = $parts['path'] ?? '/';
        $path = rtrim($path, '/') ?: '/';

        $normalized = $scheme.'://'.$host.$path;

        return strlen($normalized) > 512 ? substr($normalized, 0, 512) : $normalized;
    }

    public function isValidHttpUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '') {
            return false;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            if (! filter_var('https://'.$url, FILTER_VALIDATE_URL)) {
                return false;
            }
        }

        $parts = parse_url(str_starts_with($url, 'http') ? $url : 'https://'.$url);

        return in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && ! empty($parts['host']);
    }
}
