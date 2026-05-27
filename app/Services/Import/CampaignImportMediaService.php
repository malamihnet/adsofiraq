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
        protected CampaignImportImageUrlResolver $urlResolver,
    ) {}

    /**
     * @param  list<string>  $imageUrls
     * @return list<CampaignAsset>
     */
    public function importStills(Campaign $campaign, array $imageUrls): array
    {
        Log::info('Campaign import: still extraction complete, starting downloads.', [
            'campaign_id' => $campaign->id,
            'url_count' => count($imageUrls),
        ]);

        $assets = [];
        $sortOrder = (int) $campaign->assets()->max('sort_order');
        $seen = [];
        $stillIndex = 0;

        foreach ($imageUrls as $url) {
            $absolute = $this->resolveAbsoluteUrl($url, $campaign->source_url);

            if ($absolute === null || isset($seen[$absolute])) {
                continue;
            }

            $seen[$absolute] = true;
            $stillIndex++;
            $filename = 'still-'.$stillIndex.'.webp';
            $directory = $this->stillDirectory($campaign);

            Log::info('Campaign import: downloading still.', [
                'campaign_id' => $campaign->id,
                'index' => $stillIndex,
                'url' => $absolute,
            ]);

            $path = $this->downloadAndStoreImage($campaign, $absolute, $directory, $filename);

            if ($path === null) {
                continue;
            }

            $assets[] = $campaign->assets()->create([
                'file_path' => $path,
                'file_type' => 'image',
                'sort_order' => ++$sortOrder,
            ]);

            Log::info('Campaign import: still attached.', [
                'campaign_id' => $campaign->id,
                'path' => $path,
                'asset_id' => $assets[array_key_last($assets)]->id,
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
            $url = $this->resolveAbsoluteUrl($url, $campaign->source_url);

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

        $absolute = $this->resolveAbsoluteUrl($url, $campaign->source_url) ?? trim($url);

        Log::info('Campaign import: downloading thumbnail.', [
            'campaign_id' => $campaign->id,
            'url' => $absolute,
        ]);

        $path = $this->downloadAndStoreImage(
            $campaign,
            $absolute,
            $this->thumbnailDirectory($campaign),
            'thumbnail.webp',
        );

        if ($path) {
            Log::info('Campaign import: thumbnail saved.', [
                'campaign_id' => $campaign->id,
                'path' => $path,
            ]);
        }

        return $path;
    }

    protected function downloadAndStoreImage(
        Campaign $campaign,
        string $url,
        string $directory,
        string $filename,
    ): ?string {
        $body = $this->downloadImageBody($campaign, $url);

        if ($body === null) {
            return null;
        }

        $relativeWebp = trim($directory.'/'.$filename, '/');

        $path = $this->optimizer->storeImageAsWebpAtPath($body, $relativeWebp);

        if ($path !== null) {
            return $path;
        }

        $extension = $this->extensionFromMime($this->detectImageMime($body)) ?? 'jpg';
        $fallbackName = preg_replace('/\.webp$/i', '.'.$extension, $filename) ?? ($filename.'.'.$extension);
        $relativeFallback = trim($directory.'/'.$fallbackName, '/');

        Log::warning('Campaign import: WebP failed, storing original format.', [
            'campaign_id' => $campaign->id,
            'url' => $url,
            'path' => $relativeFallback,
        ]);

        return $this->optimizer->storeRawImageAtPath($body, $relativeFallback);
    }

    protected function downloadImageBody(Campaign $campaign, string $url): ?string
    {
        $maxBytes = (int) config('import.max_image_bytes', 10 * 1024 * 1024);
        $retries = max(1, (int) config('import.download_retries', 3));

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                $response = $this->httpGet($url, $campaign->source_url);

                if ($response === null) {
                    if ($attempt < $retries) {
                        usleep(250_000 * $attempt);
                    }

                    continue;
                }

                $body = $response->body();

                if ($body === '' || strlen($body) > $maxBytes) {
                    Log::warning('Campaign import: skipped image (empty or too large).', [
                        'campaign_id' => $campaign->id,
                        'url' => $url,
                        'bytes' => strlen($body),
                    ]);

                    return null;
                }

                if (! $this->isValidImageBody($body)) {
                    Log::warning('Campaign import: skipped image (invalid content).', [
                        'campaign_id' => $campaign->id,
                        'url' => $url,
                    ]);

                    return null;
                }

                $mime = $this->resolveAllowedImageMime(
                    strtolower($response->header('Content-Type') ?? ''),
                    $body,
                );

                if ($mime === null) {
                    Log::warning('Campaign import: skipped image (unsupported mime).', [
                        'campaign_id' => $campaign->id,
                        'url' => $url,
                        'header_mime' => $response->header('Content-Type'),
                    ]);

                    return null;
                }

                Log::info('Campaign import: image download success.', [
                    'campaign_id' => $campaign->id,
                    'url' => $url,
                    'mime' => $mime,
                    'bytes' => strlen($body),
                ]);

                return $body;
            } catch (\Throwable $e) {
                Log::warning('Campaign import: image download attempt failed.', [
                    'campaign_id' => $campaign->id,
                    'url' => $url,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $retries) {
                    usleep(250_000 * $attempt);
                }
            }
        }

        return null;
    }

    protected function downloadVideoFile(Campaign $campaign, string $url, bool $convertVideos = true): ?string
    {
        try {
            $response = $this->httpGet($url, $campaign->source_url);

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

    protected function httpGet(string $url, ?string $referer = null): ?\Illuminate\Http\Client\Response
    {
        $referer ??= config('import.referer', 'https://www.adsoftheworld.com/');

        $response = Http::withHeaders([
            'User-Agent' => config('import.user_agent'),
            'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            'Referer' => $referer,
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

    protected function stillDirectory(Campaign $campaign): string
    {
        return $this->campaignBasePath($campaign).'/assets';
    }

    protected function thumbnailDirectory(Campaign $campaign): string
    {
        return $this->campaignBasePath($campaign).'/thumbnails';
    }

    protected function campaignBasePath(Campaign $campaign): string
    {
        return config('upload.campaign_path', 'campaigns').'/'.$campaign->id;
    }

    protected function resolveAbsoluteUrl(string $url, ?string $pageUrl = null): ?string
    {
        return $this->urlResolver->resolve($url, $pageUrl);
    }

    protected function isValidImageBody(string $body): bool
    {
        return @getimagesizefromstring($body) !== false;
    }

    protected function detectImageMime(string $body): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($body);

        return is_string($mime) && $mime !== '' ? $mime : null;
    }

    protected function resolveAllowedImageMime(string $headerMime, string $body): ?string
    {
        $headerMime = strtok($headerMime, ';') ?: '';
        $detected = $this->detectImageMime($body) ?? '';
        $allowed = config('import.allowed_image_mimes', []);

        foreach ([$detected, $headerMime] as $mime) {
            if ($mime === '') {
                continue;
            }

            if (in_array($mime, $allowed, true)) {
                return $mime;
            }

            if ($mime === 'application/octet-stream' && $this->isValidImageBody($body)) {
                return $detected !== '' && in_array($detected, $allowed, true)
                    ? $detected
                    : 'image/jpeg';
            }
        }

        if ($this->isValidImageBody($body)) {
            $fallback = $detected !== '' ? $detected : 'image/jpeg';

            return in_array($fallback, $allowed, true) ? $fallback : 'image/jpeg';
        }

        return null;
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
}
