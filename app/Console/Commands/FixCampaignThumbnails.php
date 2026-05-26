<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignVideo;
use App\Services\CampaignUploadService;
use App\Services\VideoThumbnailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixCampaignThumbnails extends Command
{
    protected $signature = 'thumbnails:fix
                            {--force : Re-generate all video thumbnails, replacing managed files}
                            {--webp : Convert existing JPG/PNG thumbnails to optimized WebP}';

    protected $description = 'Fetch, fix, or convert campaign video thumbnails';

    public function handle(
        VideoThumbnailService $thumbnailService,
        CampaignUploadService $uploadService,
    ): int {
        if ($this->option('webp') && ! $this->option('force')) {
            return $this->convertExistingToWebp($thumbnailService);
        }

        $remoteResult = $this->fetchMissingOrForcedRemote($thumbnailService);
        $uploadedResult = $this->fetchMissingUploadedVideoFrames($uploadService);

        $this->newLine();
        $this->info(sprintf(
            'All done. Remote: fixed %d, failed %d, skipped %d. Uploaded video: fixed %d, failed %d, skipped %d.',
            $remoteResult['fixed'],
            $remoteResult['failed'],
            $remoteResult['skipped'],
            $uploadedResult['fixed'],
            $uploadedResult['failed'],
            $uploadedResult['skipped'],
        ));

        return self::SUCCESS;
    }

    /**
     * @return array{fixed: int, failed: int, skipped: int}
     */
    protected function fetchMissingOrForcedRemote(VideoThumbnailService $thumbnailService): array
    {
        $campaignIds = CampaignVideo::query()
            ->whereIn('type', ['youtube', 'vimeo'])
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->pluck('campaign_id')
            ->merge(
                Campaign::query()
                    ->whereNull('video_type')
                    ->whereIn('video_provider', ['youtube', 'vimeo'])
                    ->whereNotNull('video_url')
                    ->where('video_url', '!=', '')
                    ->pluck('id')
            )
            ->unique()
            ->values();

        $campaigns = Campaign::query()
            ->whereIn('id', $campaignIds)
            ->orderBy('id')
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns with YouTube/Vimeo videos found.');

            return ['fixed' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $force = (bool) $this->option('force');
        $this->info("Checking {$campaigns->count()} YouTube/Vimeo campaign(s)".($force ? ' (--force)' : '').'...');

        $fixed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($campaigns as $campaign) {
            if (! $force && ! $thumbnailService->needsThumbnail($campaign)) {
                $this->line("Campaign #{$campaign->id} ({$campaign->slug}): skipped — thumbnail exists");
                $skipped++;

                continue;
            }

            if ($force && $thumbnailService->isManagedThumbnailPath($campaign->thumbnail_path)) {
                $thumbnailService->deleteManagedThumbnail($campaign->thumbnail_path);
            }

            $this->line("Campaign #{$campaign->id} ({$campaign->slug}): fetching remote thumbnail...");

            $oldBytes = $this->fileSize($campaign->thumbnail_path);
            $remote = $campaign->firstRemoteVideoForThumbnail();

            if ($remote === null) {
                $this->warn("Campaign #{$campaign->id}: no remote video source found");
                $failed++;

                continue;
            }

            $path = $thumbnailService->downloadAndStoreThumbnail($campaign, $remote['url'], $remote['provider']);

            if ($path && Storage::disk('public')->exists($path)) {
                $campaign->thumbnail_path = $path;
                $campaign->save();

                $newBytes = Storage::disk('public')->size($path);
                $this->info("Campaign #{$campaign->id}: saved → {$path} ({$this->formatBytes($newBytes)}".($oldBytes ? ", was {$this->formatBytes($oldBytes)}" : '').')');
                $fixed++;
            } else {
                $this->warn("Campaign #{$campaign->id}: failed — see storage/logs/laravel.log");
                $failed++;
            }
        }

        return compact('fixed', 'failed', 'skipped');
    }

    /**
     * @return array{fixed: int, failed: int, skipped: int}
     */
    protected function fetchMissingUploadedVideoFrames(CampaignUploadService $uploadService): array
    {
        $campaignIds = CampaignVideo::query()
            ->where('type', 'file')
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->pluck('campaign_id')
            ->merge(
                Campaign::query()
                    ->where('video_type', 'file')
                    ->whereNotNull('video_file_path')
                    ->where('video_file_path', '!=', '')
                    ->pluck('id')
            )
            ->unique()
            ->values();

        $campaigns = Campaign::query()
            ->whereIn('id', $campaignIds)
            ->orderBy('id')
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns with uploaded video files found.');

            return ['fixed' => 0, 'failed' => 0, 'skipped' => 0];
        }

        $force = (bool) $this->option('force');
        $this->newLine();
        $this->info("Checking {$campaigns->count()} uploaded-video campaign(s)".($force ? ' (--force)' : '').'...');

        $fixed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($campaigns as $campaign) {
            if (! $force && $uploadService->hasValidThumbnail($campaign)) {
                $this->line("Campaign #{$campaign->id} ({$campaign->slug}): skipped — thumbnail exists");
                $skipped++;

                continue;
            }

            $this->line("Campaign #{$campaign->id} ({$campaign->slug}): extracting video frame...");

            $oldBytes = $this->fileSize($campaign->thumbnail_path);
            $path = $uploadService->generateThumbnailFromVideoFile($campaign);

            if ($path && Storage::disk('public')->exists($path)) {
                $campaign->thumbnail_path = $path;
                $campaign->save();

                $newBytes = Storage::disk('public')->size($path);
                $this->info("Campaign #{$campaign->id}: saved → {$path} ({$this->formatBytes($newBytes)}".($oldBytes ? ", was {$this->formatBytes($oldBytes)}" : '').')');
                $fixed++;
            } else {
                $this->warn("Campaign #{$campaign->id}: failed or skipped — see storage/logs/laravel.log");
                $failed++;
            }
        }

        return compact('fixed', 'failed', 'skipped');
    }

    protected function convertExistingToWebp(VideoThumbnailService $thumbnailService): int
    {
        $campaigns = Campaign::query()
            ->whereNotNull('thumbnail_path')
            ->where('thumbnail_path', '!=', '')
            ->orderBy('id')
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns with stored thumbnails found.');

            return self::SUCCESS;
        }

        $this->info("Converting {$campaigns->count()} thumbnail(s) to WebP...");

        $converted = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($campaigns as $campaign) {
            $path = $thumbnailService->normalizeStoragePath($campaign->thumbnail_path);

            if ($path && str_ends_with(strtolower($path), '.webp')) {
                $this->line("Campaign #{$campaign->id}: skipped — already WebP");
                $skipped++;

                continue;
            }

            $oldBytes = $this->fileSize($campaign->thumbnail_path);
            $this->line("Campaign #{$campaign->id}: converting...");

            $newPath = $thumbnailService->convertExistingToWebp($campaign);

            if ($newPath && Storage::disk('public')->exists($newPath)) {
                $campaign->thumbnail_path = $newPath;
                $campaign->save();

                $newBytes = Storage::disk('public')->size($newPath);
                $this->info("Campaign #{$campaign->id}: converted → {$newPath} ({$this->formatBytes($newBytes)}".($oldBytes ? ", was {$this->formatBytes($oldBytes)}" : '').')');
                $converted++;
            } else {
                $this->warn("Campaign #{$campaign->id}: conversion failed");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done. Converted: {$converted}, Skipped: {$skipped}, Failed: {$failed}");

        return self::SUCCESS;
    }

    protected function fileSize(?string $path): ?int
    {
        $normalized = app(VideoThumbnailService::class)->normalizeStoragePath($path);

        if ($normalized && Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->size($normalized);
        }

        return null;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
