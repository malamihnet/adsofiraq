<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAsset;
use App\Services\Import\CampaignImportImageUrlResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;

class CampaignAssetDedupService
{
    protected ?ImageManager $imageManager = null;

    public function __construct(
        protected CampaignImportImageUrlResolver $urlResolver,
    ) {}

    public static function hashBytes(string $bytes): string
    {
        return hash('sha256', $bytes);
    }

    /**
     * Fingerprint decoded image pixels (catches same visual across WebP/JPG/PNG).
     */
    public function visualContentHash(string $bytes): ?string
    {
        if ($bytes === '') {
            return null;
        }

        try {
            $image = $this->imageManager()->read($bytes);
            $image->cover(64, 64);

            return hash('sha256', (string) $image->encode(new PngEncoder()));
        } catch (\Throwable $e) {
            Log::debug('Campaign asset visual hash fallback to raw bytes.', [
                'error' => $e->getMessage(),
            ]);

            return self::hashBytes($bytes);
        }
    }

    public function sourceUrlKey(?string $url, ?string $pageUrl = null): ?string
    {
        $normalized = $this->normalizeSourceUrl($url, $pageUrl);

        return $normalized !== null ? hash('sha256', $normalized) : null;
    }

    public function normalizeSourceUrl(?string $url, ?string $pageUrl = null): ?string
    {
        $resolved = $this->urlResolver->resolve($url, $pageUrl);

        if ($resolved === null) {
            return null;
        }

        return strtolower(rtrim($resolved, '/'));
    }

    public function importAlreadyExists(
        Campaign $campaign,
        string $sourceUrl,
        string $contentHash,
    ): bool {
        $sourceKey = $this->sourceUrlKey($sourceUrl);

        $query = $campaign->assets()->where('file_type', 'image');

        return $query->where(function ($builder) use ($contentHash, $sourceKey) {
            $builder->where('content_hash', $contentHash);

            if ($sourceKey !== null) {
                $builder->orWhere('source_url_key', $sourceKey);
            }
        })->exists();
    }

    /**
     * @return \Illuminate\Support\Collection<int, CampaignAsset>
     */
    public function galleryStillsFor(Campaign $campaign): Collection
    {
        if (! $campaign->relationLoaded('assets')) {
            $campaign->load('assets');
        }

        $thumbnailKey = $this->galleryPathKey($campaign->thumbnail_path);

        return $this->uniqueImageAssets($campaign->assets, $thumbnailKey);
    }

    /**
     * @param  Collection<int, CampaignAsset>  $assets
     * @return Collection<int, CampaignAsset>
     */
    public function uniqueImageAssets(Collection $assets, ?string $thumbnailKey = null): Collection
    {
        $sorted = $assets
            ->filter(fn (CampaignAsset $asset) => $asset->isDisplayableImage())
            ->sortBy(fn (CampaignAsset $asset) => [$asset->sort_order, $asset->id])
            ->values();

        $keepers = [];
        $seenPaths = [];
        $seenHashes = [];
        $seenSources = [];
        foreach ($sorted as $asset) {
            $pathKey = $asset->galleryPathKey();

            if ($pathKey === null || isset($seenPaths[$pathKey])) {
                continue;
            }

            if ($thumbnailKey !== null && $pathKey === $thumbnailKey) {
                continue;
            }

            $hash = $this->resolveContentHash($asset);
            $sourceKey = $asset->source_url_key
                ?? $this->sourceUrlKey($asset->source_url);

            if ($hash !== null && isset($seenHashes[$hash])) {
                continue;
            }

            if ($sourceKey !== null && isset($seenSources[$sourceKey])) {
                continue;
            }

            $seenPaths[$pathKey] = true;

            if ($hash !== null) {
                $seenHashes[$hash] = true;
            }

            if ($sourceKey !== null) {
                $seenSources[$sourceKey] = true;
            }

            $keepers[] = $asset;
        }

        return collect($keepers);
    }

    /**
     * @return array{removed: int, files_deleted: int, campaigns: int}
     */
    public function removeDuplicateStillsForCampaign(
        Campaign $campaign,
        bool $deleteFiles = true,
    ): array {
        $campaign->load('assets');

        $imageAssets = $campaign->assets
            ->filter(fn (CampaignAsset $asset) => $asset->isDisplayableImage())
            ->sortBy(fn (CampaignAsset $asset) => [$asset->sort_order, $asset->id])
            ->values();

        if ($imageAssets->count() < 2) {
            $this->backfillAssetMetadata($imageAssets);

            return ['removed' => 0, 'files_deleted' => 0, 'campaigns' => 1];
        }

        $groups = $this->buildDuplicateGroups($imageAssets);
        $removed = 0;
        $filesDeleted = 0;

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            $winner = $this->pickBestAsset($group);
            $losers = array_filter($group, fn (CampaignAsset $asset) => $asset->id !== $winner->id);

            foreach ($losers as $loser) {
                if ($deleteFiles) {
                    $filesDeleted += $this->deleteAssetFile($loser) ? 1 : 0;
                }

                $loser->delete();
                $removed++;

                Log::info('Duplicate still removed for campaign #'.$campaign->id, [
                    'campaign_id' => $campaign->id,
                    'removed_asset_id' => $loser->id,
                    'removed_path' => $loser->file_path,
                    'kept_asset_id' => $winner->id,
                    'kept_path' => $winner->file_path,
                ]);
            }
        }

        $this->renumberSortOrder($campaign->fresh('assets')->assets);
        $this->backfillAssetMetadata($campaign->fresh('assets')->assets);

        return [
            'removed' => $removed,
            'files_deleted' => $filesDeleted,
            'campaigns' => 1,
        ];
    }

    /**
     * @return array{removed: int, files_deleted: int, campaigns: int}
     */
    public function removeDuplicateStillsAll(?int $limit = null): array
    {
        $stats = ['removed' => 0, 'files_deleted' => 0, 'campaigns' => 0];

        $query = Campaign::query()->with('assets')->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $query->chunkById(25, function (Collection $campaigns) use (&$stats) {
            foreach ($campaigns as $campaign) {
                $result = $this->removeDuplicateStillsForCampaign($campaign);
                $stats['removed'] += $result['removed'];
                $stats['files_deleted'] += $result['files_deleted'];

                if ($result['removed'] > 0) {
                    $stats['campaigns']++;
                }
            }
        });

        return $stats;
    }

    public function resolveContentHash(CampaignAsset $asset): ?string
    {
        if (! empty($asset->content_hash)) {
            return $asset->content_hash;
        }

        $path = $asset->normalizedFilePath();

        if ($path === null || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $bytes = Storage::disk('public')->get($path);

        return $bytes !== '' ? $this->visualContentHash($bytes) : null;
    }

    protected function imageManager(): ImageManager
    {
        if ($this->imageManager === null) {
            $this->imageManager = new ImageManager(new GdDriver());
        }

        return $this->imageManager;
    }

    /**
     * @param  Collection<int, CampaignAsset>  $assets
     */
    public function backfillAssetMetadata(Collection $assets): void
    {
        foreach ($assets as $asset) {
            if (! $asset->isDisplayableImage()) {
                continue;
            }

            $updates = [];
            $hash = $this->resolveContentHash($asset);

            if ($hash !== null && $asset->content_hash !== $hash) {
                $updates['content_hash'] = $hash;
            }

            if (! empty($asset->source_url) && empty($asset->source_url_key)) {
                $updates['source_url_key'] = $this->sourceUrlKey($asset->source_url);
            }

            if ($updates !== []) {
                $asset->update($updates);
            }
        }
    }

    /**
     * @param  Collection<int, CampaignAsset>  $assets
     * @return list<list<CampaignAsset>>
     */
    protected function buildDuplicateGroups(Collection $assets): array
    {
        $parent = [];

        foreach ($assets as $asset) {
            $parent[$asset->id] = $asset->id;
        }

        $find = function (int $id) use (&$parent, &$find): int {
            if ($parent[$id] !== $id) {
                $parent[$id] = $find($parent[$id]);
            }

            return $parent[$id];
        };

        $union = function (int $a, int $b) use (&$parent, $find): void {
            $rootA = $find($a);
            $rootB = $find($b);

            if ($rootA !== $rootB) {
                $parent[$rootB] = $rootA;
            }
        };

        $hashToId = [];
        $sourceToId = [];

        foreach ($assets as $asset) {
            $hash = $this->resolveContentHash($asset);
            $sourceKey = $asset->source_url_key
                ?? $this->sourceUrlKey($asset->source_url);

            if ($hash !== null) {
                if (isset($hashToId[$hash])) {
                    $union($asset->id, $hashToId[$hash]);
                } else {
                    $hashToId[$hash] = $asset->id;
                }
            }

            if ($sourceKey !== null) {
                if (isset($sourceToId[$sourceKey])) {
                    $union($asset->id, $sourceToId[$sourceKey]);
                } else {
                    $sourceToId[$sourceKey] = $asset->id;
                }
            }
        }

        $groups = [];

        foreach ($assets as $asset) {
            $root = $find($asset->id);
            $groups[$root][] = $asset;
        }

        return array_values($groups);
    }

    /**
     * @param  list<CampaignAsset>  $assets
     */
    protected function pickBestAsset(array $assets): CampaignAsset
    {
        usort($assets, function (CampaignAsset $a, CampaignAsset $b) {
            return $this->compareAssetPreference($a, $b);
        });

        return $assets[0];
    }

    protected function compareAssetPreference(CampaignAsset $a, CampaignAsset $b): int
    {
        $webpA = $a->isWebpFile() ? 0 : 1;
        $webpB = $b->isWebpFile() ? 0 : 1;

        if ($webpA !== $webpB) {
            return $webpA <=> $webpB;
        }

        if ($a->sort_order !== $b->sort_order) {
            return $a->sort_order <=> $b->sort_order;
        }

        return $a->id <=> $b->id;
    }

    /**
     * @param  Collection<int, CampaignAsset>  $assets
     */
    protected function renumberSortOrder(Collection $assets): void
    {
        $order = 0;

        foreach ($assets->sortBy(fn (CampaignAsset $asset) => [$asset->sort_order, $asset->id]) as $asset) {
            if ($asset->sort_order !== $order) {
                $asset->update(['sort_order' => $order]);
            }

            $order++;
        }
    }

    protected function deleteAssetFile(CampaignAsset $asset): bool
    {
        $path = $asset->normalizedFilePath();

        if ($path === null || filter_var($path, FILTER_VALIDATE_URL)) {
            return false;
        }

        if (! Storage::disk('public')->exists($path)) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    protected function galleryPathKey(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', trim($path));

        foreach (['public/storage/', 'public/', 'storage/app/public/', 'app/public/', 'storage/'] as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                $normalized = substr($normalized, strlen($prefix));
            }
        }

        $normalized = ltrim($normalized, '/');

        if (filter_var($normalized, FILTER_VALIDATE_URL)) {
            return strtolower($normalized);
        }

        return strtolower($normalized);
    }
}
