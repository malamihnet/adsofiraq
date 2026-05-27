<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAsset;
use Illuminate\Support\Collection;

/**
 * @deprecated Use CampaignMediaDeduplicationService directly.
 */
class CampaignAssetDedupService
{
    public function __construct(
        protected CampaignMediaDeduplicationService $media,
    ) {}

    public static function hashBytes(string $bytes): string
    {
        return CampaignMediaDeduplicationService::hashBytes($bytes);
    }

    public function visualContentHash(string $bytes): ?string
    {
        return $this->media->visualContentHash($bytes);
    }

    public function sourceUrlKey(?string $url, ?string $pageUrl = null): ?string
    {
        return $this->media->sourceUrlKey($url, $pageUrl);
    }

    public function normalizeSourceUrl(?string $url, ?string $pageUrl = null): ?string
    {
        return $this->media->normalizeSourceUrl($url, $pageUrl);
    }

    public function importAlreadyExists(
        Campaign $campaign,
        string $sourceUrl,
        string $contentHash,
    ): bool {
        return $this->media->stillImportExists($campaign, $sourceUrl, $contentHash);
    }

    public function galleryStillsFor(Campaign $campaign): Collection
    {
        return $this->media->galleryStillsFor($campaign);
    }

    public function uniqueImageAssets(Collection $assets, ?string $thumbnailKey = null): Collection
    {
        return $this->media->uniqueImageAssets($assets, $thumbnailKey);
    }

    public function removeDuplicateStillsForCampaign(
        Campaign $campaign,
        bool $deleteFiles = true,
    ): array {
        $result = $this->media->cleanCampaign($campaign, dryRun: false, deleteFiles: $deleteFiles);

        return [
            'removed' => $result['stills_removed'] + $result['thumbnail_stills_removed'],
            'files_deleted' => $result['files_deleted'],
            'campaigns' => 1,
        ];
    }

    public function removeDuplicateStillsAll(?int $limit = null): array
    {
        $stats = $this->media->cleanAllCampaigns(
            dryRun: false,
            deleteFiles: true,
            limit: $limit,
        );

        return [
            'removed' => $stats['stills_removed'] + $stats['thumbnail_stills_removed'],
            'files_deleted' => $stats['files_deleted'],
            'campaigns' => $stats['campaigns_affected'],
        ];
    }

    public function resolveContentHash(CampaignAsset $asset): ?string
    {
        return $this->media->resolveStillContentHash($asset);
    }

    public function backfillAssetMetadata(Collection $assets): void
    {
        $this->media->backfillStillMetadata($assets);
    }
}
