<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class CampaignUploadService
{
    protected const THUMBNAILS_DIR = 'campaigns/thumbnails';

    protected ?ImageManager $imageManager = null;

    public function __construct(
        protected VideoThumbnailService $videoThumbnailService,
        protected VideoFrameThumbnailService $videoFrameThumbnailService,
    ) {}

    public function storeThumbnail(Campaign $campaign, UploadedFile $file): string
    {
        return $file->store(config('upload.campaign_path').'/'.$campaign->id.'/thumbnails', 'public');
    }

    public function storeVideoFile(Campaign $campaign, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $filename = sprintf('campaign-%d-%d.%s', $campaign->id, now()->timestamp, $extension);

        return $file->storeAs('campaigns/videos', $filename, 'public');
    }

    /**
     * Store uploaded stills and return the first created asset (upload order).
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function storeAssets(Campaign $campaign, array $files): ?CampaignAsset
    {
        $sortOrder = (int) ($campaign->assets()->max('sort_order') ?? 0);
        $firstAsset = null;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store(config('upload.campaign_path').'/'.$campaign->id.'/assets', 'public');

            $asset = $campaign->assets()->create([
                'file_path' => $path,
                'file_type' => 'image',
                'sort_order' => ++$sortOrder,
            ]);

            $firstAsset ??= $asset;
        }

        return $firstAsset;
    }

    /**
     * Resolve campaign thumbnail:
     * manual → first still → YouTube/Vimeo → uploaded video frame → placeholder at display.
     */
    public function resolveThumbnail(
        Campaign $campaign,
        bool $manualThumbnailUploaded = false,
        ?CampaignAsset $firstNewAsset = null,
    ): void {
        if ($manualThumbnailUploaded || $this->hasValidThumbnail($campaign)) {
            return;
        }

        $path = $firstNewAsset
            ? $this->generateThumbnailFromAsset($campaign, $firstNewAsset)
            : $this->generateThumbnailFromFirstAsset($campaign);

        if ($path) {
            $campaign->update(['thumbnail_path' => $path]);

            return;
        }

        if ($this->shouldFetchRemoteVideoThumbnail($campaign)) {
            try {
                $this->videoThumbnailService->applyFallbackIfNeeded($campaign->fresh(), false);
            } catch (\Throwable $e) {
                report($e);
            }

            if ($this->hasValidThumbnail($campaign->fresh())) {
                return;
            }
        }

        $videoFramePath = $this->generateThumbnailFromVideoFile($campaign->fresh());

        if ($videoFramePath) {
            $campaign->update(['thumbnail_path' => $videoFramePath]);
        }
    }

    /**
     * Generate thumbnail from the first frame of an uploaded video file (requires FFmpeg).
     */
    public function generateThumbnailFromVideoFile(Campaign $campaign): ?string
    {
        $video = $campaign->firstFileVideoForThumbnail();

        if (! $video || $this->hasValidThumbnail($campaign)) {
            return null;
        }

        if ($this->videoFrameThumbnailService->resolveVideoAbsolutePath($video->file_path) === null) {
            return null;
        }

        $frameBinary = $this->videoFrameThumbnailService->extractFrameBinaryFromVideo($campaign, $video);

        if ($frameBinary === null) {
            return null;
        }

        return $this->storeOptimizedThumbnailFromVideoFrame($campaign, $frameBinary);
    }

    /**
     * Resize/crop extracted video frame to 1280×720 cover and save as WebP.
     */
    public function storeOptimizedThumbnailFromVideoFrame(Campaign $campaign, string $imageBinary): ?string
    {
        Storage::disk('public')->makeDirectory(self::THUMBNAILS_DIR);

        $width = (int) config('upload.thumbnail_width', 1280);
        $height = (int) config('upload.thumbnail_height', 720);
        $quality = (int) config('upload.webp_quality', 82);

        try {
            $image = $this->imageManager()->decodeBinary($imageBinary);
            $image->cover($width, $height);

            $filename = sprintf('campaign-%d-video-frame-%d.webp', $campaign->id, now()->timestamp);
            $path = self::THUMBNAILS_DIR.'/'.$filename;
            $encoded = $image->encode(new WebpEncoder(quality: $quality));

            Storage::disk('public')->put($path, (string) $encoded);

            if (Storage::disk('public')->missing($path)) {
                throw new \RuntimeException('WebP thumbnail was not written to disk.');
            }

            Log::info('Campaign thumbnail: generated from uploaded video.', [
                'campaign_id' => $campaign->id,
                'thumbnail_path' => $path,
            ]);

            return $path;
        } catch (\Throwable $e) {
            Log::error('Campaign thumbnail: failed to encode video frame.', [
                'campaign_id' => $campaign->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function shouldFetchRemoteVideoThumbnail(Campaign $campaign): bool
    {
        return $campaign->firstRemoteVideoForThumbnail() !== null;
    }

    /**
     * Generate an optimized WebP thumbnail from the campaign's first still (by sort_order).
     */
    public function generateThumbnailFromFirstAsset(Campaign $campaign): ?string
    {
        $asset = $campaign->assets()->orderBy('sort_order')->first();

        if (! $asset) {
            return null;
        }

        return $this->generateThumbnailFromAsset($campaign, $asset);
    }

    /**
     * Generate an optimized WebP thumbnail from a specific still (separate file, not the asset path).
     */
    public function generateThumbnailFromAsset(Campaign $campaign, CampaignAsset $asset): ?string
    {
        $path = $this->normalizeStoragePath($asset->file_path);

        if ($path === null || Storage::disk('public')->missing($path)) {
            Log::warning('Campaign thumbnail: still file missing.', [
                'campaign_id' => $campaign->id,
                'asset_id' => $asset->id,
                'file_path' => $asset->file_path,
            ]);

            return null;
        }

        try {
            $binary = Storage::disk('public')->get($path);

            return $this->storeOptimizedThumbnailFromStill($campaign, $binary);
        } catch (\Throwable $e) {
            Log::error('Campaign thumbnail: failed to generate from still.', [
                'campaign_id' => $campaign->id,
                'asset_id' => $asset->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function hasValidThumbnail(Campaign $campaign): bool
    {
        $path = $this->normalizeStoragePath($campaign->thumbnail_path);

        if ($path === null) {
            return false;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return true;
        }

        return Storage::disk('public')->exists($path);
    }

    public function deleteCampaignFiles(Campaign $campaign): void
    {
        $campaign->loadMissing('videos');

        foreach ($campaign->videos as $video) {
            if ($video->file_path) {
                $path = $this->normalizeStoragePath($video->file_path);
                if ($path && Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        if ($campaign->video_file_path) {
            $path = $this->normalizeStoragePath($campaign->video_file_path);
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        Storage::disk('public')->deleteDirectory(config('upload.campaign_path').'/'.$campaign->id);
    }

    /**
     * Resize/crop to 1280×720 cover and save as WebP in campaigns/thumbnails/.
     */
    protected function storeOptimizedThumbnailFromStill(Campaign $campaign, string $imageBinary): ?string
    {
        Storage::disk('public')->makeDirectory(self::THUMBNAILS_DIR);

        $width = (int) config('upload.thumbnail_width', 1280);
        $height = (int) config('upload.thumbnail_height', 720);
        $quality = (int) config('upload.webp_quality', 82);

        try {
            $image = $this->imageManager()->decodeBinary($imageBinary);
            $image->cover($width, $height);

            $filename = sprintf('campaign-%d-first-still-%d.webp', $campaign->id, now()->timestamp);
            $path = self::THUMBNAILS_DIR.'/'.$filename;
            $encoded = $image->encode(new WebpEncoder(quality: $quality));

            Storage::disk('public')->put($path, (string) $encoded);

            if (Storage::disk('public')->missing($path)) {
                throw new \RuntimeException('WebP thumbnail was not written to disk.');
            }

            Log::info('Campaign thumbnail: generated from still.', [
                'campaign_id' => $campaign->id,
                'thumbnail_path' => $path,
            ]);

            return $path;
        } catch (\Throwable $e) {
            Log::error('Campaign thumbnail: WebP encode failed, trying JPG fallback.', [
                'campaign_id' => $campaign->id,
                'exception' => $e->getMessage(),
            ]);

            return $this->storeJpgFallbackFromStill($campaign, $imageBinary, $width, $height);
        }
    }

    protected function storeJpgFallbackFromStill(Campaign $campaign, string $imageBinary, int $width, int $height): ?string
    {
        try {
            $image = $this->imageManager()->decodeBinary($imageBinary);
            $image->cover($width, $height);

            $filename = sprintf('campaign-%d-first-still-%d.jpg', $campaign->id, now()->timestamp);
            $path = self::THUMBNAILS_DIR.'/'.$filename;

            Storage::disk('public')->put($path, (string) $image->encode(new JpegEncoder(quality: 85)));

            return Storage::disk('public')->exists($path) ? $path : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    protected function imageManager(): ImageManager
    {
        if ($this->imageManager === null) {
            $this->imageManager = new ImageManager(new GdDriver());
        }

        return $this->imageManager;
    }

    protected function normalizeStoragePath(?string $path): ?string
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
