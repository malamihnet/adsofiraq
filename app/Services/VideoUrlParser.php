<?php

namespace App\Services;

class VideoUrlParser
{
    public static function parse(?string $url): ?array
    {
        if (empty($url)) {
            return null;
        }

        $url = trim($url);

        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return [
                'provider' => 'youtube',
                'video_id' => $matches[1],
                'embed_url' => 'https://www.youtube.com/embed/'.$matches[1],
            ];
        }

        if (preg_match('/player\.vimeo\.com\/video\/(\d+)/', $url, $matches)) {
            return [
                'provider' => 'vimeo',
                'video_id' => $matches[1],
                'embed_url' => 'https://player.vimeo.com/video/'.$matches[1],
            ];
        }

        if (preg_match('/vimeo\.com\/(?:channels\/[^\/]+\/|groups\/[^\/]+\/videos\/|video\/)?(\d+)/', $url, $matches)) {
            return [
                'provider' => 'vimeo',
                'video_id' => $matches[1],
                'embed_url' => 'https://player.vimeo.com/video/'.$matches[1],
            ];
        }

        return null;
    }

    public static function parseYouTube(?string $url): ?array
    {
        $parsed = self::parse($url);

        return ($parsed['provider'] ?? null) === 'youtube' ? $parsed : null;
    }

    public static function parseVimeo(?string $url): ?array
    {
        $parsed = self::parse($url);

        return ($parsed['provider'] ?? null) === 'vimeo' ? $parsed : null;
    }
}
