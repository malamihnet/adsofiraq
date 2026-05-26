<?php

namespace App\Services\Import;

use App\Models\Campaign;
use App\Models\CampaignAsset;
use App\Models\CampaignVideo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampaignImportMediaService
{
    public function __construct(
        protected CampaignImportMediaOptimizer $optimizer,
        protected CampaignImportVideoConverter $videoConverter,
    ) {}

    /**
     * @param  list<string>  $imageUrls
     * @return list<CampaignAsset>
     */
    public function importStills(Campaign $campaign, array $imageUrls): array
    {
        $assets = [];
        $sortOrder = 0;
        $seen = [];

        foreach ($imageUrls as $url) {
            $url = $this->resolveAbsoluteUrl($url);

            if ($url === null || isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;

            $path = $this->downloadAndOptimizeImage($campaign, $url);

            if ($path === null) {
                continue;
            }

            $assets[] = $campaign->assets()->create([
                'file_path' => $path,
                'file_type' => 'image',
                'sort_order' => ++$sortOrder,
            ]);
        }

        return $assets;
    }

    /**
     * @param  list<array{type: string, url: string}>  $videos
     * @param  list<string>  $directVideoUrls
     * @return list<CampaignVideo>
     */
    public function importVideos(
        Campaign $campaign,
        array $videos,
        array $directVideoUrls = [],
        bool $convertVideos = true,
    ): array {
        $created = [];
        $sortOrder = 0;
        $seen = [];

        foreach ($videos as $video) {
            $url = trim($video['url'] ?? '');

            if ($url === '' || isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;

            $created[] = $campaign->videos()->create([
                'type' => $video['type'],
                'url' => $url,
                'sort_order' => ++$sortOrder,
            ]);
        }

        foreach ($directVideoUrls as $url) {
            $url = $this->resolveAbsoluteUrl($url);

            if ($url === null || isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;

            $filePath = $this->downloadVideoFile($campaign, $url, $convertVideos);

            if ($filePath === null) {
                continue;
            }

            $created[] = $campaign->videos()->create([
                'type' => 'file',
                'file_path' => $filePath,
                'sort_order' => ++$sortOrder,
            ]);
        }

        return $created;
    }

    public function downloadThumbnail(Campaign $campaign, ?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        return $this->downloadAndOptimizeImage(
            $campaign,
            $this->resolveAbsoluteUrl($url) ?? $url,
            'campaigns/thumbnails'
        );
    }

    protected function downloadAndOptimizeImage(
        Campaign $campaign,
        string $url,
        string $directory = 'campaigns/assets',
    ): ?string {
        try {
            $response = $this->httpGet($url);

            if ($response === null) {
                return null;
            }

            $mime = strtolower($response->header('Content-Type') ?? '');
            $mime = strtok($mime, ';') ?: '';

            if (! in_array($mime, config('import.allowed_image_mimes', []), true)) {
                Log::warning('Campaign import: skipped image (invalid mime).', [
                    'campaign_id' => $campaign->id,
                    'url' => $url,
                    'mime' => $mime,
                ]);

                return null;
            }

            $body = $response->body();
            $maxBytes = config('import.max_image_bytes', 10 * 1024 * 1024);

            if (strlen($body) > $maxBytes) {
                Log::warning('Campaign import: skipped image (too large).', [
                    'campaign_id' => $campaign->id,
                    'url' => $url,
                ]);

                return null;
            }

            $path = $this->optimizer->storeImageAsWebp($body, $campaign, $directory);

            if ($path === null) {
                Log::warning('Campaign import: image WebP save failed.', [
                    'campaign_id' => $campaign->id,
                    'url' => $url,
                ]);
            }

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Campaign import: image download failed.', [
                'campaign_id' => $campaign->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function downloadVideoFile(Campaign $campaign, string $url, bool $convertVideos = true): ?string
    {
        try {
            $response = $this->httpGet($url);

            if ($response === null) {
                return null;
            }

            $mime = strtolower($response->header('Content-Type') ?? '');
            $mime = strtok($mime, ';') ?: '';

            if (! in_array($mime, config('import.allowed_video_mimes', []), true)) {
                Log::warning('Campaign import: skipped video (invalid mime).', [
                    'campaign_id' => $campaign->id,
                    'url' => $url,
                    'mime' => $mime,
                ]);

                return null;
            }

            $body = $response->body();
            $maxBytes = config('import.max_video_bytes', 200 * 1024 * 1024);

            if (strlen($body) > $maxBytes) {
                Log::warning('Campaign import: skipped video (too large).', [
                    'campaign_id' => $campaign->id,
                    'url' => $url,
                ]);

                return null;
            }

            $extension = $this->extensionFromMime($mime) ?? 'mp4';
            $filename = sprintf('import-c%d-%s.%s', $campaign->id, Str::random(12), $extension);
            $path = 'campaigns/videos/'.$filename;

            Storage::disk('public')->put($path, $body);

            if ($convertVideos && in_array($extension, ['mp4', 'mov'], true)) {
                return $this->videoConverter->convertToWebm($campaign, $path);
            }

            return $path;
        } catch (\Throwable $e) {
            Log::warning('Campaign import: video download failed.', [
                'campaign_id' => $campaign->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function httpGet(string $url): ?\Illuminate\Http\Client\Response
    {
        $response = Http::withHeaders([
            'User-Agent' => config('import.user_agent'),
            'Accept' => '*/*',
        ])
            ->timeout(config('import.timeout', 30))
            ->connectTimeout(15)
            ->withOptions(['allow_redirects' => true])
            ->get($url);

        if ($response->failed()) {
            return null;
        }

        return $response;
    }

    protected function extensionFromMime(string $mime): ?string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            default => null,
        };
    }

    protected function resolveAbsoluteUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return null;
        }

        return $url;
    }
}
