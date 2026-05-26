<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Fetches video provider thumbnails (YouTube/Vimeo), optimizes them as WebP,
 * and stores them on the public disk.
 */
class VideoThumbnailService
{
    protected const HTTP_TIMEOUT = 10;

    protected const USER_AGENT = 'AdsOfIraq/1.0 (Laravel; thumbnail-fetcher)';

    protected const THUMBNAILS_DIR = 'campaigns/thumbnails';

    protected const THUMB_WIDTH = 1280;

    protected const THUMB_HEIGHT = 720;

    protected const WEBP_QUALITY = 82;

    protected ?ImageManager $imageManager = null;

    /**
     * Resolve the video provider from a URL or stored provider value.
     */
    public function detectProvider(?string $videoUrl, ?string $knownProvider = null): ?string
    {
        if ($knownProvider && in_array($knownProvider, ['youtube', 'vimeo'], true)) {
            return $knownProvider;
        }

        return VideoUrlParser::parse($videoUrl)['provider'] ?? null;
    }

    /**
     * Whether a campaign still needs an auto-fetched thumbnail.
     */
    public function needsThumbnail(Campaign $campaign): bool
    {
        $remote = $campaign->firstRemoteVideoForThumbnail();

        if ($remote === null) {
            return false;
        }

        if (! $this->detectProvider($remote['url'], $remote['provider'])) {
            return false;
        }

        $path = $this->normalizeStoragePath($campaign->thumbnail_path);

        if ($path === null) {
            return true;
        }

        return Storage::disk('public')->missing($path);
    }

    /**
     * Whether the path is an auto-managed thumbnail under campaigns/thumbnails/.
     */
    public function isManagedThumbnailPath(?string $path): bool
    {
        $normalized = $this->normalizeStoragePath($path);

        if ($normalized === null) {
            return false;
        }

        return str_starts_with($normalized, self::THUMBNAILS_DIR.'/');
    }

    /**
     * Delete a managed thumbnail file (campaigns/thumbnails/*) if it exists.
     */
    public function deleteManagedThumbnail(?string $path): bool
    {
        $normalized = $this->normalizeStoragePath($path);

        if ($normalized === null || ! $this->isManagedThumbnailPath($normalized)) {
            return false;
        }

        if (Storage::disk('public')->exists($normalized)) {
            Storage::disk('public')->delete($normalized);

            Log::info('Video thumbnail: deleted old managed file.', [
                'path' => $normalized,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Resolve a remote thumbnail URL from YouTube or Vimeo (without downloading).
     */
    public function getThumbnailUrl(?string $videoUrl, ?string $provider = null): ?string
    {
        $parsed = VideoUrlParser::parse($videoUrl);

        if (! $parsed) {
            return null;
        }

        $provider = $this->detectProvider($videoUrl, $provider ?? $parsed['provider']);

        return match ($provider) {
            'youtube' => $this->resolveYouTubeThumbnailUrl($parsed['video_id']),
            'vimeo' => $this->resolveVimeoThumbnailUrl($videoUrl),
            default => null,
        };
    }

    /**
     * Download a provider thumbnail, optimize as WebP, and store on the public disk.
     */
    public function downloadAndStoreThumbnail(Campaign $campaign, ?string $videoUrl = null, ?string $provider = null): ?string
    {
        $videoUrl = $videoUrl ?? $campaign->video_url;
        $provider = $this->detectProvider($videoUrl, $provider ?? $campaign->video_provider);

        Log::info('Video thumbnail: starting fetch.', [
            'campaign_id' => $campaign->id,
            'video_url' => $videoUrl,
            'detected_provider' => $provider,
        ]);

        if (! $provider) {
            Log::warning('Video thumbnail: unsupported or unparseable video URL.', [
                'campaign_id' => $campaign->id,
                'video_url' => $videoUrl,
            ]);

            return null;
        }

        try {
            return match ($provider) {
                'youtube' => $this->downloadYouTubeThumbnail($campaign, $videoUrl),
                'vimeo' => $this->downloadVimeoThumbnail($campaign, $videoUrl),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Video thumbnail: unhandled exception.', [
                'campaign_id' => $campaign->id,
                'video_url' => $videoUrl,
                'detected_provider' => $provider,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convert an existing stored thumbnail (JPG/PNG) to optimized WebP.
     */
    public function convertExistingToWebp(Campaign $campaign): ?string
    {
        $sourcePath = $this->normalizeStoragePath($campaign->thumbnail_path);

        if ($sourcePath === null || Storage::disk('public')->missing($sourcePath)) {
            Log::warning('Video thumbnail: cannot convert — source missing.', [
                'campaign_id' => $campaign->id,
                'thumbnail_path' => $campaign->thumbnail_path,
            ]);

            return null;
        }

        if (str_ends_with(strtolower($sourcePath), '.webp')) {
            Log::info('Video thumbnail: already WebP, skipping conversion.', [
                'campaign_id' => $campaign->id,
                'path' => $sourcePath,
            ]);

            return $sourcePath;
        }

        if (! $this->isConvertibleImagePath($sourcePath)) {
            Log::warning('Video thumbnail: source is not a convertible raster image.', [
                'campaign_id' => $campaign->id,
                'path' => $sourcePath,
            ]);

            return null;
        }

        $binary = Storage::disk('public')->get($sourcePath);
        $newPath = $this->storeOptimizedThumbnail($campaign, $binary, 'local-conversion');

        if ($newPath && $newPath !== $sourcePath && $this->isManagedThumbnailPath($sourcePath)) {
            Storage::disk('public')->delete($sourcePath);
        }

        return $newPath;
    }

    /**
     * Apply an auto-fetched thumbnail when no manual upload exists.
     */
    public function applyFallbackIfNeeded(Campaign $campaign, bool $manualUploadProvided = false): void
    {
        if ($manualUploadProvided) {
            return;
        }

        if (! $this->needsThumbnail($campaign)) {
            return;
        }

        $remote = $campaign->firstRemoteVideoForThumbnail();

        if ($remote === null) {
            return;
        }

        $path = $this->downloadAndStoreThumbnail($campaign, $remote['url'], $remote['provider']);

        if ($path) {
            $campaign->thumbnail_path = $path;
            $campaign->save();

            Log::info('Video thumbnail: campaign updated.', [
                'campaign_id' => $campaign->id,
                'thumbnail_path' => $path,
            ]);
        }
    }

    public function placeholderUrl(): string
    {
        $webp = config('upload.placeholder', 'images/placeholder.webp');

        if (file_exists(public_path($webp))) {
            return asset($webp);
        }

        return asset(config('upload.placeholder_fallback', 'images/placeholder.jpg'));
    }

    protected function downloadYouTubeThumbnail(Campaign $campaign, string $videoUrl): ?string
    {
        $parsed = VideoUrlParser::parse($videoUrl);

        if (! $parsed || empty($parsed['video_id'])) {
            Log::warning('Video thumbnail: YouTube video ID not found.', [
                'campaign_id' => $campaign->id,
                'video_url' => $videoUrl,
            ]);

            return null;
        }

        $videoId = $parsed['video_id'];
        $candidates = [
            "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg",
            "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
        ];

        foreach ($candidates as $remoteUrl) {
            $path = $this->downloadImageFromUrl($campaign, $remoteUrl, 'youtube');

            if ($path) {
                return $path;
            }
        }

        Log::error('Video thumbnail: all YouTube candidates failed.', [
            'campaign_id' => $campaign->id,
            'video_url' => $videoUrl,
            'video_id' => $videoId,
            'candidates' => $candidates,
        ]);

        return null;
    }

    protected function downloadVimeoThumbnail(Campaign $campaign, string $videoUrl): ?string
    {
        $remoteUrl = $this->resolveVimeoThumbnailUrl($videoUrl);

        if (! $remoteUrl) {
            Log::error('Video thumbnail: Vimeo oEmbed did not return thumbnail_url.', [
                'campaign_id' => $campaign->id,
                'video_url' => $videoUrl,
            ]);

            return null;
        }

        return $this->downloadImageFromUrl($campaign, $remoteUrl, 'vimeo');
    }

    /**
     * Download remote image bytes, optimize to WebP 1280×720 (cover crop), and save.
     */
    protected function downloadImageFromUrl(Campaign $campaign, string $remoteUrl, string $provider): ?string
    {
        Log::info('Video thumbnail: attempting download.', [
            'campaign_id' => $campaign->id,
            'detected_provider' => $provider,
            'remote_thumbnail_url' => $remoteUrl,
        ]);

        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'image/*',
                ])
                ->get($remoteUrl);

            $status = $response->status();
            $contentType = (string) $response->header('Content-Type', '');

            Log::info('Video thumbnail: HTTP response received.', [
                'campaign_id' => $campaign->id,
                'remote_thumbnail_url' => $remoteUrl,
                'http_status' => $status,
                'content_type' => $contentType,
                'body_bytes' => strlen($response->body()),
            ]);

            if (! $response->successful()) {
                return null;
            }

            if (! $this->isImageResponse($contentType, $response->body())) {
                Log::warning('Video thumbnail: response is not an image.', [
                    'campaign_id' => $campaign->id,
                    'remote_thumbnail_url' => $remoteUrl,
                    'content_type' => $contentType,
                ]);

                return null;
            }

            return $this->storeOptimizedThumbnail($campaign, $response->body(), $provider);
        } catch (\Throwable $e) {
            Log::error('Video thumbnail: download failed.', [
                'campaign_id' => $campaign->id,
                'remote_thumbnail_url' => $remoteUrl,
                'detected_provider' => $provider,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Resize/crop to 16:9 (1280×720 cover) and save as WebP quality 82.
     * Falls back to original JPG if WebP encoding fails.
     */
    protected function storeOptimizedThumbnail(Campaign $campaign, string $imageBinary, string $context): ?string
    {
        Storage::disk('public')->makeDirectory(self::THUMBNAILS_DIR);

        try {
            $image = $this->imageManager()->decodeBinary($imageBinary);
            $image->cover(self::THUMB_WIDTH, self::THUMB_HEIGHT);

            $filename = sprintf('campaign-%d-%s.webp', $campaign->id, now()->timestamp);
            $path = self::THUMBNAILS_DIR.'/'.$filename;
            $encoded = $image->encode(new WebpEncoder(self::WEBP_QUALITY));

            Storage::disk('public')->put($path, (string) $encoded);

            if (Storage::disk('public')->missing($path)) {
                throw new \RuntimeException('WebP file was not written to disk.');
            }

            Log::info('Video thumbnail: WebP saved.', [
                'campaign_id' => $campaign->id,
                'context' => $context,
                'save_path' => $path,
                'output_bytes' => Storage::disk('public')->size($path),
                'dimensions' => self::THUMB_WIDTH.'x'.self::THUMB_HEIGHT,
                'webp_quality' => self::WEBP_QUALITY,
            ]);

            return $path;
        } catch (\Throwable $e) {
            Log::error('Video thumbnail: WebP conversion failed, falling back to JPG.', [
                'campaign_id' => $campaign->id,
                'context' => $context,
                'exception' => $e->getMessage(),
            ]);

            return $this->storeFallbackJpg($campaign, $imageBinary, $context);
        }
    }

    /**
     * Save the original downloaded bytes as JPG when WebP conversion is unavailable.
     */
    protected function storeFallbackJpg(Campaign $campaign, string $imageBinary, string $context): ?string
    {
        try {
            $image = $this->imageManager()->decodeBinary($imageBinary);
            $image->cover(self::THUMB_WIDTH, self::THUMB_HEIGHT);

            $filename = sprintf('campaign-%d-%s.jpg', $campaign->id, now()->timestamp);
            $path = self::THUMBNAILS_DIR.'/'.$filename;
            $encoded = $image->encode(new JpegEncoder(quality: 85));

            Storage::disk('public')->put($path, (string) $encoded);

            Log::info('Video thumbnail: JPG fallback saved.', [
                'campaign_id' => $campaign->id,
                'context' => $context,
                'save_path' => $path,
            ]);

            return $path;
        } catch (\Throwable $e) {
            // Last resort: store raw bytes unchanged.
            $filename = sprintf('campaign-%d-%s.jpg', $campaign->id, now()->timestamp);
            $path = self::THUMBNAILS_DIR.'/'.$filename;

            Storage::disk('public')->put($path, $imageBinary);

            Log::warning('Video thumbnail: raw JPG bytes saved after encode failure.', [
                'campaign_id' => $campaign->id,
                'context' => $context,
                'save_path' => $path,
                'exception' => $e->getMessage(),
            ]);

            return Storage::disk('public')->exists($path) ? $path : null;
        }
    }

    protected function imageManager(): ImageManager
    {
        if ($this->imageManager === null) {
            $this->imageManager = new ImageManager(new GdDriver());
        }

        return $this->imageManager;
    }

    protected function isConvertibleImagePath(string $path): bool
    {
        return (bool) preg_match('/\.(jpe?g|png)$/i', $path);
    }

    protected function resolveYouTubeThumbnailUrl(string $videoId): ?string
    {
        foreach (['maxresdefault.jpg', 'hqdefault.jpg'] as $quality) {
            $url = "https://img.youtube.com/vi/{$videoId}/{$quality}";
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'image/*'])
                ->get($url);

            if ($response->successful() && $this->isImageResponse((string) $response->header('Content-Type', ''), $response->body())) {
                return $url;
            }
        }

        return null;
    }

    protected function resolveVimeoThumbnailUrl(string $videoUrl): ?string
    {
        $oembedUrl = 'https://vimeo.com/api/oembed.json?url='.urlencode($videoUrl);

        Log::info('Video thumbnail: Vimeo oEmbed request.', [
            'video_url' => $videoUrl,
            'oembed_url' => $oembedUrl,
        ]);

        try {
            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders(['User-Agent' => self::USER_AGENT, 'Accept' => 'application/json'])
                ->get($oembedUrl);

            Log::info('Video thumbnail: Vimeo oEmbed response.', [
                'video_url' => $videoUrl,
                'http_status' => $response->status(),
                'content_type' => $response->header('Content-Type', ''),
            ]);

            if (! $response->successful()) {
                return null;
            }

            $thumbnailUrl = $response->json('thumbnail_url');

            Log::info('Video thumbnail: Vimeo thumbnail_url resolved.', [
                'video_url' => $videoUrl,
                'remote_thumbnail_url' => $thumbnailUrl,
            ]);

            return is_string($thumbnailUrl) && $thumbnailUrl !== '' ? $thumbnailUrl : null;
        } catch (\Throwable $e) {
            Log::error('Video thumbnail: Vimeo oEmbed exception.', [
                'video_url' => $videoUrl,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function isImageResponse(string $contentType, string $body): bool
    {
        if ($contentType !== '' && str_contains(strtolower($contentType), 'image')) {
            return strlen($body) > 0;
        }

        if (strlen($body) < 4) {
            return false;
        }

        return str_starts_with($body, "\xFF\xD8\xFF")
            || str_starts_with($body, "\x89PNG")
            || str_starts_with($body, 'RIFF');
    }

    public function normalizeStoragePath(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = str_replace('\\', '/', trim($path));
        $path = ltrim($path, '/');

        foreach (['public/storage/', 'public/', 'storage/app/public/', 'app/public/', 'storage/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        return $path !== '' ? $path : null;
    }
}
