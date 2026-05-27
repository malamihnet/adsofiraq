<?php

namespace App\Services\Import;

use App\Models\Campaign;
use App\Services\CampaignUploadService;
use App\Services\PublicStorageSyncService;
use App\Services\VideoThumbnailService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RepairImportedCampaignMedia
{
    public function __construct(
        protected CampaignPageFetcher $fetcher,
        protected CampaignPageParser $parser,
        protected CampaignImportMediaService $mediaService,
        protected CampaignUploadService $uploadService,
        protected VideoThumbnailService $videoThumbnailService,
        protected PublicStorageSyncService $publicStorageSync,
    ) {}

    /**
     * @return array{
     *     stills_added: int,
     *     thumbnail_updated: bool,
     *     skipped: bool,
     *     message: ?string,
     *     sync: array{copied: int, skipped: int, failed: int, target: ?string},
     * }
     */
    public function repair(Campaign $campaign, bool $replaceExisting = false): array
    {
        $result = [
            'stills_added' => 0,
            'thumbnail_updated' => false,
            'skipped' => false,
            'message' => null,
            'sync' => $this->publicStorageSync->emptyStats(),
        ];

        if (empty($campaign->source_url)) {
            $result['skipped'] = true;
            $result['message'] = 'Campaign has no source_url.';

            return $result;
        }

        Log::info('Campaign media repair: started.', [
            'campaign_id' => $campaign->id,
            'source_url' => $campaign->source_url,
            'replace' => $replaceExisting,
        ]);

        try {
            $html = $this->fetcher->fetch(
                $campaign->source_url,
                null,
                (int) config('import.download_retries', 3),
            );
            $parsed = $this->parser->parse($html, $campaign->source_url);
        } catch (\Throwable $e) {
            $result['skipped'] = true;
            $result['message'] = $e->getMessage();
            Log::warning('Campaign media repair: fetch/parse failed.', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return $result;
        }

        $imageUrls = is_array($parsed['image_urls'] ?? null) ? $parsed['image_urls'] : [];

        if ($replaceExisting) {
            $this->removeImportedMediaFiles($campaign);
        }

        $campaign = $campaign->fresh(['assets']);

        $needsStills = $replaceExisting || $campaign->assets->isEmpty() || $this->hasMissingAssetFiles($campaign->assets);

        if ($needsStills && $imageUrls !== []) {
            $before = $campaign->assets()->count();
            $this->mediaService->importStills($campaign, $imageUrls);
            $result['stills_added'] = max(0, $campaign->assets()->count() - $before);
        }

        $campaign = $campaign->fresh(['assets']);

        if (! $this->uploadService->hasValidThumbnail($campaign->fresh()) || $replaceExisting) {
            $thumbnailCandidates = array_values(array_filter(array_unique([
                $parsed['og_image'] ?? null,
                $parsed['thumbnail_url'] ?? null,
                $imageUrls[0] ?? null,
            ])));

            foreach ($thumbnailCandidates as $candidate) {
                $path = $this->mediaService->downloadThumbnail($campaign, $candidate);

                if ($path) {
                    $campaign->update(['thumbnail_path' => $path]);
                    $result['thumbnail_updated'] = true;

                    break;
                }
            }
        }

        $campaign = $campaign->fresh(['videos', 'assets']);
        $this->uploadService->resolveThumbnail($campaign, false, $campaign->assets->first());

        if (! $this->uploadService->hasValidThumbnail($campaign->fresh())) {
            try {
                $this->videoThumbnailService->applyFallbackIfNeeded($campaign->fresh(), false);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $campaign = $campaign->fresh(['assets', 'videos']);
        $result['sync'] = $this->publicStorageSync->syncCampaign($campaign);

        Log::info('Campaign media repair: finished.', [
            'campaign_id' => $campaign->id,
            'stills_added' => $result['stills_added'],
            'thumbnail_updated' => $result['thumbnail_updated'],
            'sync_copied' => $result['sync']['copied'],
        ]);

        return $result;
    }

    public function needsRepair(Campaign $campaign): bool
    {
        if (empty($campaign->source_url)) {
            return false;
        }

        if ($campaign->assets->isEmpty()) {
            return true;
        }

        if ($this->hasMissingAssetFiles($campaign->assets)) {
            return true;
        }

        if (empty($campaign->thumbnail_path)) {
            return true;
        }

        if (filter_var($campaign->thumbnail_path, FILTER_VALIDATE_URL)) {
            return true;
        }

        $path = $campaign->thumbnail_path;

        return ! Storage::disk('public')->exists($path);
    }

    /**
     * @return array{repaired: int, skipped: int, failed: int, sync_copied: int}
     */
    public function repairAll(bool $replaceExisting = false, ?int $limit = null): array
    {
        $stats = ['repaired' => 0, 'skipped' => 0, 'failed' => 0, 'sync_copied' => 0];

        $query = Campaign::query()
            ->whereNotNull('source_url')
            ->where('source_url', '!=', '')
            ->with('assets')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $query->chunkById(25, function (Collection $campaigns) use (&$stats, $replaceExisting) {
            foreach ($campaigns as $campaign) {
                if (! $replaceExisting && ! $this->needsRepair($campaign)) {
                    $stats['skipped']++;

                    continue;
                }

                try {
                    $repairResult = $this->repair($campaign, $replaceExisting);
                    $stats['repaired']++;
                    $stats['sync_copied'] += $repairResult['sync']['copied'] ?? 0;
                } catch (\Throwable $e) {
                    $stats['failed']++;
                    report($e);
                    Log::warning('Campaign media repair: campaign failed.', [
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        return $stats;
    }

    protected function removeImportedMediaFiles(Campaign $campaign): void
    {
        $prefix = config('upload.campaign_path', 'campaigns').'/'.$campaign->id.'/';

        foreach ($campaign->assets as $asset) {
            if (str_starts_with($asset->file_path, $prefix)
                || str_starts_with($asset->file_path, 'campaigns/assets/')
                || str_starts_with($asset->file_path, 'campaigns/thumbnails/')) {
                if (Storage::disk('public')->exists($asset->file_path)) {
                    Storage::disk('public')->delete($asset->file_path);
                }

                $asset->delete();
            }
        }
    }

    /**
     * @param  Collection<int, \App\Models\CampaignAsset>  $assets
     */
    protected function hasMissingAssetFiles(Collection $assets): bool
    {
        foreach ($assets as $asset) {
            if (! Storage::disk('public')->exists($asset->file_path)) {
                return true;
            }
        }

        return false;
    }
}
