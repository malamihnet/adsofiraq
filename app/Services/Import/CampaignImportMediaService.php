<?php

namespace App\Services\Import;

use App\Models\Campaign;
use App\Models\CampaignAsset;
use App\Models\CampaignVideo;
use App\Services\CampaignMediaDeduplicationService;
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
        protected CampaignMediaDeduplicationService $mediaDedup,
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
        $this->mediaDedup->backfillStillMetadata($campaign->assets()->get());
        $sortOrder = (int) $campaign->assets()->max('sort_order');
        $seenUrls = [];

        foreach ($imageUrls as $url) {
            $absolute = $this->resolveAbsoluteUrl($url, $campaign->source_url);

            if ($absolute === null || isset($seenUrls[$absolute])) {
                continue;
            }

            $seenUrls[$absolute] = true;

            Log::info('Campaign import: downloading still.', [
                'campaign_id' => $campaign->id,
                'url' => $absolute,
            ]);

            $body = $this->downloadImageBody($campaign, $absolute);

            if ($body === null) {
                continue;
            }

            $contentHash = $this->mediaDedup->visualContentHash($body);

            if ($contentHash === null) {
                continue;
            }

            $stillIndex = $this->nextStillIndex($campaign);
            $extension = $this->extensionForImage($body, $absolute);
            $directory = $this->stillDirectory($campaign);
            $filename = 'still-'.$stillIndex.'.'.$extension;
            $relativePath = trim($directory.'/'.$filename, '/');

            if ($this->mediaDedup->stillImportExists($campaign, $absolute, $contentHash, $relativePath)) {
                $this->mediaDedup->logDuplicateSkipped($campaign, 'still', [
                    'source_url' => $absolute,
                    'content_hash' => $contentHash,
                    'file_path' => $relativePath,
                ]);

                continue;
            }

            $path = $this->storeImageBody($campaign, $body, $absolute, $directory, $filename);

            if ($path === null) {
                continue;
            }

            $sourceKey = $this->mediaDedup->sourceUrlKey($absolute, $campaign->source_url);

            $assets[] = $campaign->assets()->create([
                'file_path' => $path,
                'file_type' => 'image',
                'sort_order' => ++$sortOrder,
                'source_url' => $absolute,
                'source_url_key' => $sourceKey,
                'content_hash' => $contentHash,
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
        $sortOrder = (int) $campaign->videos()->max('sort_order');
        $this->mediaDedup->backfillVideoMetadata($campaign->videos()->get());
        $seenKeys = [];

        foreach ($videos as $video) {
            $type = strtolower(trim($video['type'] ?? ''));
            $url = trim($video['url'] ?? '');

            if ($url === '' || ! in_array($type, ['youtube', 'vimeo'], true)) {
                continue;
            }

            $embedKey = $this->mediaDedup->videoEmbedKey($type, $url);

            if ($embedKey === null || isset($seenKeys[$embedKey])) {
                continue;
            }

            if ($this->mediaDedup->videoImportExists($campaign, $type, $url)) {
                $this->mediaDedup->logDuplicateSkipped($campaign, 'video', [
                    'type' => $type,
                    'url' => $url,
                    'embed_key' => $embedKey,
                ]);

                continue;
            }

            $seenKeys[$embedKey] = true;

            $created[] = $campaign->videos()->create([
                'type' => $type,
                'url' => $url,
                'embed_key' => $embedKey,
                'source_url_key' => $this->mediaDedup->sourceUrlKey($url, $campaign->source_url),
                'sort_order' => ++$sortOrder,
            ]);
        }

        foreach ($directVideoUrls as $url) {
            $url = $this->resolveAbsoluteUrl($url, $campaign->source_url);

            if ($url === null) {
                continue;
            }

            $sourceKey = $this->mediaDedup->sourceUrlKey($url, $campaign->source_url);

            if ($sourceKey !== null && isset($seenKeys['file:url:'.$sourceKey])) {
                continue;
            }

            $filePath = $this->downloadVideoFile($campaign, $url, $convertVideos);

            if ($filePath === null) {
                continue;
            }

            $fileHash = $this->mediaDedup->resolveVideoFileHash($filePath);

            if ($fileHash !== null && $this->mediaDedup->videoImportExists($campaign, 'file', $url, $fileHash)) {
                $this->mediaDedup->logDuplicateSkipped($campaign, 'video_file', [
                    'url' => $url,
                    'content_hash' => $fileHash,
                ]);

                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }

                continue;
            }

            if ($sourceKey !== null) {
                $seenKeys['file:url:'.$sourceKey] = true;
            }

            if ($fileHash !== null) {
                $seenKeys['file:hash:'.$fileHash] = true;
            }

            $created[] = $campaign->videos()->create([
                'type' => 'file',
                'file_path' => $filePath,
                'url' => $url,
                'content_hash' => $fileHash,
                'source_url_key' => $sourceKey,
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

        $body = $this->downloadImageBody($campaign, $absolute);

        if ($body === null) {
            return null;
        }

        $extension = $this->extensionForImage($body, $absolute);
        $path = $this->storeImageBody(
            $campaign,
            $body,
            $absolute,
            $this->thumbnailDirectory($campaign),
            'thumbnail.'.$extension,
        );

        if ($path) {
            Log::info('Campaign import: thumbnail saved.', [
                'campaign_id' => $campaign->id,
                'path' => $path,
            ]);
        }

        return $path;
    }

    protected function storeImageBody(
        Campaign $campaign,
        string $body,
        string $sourceUrl,
        string $directory,
        string $filename,
    ): ?string {
        $extension = $this->extensionForImage($body, $sourceUrl);
        $filename = $this->filenameWithExtension($filename, $extension);
        $relativePath = trim($directory.'/'.$filename, '/');

        return $this->optimizer->storeImageAtPath($body, $relativePath);
    }

    protected function filenameWithExtension(string $filename, string $extension): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        return $base.'.'.ltrim($extension, '.');
    }

    protected function extensionForImage(string $body, string $sourceUrl): string
    {
        $mime = $this->detectImageMime($body);
        $fromMime = $mime !== null ? $this->extensionFromMime($mime) : null;

        if ($fromMime !== null) {
            return $fromMime;
        }

        if (preg_match('/\.(jpe?g|png|webp|gif)(\?|#|$)/i', $sourceUrl, $matches)) {
            $ext = strtolower($matches[1]);

            return $ext === 'jpeg' ? 'jpg' : $ext;
        }

        return 'jpg';
    }

    protected function nextStillIndex(Campaign $campaign): int
    {
        $max = 0;

        foreach ($campaign->assets()->pluck('file_path') as $path) {
            if (preg_match('/still-(\d+)\./i', (string) $path, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max + 1;
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
