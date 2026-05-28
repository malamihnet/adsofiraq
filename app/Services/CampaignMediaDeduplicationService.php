<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignAsset;
use App\Models\CampaignVideo;
use App\Services\Import\CampaignImportImageUrlResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\ImageManager;

class CampaignMediaDeduplicationService
{
    protected ?ImageManager $imageManager = null;

    public function __construct(
        protected CampaignImportImageUrlResolver $urlResolver,
    ) {}

    public static function hashBytes(string $bytes): string
    {
        return hash('sha256', $bytes);
    }

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
            Log::debug('Campaign media visual hash fallback to raw bytes.', [
                'error' => $e->getMessage(),
            ]);

            return self::hashBytes($bytes);
        }
    }

    public function normalizeSourceUrl(?string $url, ?string $pageUrl = null): ?string
    {
        $resolved = $this->urlResolver->resolve($url, $pageUrl);

        if ($resolved === null) {
            return null;
        }

        return strtolower(rtrim($resolved, '/'));
    }

    public function sourceUrlKey(?string $url, ?string $pageUrl = null): ?string
    {
        $normalized = $this->normalizeSourceUrl($url, $pageUrl);

        return $normalized !== null ? hash('sha256', $normalized) : null;
    }

    public function galleryPathKey(?string $path): ?string
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

    // -------------------------------------------------------------------------
    // Import guards
    // -------------------------------------------------------------------------

    public function stillImportExists(
        Campaign $campaign,
        string $sourceUrl,
        string $contentHash,
        ?string $filePath = null,
    ): bool {
        if ($filePath !== null && $filePath !== '') {
            $existsAtPath = $campaign->assets()
                ->where('file_type', 'image')
                ->where('file_path', $filePath)
                ->exists();

            if ($existsAtPath) {
                return true;
            }
        }

        $sourceKey = $this->sourceUrlKey($sourceUrl, $campaign->source_url);

        return $campaign->assets()
            ->where('file_type', 'image')
            ->where(function ($builder) use ($contentHash, $sourceKey) {
                $builder->where('content_hash', $contentHash);

                if ($sourceKey !== null) {
                    $builder->orWhere('source_url_key', $sourceKey);
                }
            })
            ->exists();
    }

    public function videoEmbedKey(string $type, ?string $url): ?string
    {
        $type = strtolower(trim($type));
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if ($type === 'youtube') {
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                return 'youtube:'.$matches[1];
            }
        }

        if ($type === 'vimeo') {
            if (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches)) {
                return 'vimeo:'.$matches[1];
            }
        }

        $normalized = $this->normalizeSourceUrl($url);

        return $normalized !== null ? $type.':url:'.hash('sha256', $normalized) : null;
    }

    public function videoImportExists(
        Campaign $campaign,
        string $type,
        ?string $url = null,
        ?string $fileHash = null,
    ): bool {
        $embedKey = $this->videoEmbedKey($type, $url);
        $sourceKey = $url !== null ? $this->sourceUrlKey($url, $campaign->source_url) : null;

        $query = $campaign->videos();

        return $query->where(function ($builder) use ($embedKey, $sourceKey, $fileHash) {
            if ($embedKey !== null) {
                $builder->orWhere('embed_key', $embedKey);
            }

            if ($sourceKey !== null) {
                $builder->orWhere('source_url_key', $sourceKey);
            }

            if ($fileHash !== null) {
                $builder->orWhere('content_hash', $fileHash);
            }
        })->exists();
    }

    public function logDuplicateSkipped(Campaign $campaign, string $mediaType, array $context = []): void
    {
        Log::info('Duplicate media skipped', array_merge([
            'campaign_id' => $campaign->id,
            'media_type' => $mediaType,
        ], $context));
    }

    // -------------------------------------------------------------------------
    // Gallery / unique lists
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, CampaignAsset>
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

            $hash = $this->resolveStillContentHash($asset);
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

    public function resolveStillContentHash(CampaignAsset $asset): ?string
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

    public function resolveVideoFileHash(?string $filePath): ?string
    {
        if ($filePath === null || trim($filePath) === '') {
            return null;
        }

        $path = $this->normalizeStoragePath($filePath);

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $bytes = Storage::disk('public')->get($path);

        return $bytes !== '' ? self::hashBytes($bytes) : null;
    }

    // -------------------------------------------------------------------------
    // Cleanup
    // -------------------------------------------------------------------------

    /**
     * Remove imported stills that are not real AOTW gallery uploads (og/meta/posters/legacy broad scrape).
     *
     * @param  list<string>  $galleryUrls
     * @param  list<string>  $excludedUrls
     * @return array{removed: int, files_deleted: int}
     */
    public function removeNonGalleryStills(
        Campaign $campaign,
        array $galleryUrls,
        array $excludedUrls,
        bool $dryRun = false,
        bool $deleteFiles = true,
    ): array {
        $allowedKeys = [];

        foreach ($galleryUrls as $url) {
            $key = $this->sourceUrlKey($url, $campaign->source_url);

            if ($key !== null) {
                $allowedKeys[$key] = true;
            }
        }

        $allowedUrls = [];

        foreach ($galleryUrls as $url) {
            $normalized = $this->normalizeSourceUrl($url);

            if ($normalized !== null) {
                $allowedUrls[$normalized] = true;
            }
        }

        $excludedKeys = [];

        foreach ($excludedUrls as $url) {
            $key = $this->sourceUrlKey($url, $campaign->source_url);

            if ($key !== null) {
                $excludedKeys[$key] = true;
            }
        }

        $thumbnailKey = $this->galleryPathKey($campaign->thumbnail_path);

        $removed = 0;
        $filesDeleted = 0;
        $galleryEmpty = $galleryUrls === [];

        foreach ($campaign->assets->where('file_type', 'image') as $asset) {
            $sourceKey = $asset->source_url_key
                ?? $this->sourceUrlKey($asset->source_url, $campaign->source_url);
            $normalizedSource = $this->normalizeSourceUrl($asset->source_url);
            $pathKey = $asset->galleryPathKey();

            $isAllowed = ! $galleryEmpty && (
                ($sourceKey !== null && isset($allowedKeys[$sourceKey]))
                || ($normalizedSource !== null && isset($allowedUrls[$normalizedSource]))
            );

            $isExcluded = ($sourceKey !== null && isset($excludedKeys[$sourceKey]))
                || ($thumbnailKey !== null && $pathKey === $thumbnailKey);

            if ($isAllowed && ! $isExcluded) {
                continue;
            }

            if ($dryRun) {
                $removed++;

                continue;
            }

            if ($deleteFiles) {
                $filesDeleted += $this->deleteAssetFile($asset) ? 1 : 0;
            }

            $asset->delete();
            $removed++;
        }

        if (! $dryRun && $removed > 0) {
            $this->rebuildAssetSortOrder($campaign->fresh()->assets);
        }

        return ['removed' => $removed, 'files_deleted' => $filesDeleted];
    }

    /**
     * @return array{
     *     dry_run: bool,
     *     campaign_id: int,
     *     stills_removed: int,
     *     videos_removed: int,
     *     thumbnail_stills_removed: int,
     *     files_deleted: int,
     *     sort_rebuilt: bool,
     * }
     */
    public function cleanCampaign(
        Campaign $campaign,
        bool $dryRun = false,
        bool $deleteFiles = true,
    ): array {
        $campaign->load(['assets', 'videos']);

        $result = [
            'dry_run' => $dryRun,
            'campaign_id' => $campaign->id,
            'stills_removed' => 0,
            'videos_removed' => 0,
            'thumbnail_stills_removed' => 0,
            'files_deleted' => 0,
            'sort_rebuilt' => false,
        ];

        $this->backfillStillMetadata($campaign->assets);
        $this->backfillVideoMetadata($campaign->videos);

        $thumbnailKey = $this->galleryPathKey($campaign->thumbnail_path);

        $stillResult = $this->removeDuplicateStills($campaign, $dryRun, $deleteFiles);
        $result['stills_removed'] = $stillResult['removed'];
        $result['files_deleted'] += $stillResult['files_deleted'];

        $thumbResult = $this->removeThumbnailDuplicateStills($campaign, $thumbnailKey, $dryRun, $deleteFiles);
        $result['thumbnail_stills_removed'] = $thumbResult['removed'];
        $result['files_deleted'] += $thumbResult['files_deleted'];

        $videoResult = $this->removeDuplicateVideos($campaign, $dryRun, $deleteFiles);
        $result['videos_removed'] = $videoResult['removed'];
        $result['files_deleted'] += $videoResult['files_deleted'];

        if (! $dryRun) {
            $campaign = $campaign->fresh(['assets', 'videos']);
            $this->rebuildAssetSortOrder($campaign->assets);
            $this->rebuildVideoSortOrder($campaign->videos);
            $result['sort_rebuilt'] = true;
        }

        if ($result['stills_removed'] + $result['videos_removed'] + $result['thumbnail_stills_removed'] > 0) {
            Log::info('Campaign media cleanup summary', $result);
        }

        return $result;
    }

    /**
     * @return array{
     *     campaigns_processed: int,
     *     campaigns_affected: int,
     *     stills_removed: int,
     *     videos_removed: int,
     *     thumbnail_stills_removed: int,
     *     files_deleted: int,
     *     dry_run: bool,
     * }
     */
    public function cleanAllCampaigns(
        bool $dryRun = false,
        bool $deleteFiles = true,
        ?int $limit = null,
        ?int $campaignId = null,
    ): array {
        $stats = [
            'campaigns_processed' => 0,
            'campaigns_affected' => 0,
            'stills_removed' => 0,
            'videos_removed' => 0,
            'thumbnail_stills_removed' => 0,
            'files_deleted' => 0,
            'dry_run' => $dryRun,
        ];

        $query = Campaign::query()->with(['assets', 'videos'])->orderBy('id');

        if ($campaignId !== null) {
            $query->whereKey($campaignId);
        } elseif ($limit !== null) {
            $query->limit($limit);
        }

        $query->chunkById(25, function (Collection $campaigns) use (&$stats, $dryRun, $deleteFiles) {
            foreach ($campaigns as $campaign) {
                $stats['campaigns_processed']++;
                $result = $this->cleanCampaign($campaign, $dryRun, $deleteFiles);

                $affected = $result['stills_removed']
                    + $result['videos_removed']
                    + $result['thumbnail_stills_removed'];

                if ($affected > 0) {
                    $stats['campaigns_affected']++;
                }

                $stats['stills_removed'] += $result['stills_removed'];
                $stats['videos_removed'] += $result['videos_removed'];
                $stats['thumbnail_stills_removed'] += $result['thumbnail_stills_removed'];
                $stats['files_deleted'] += $result['files_deleted'];
            }
        });

        Log::info('Campaign media bulk cleanup summary', $stats);

        return $stats;
    }

    /**
     * @return array{orphans: list<string>, deleted: int, dry_run: bool}
     */
    public function cleanupOrphanFiles(bool $dryRun = true, ?int $campaignId = null): array
    {
        $referenced = $this->collectReferencedStoragePaths($campaignId);
        $orphans = [];
        $base = config('upload.campaign_path', 'campaigns');

        if (! Storage::disk('public')->exists($base)) {
            return ['orphans' => [], 'deleted' => 0, 'dry_run' => $dryRun];
        }

        $directories = $campaignId !== null
            ? [$base.'/'.$campaignId]
            : Storage::disk('public')->directories($base);

        foreach ($directories as $directory) {
            foreach (Storage::disk('public')->allFiles($directory) as $path) {
                $normalized = strtolower(str_replace('\\', '/', $path));

                if (! isset($referenced[$normalized])) {
                    $orphans[] = $path;
                }
            }
        }

        $deleted = 0;

        if (! $dryRun) {
            foreach ($orphans as $path) {
                if (Storage::disk('public')->delete($path)) {
                    $deleted++;
                    Log::info('Orphan campaign media file removed', ['path' => $path]);
                }
            }
        }

        Log::info('Orphan campaign media scan complete', [
            'orphan_count' => count($orphans),
            'deleted' => $deleted,
            'dry_run' => $dryRun,
        ]);

        return [
            'orphans' => $orphans,
            'deleted' => $deleted,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * @param  Collection<int, CampaignAsset>  $assets
     */
    public function backfillStillMetadata(Collection $assets): void
    {
        foreach ($assets as $asset) {
            if (! $asset->isDisplayableImage()) {
                continue;
            }

            $updates = [];
            $hash = $this->resolveStillContentHash($asset);

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
     * @param  Collection<int, CampaignVideo>  $videos
     */
    public function backfillVideoMetadata(Collection $videos): void
    {
        foreach ($videos as $video) {
            $updates = [];
            $embedKey = $this->videoEmbedKey($video->type, $video->url);

            if ($embedKey !== null && $video->embed_key !== $embedKey) {
                $updates['embed_key'] = $embedKey;
            }

            if (! empty($video->url) && empty($video->source_url_key)) {
                $key = $this->sourceUrlKey($video->url);
                if ($key !== null) {
                    $updates['source_url_key'] = $key;
                }
            }

            if ($video->type === 'file' && ! empty($video->file_path)) {
                $hash = $this->resolveVideoFileHash($video->file_path);
                if ($hash !== null && $video->content_hash !== $hash) {
                    $updates['content_hash'] = $hash;
                }
            }

            if ($updates !== []) {
                $video->update($updates);
            }
        }
    }

    /**
     * @return array{removed: int, files_deleted: int}
     */
    protected function removeDuplicateStills(
        Campaign $campaign,
        bool $dryRun,
        bool $deleteFiles,
    ): array {
        $imageAssets = $campaign->assets
            ->filter(fn (CampaignAsset $asset) => $asset->isDisplayableImage())
            ->sortBy(fn (CampaignAsset $asset) => [$asset->sort_order, $asset->id])
            ->values();

        if ($imageAssets->count() < 2) {
            return ['removed' => 0, 'files_deleted' => 0];
        }

        return $this->removeGroupedDuplicates(
            $this->buildStillDuplicateGroups($imageAssets),
            $dryRun,
            $deleteFiles,
            fn (array $group) => $this->pickBestStill($group),
            'still',
            $campaign->id,
        );
    }

    /**
     * @return array{removed: int, files_deleted: int}
     */
    protected function removeThumbnailDuplicateStills(
        Campaign $campaign,
        ?string $thumbnailKey,
        bool $dryRun,
        bool $deleteFiles,
    ): array {
        if ($thumbnailKey === null) {
            return ['removed' => 0, 'files_deleted' => 0];
        }

        $removed = 0;
        $filesDeleted = 0;

        foreach ($campaign->assets as $asset) {
            if (! $asset->isDisplayableImage()) {
                continue;
            }

            if ($asset->galleryPathKey() !== $thumbnailKey) {
                continue;
            }

            if ($dryRun) {
                $removed++;

                continue;
            }

            if ($deleteFiles) {
                $filesDeleted += $this->deleteAssetFile($asset) ? 1 : 0;
            }

            $asset->delete();
            $removed++;

            Log::info('Duplicate still removed (matches campaign thumbnail)', [
                'campaign_id' => $campaign->id,
                'asset_id' => $asset->id,
                'path' => $asset->file_path,
            ]);
        }

        return ['removed' => $removed, 'files_deleted' => $filesDeleted];
    }

    /**
     * @return array{removed: int, files_deleted: int}
     */
    protected function removeDuplicateVideos(
        Campaign $campaign,
        bool $dryRun,
        bool $deleteFiles,
    ): array {
        $videos = $campaign->videos
            ->sortBy(fn (CampaignVideo $video) => [$video->sort_order, $video->id])
            ->values();

        if ($videos->count() < 2) {
            return ['removed' => 0, 'files_deleted' => 0];
        }

        return $this->removeGroupedDuplicates(
            $this->buildVideoDuplicateGroups($videos),
            $dryRun,
            $deleteFiles,
            fn (array $group) => $this->pickBestVideo($group),
            'video',
            $campaign->id,
        );
    }

    /**
     * @param  list<list<CampaignAsset>>  $groups
     * @param  callable(list<CampaignAsset>): CampaignAsset  $pickWinner
     * @return array{removed: int, files_deleted: int}
     */
    protected function removeGroupedDuplicates(
        array $groups,
        bool $dryRun,
        bool $deleteFiles,
        callable $pickWinner,
        string $mediaType,
        int $campaignId,
    ): array {
        $removed = 0;
        $filesDeleted = 0;

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            $winner = $pickWinner($group);

            foreach ($group as $item) {
                if ($item->id === $winner->id) {
                    continue;
                }

                if ($dryRun) {
                    $removed++;

                    continue;
                }

                if ($deleteFiles) {
                    if ($item instanceof CampaignAsset) {
                        $filesDeleted += $this->deleteAssetFile($item) ? 1 : 0;
                    } elseif ($item instanceof CampaignVideo) {
                        $filesDeleted += $this->deleteVideoFile($item) ? 1 : 0;
                    }
                }

                $item->delete();
                $removed++;

                Log::info('Duplicate '.$mediaType.' removed', [
                    'campaign_id' => $campaignId,
                    'removed_id' => $item->id,
                    'kept_id' => $winner->id,
                    'hash_match' => true,
                ]);
            }
        }

        return ['removed' => $removed, 'files_deleted' => $filesDeleted];
    }

    /**
     * @param  Collection<int, CampaignAsset>  $assets
     * @return list<list<CampaignAsset>>
     */
    protected function buildStillDuplicateGroups(Collection $assets): array
    {
        return $this->buildUnionGroups(
            $assets,
            fn (CampaignAsset $asset) => $this->resolveStillContentHash($asset),
            fn (CampaignAsset $asset) => $asset->source_url_key ?? $this->sourceUrlKey($asset->source_url),
        );
    }

    /**
     * @param  Collection<int, CampaignVideo>  $videos
     * @return list<list<CampaignVideo>>
     */
    protected function buildVideoDuplicateGroups(Collection $videos): array
    {
        return $this->buildUnionGroups(
            $videos,
            fn (CampaignVideo $video) => $video->content_hash ?? $this->resolveVideoFileHash($video->file_path),
            fn (CampaignVideo $video) => $video->embed_key ?? $this->videoEmbedKey($video->type, $video->url),
            fn (CampaignVideo $video) => $video->source_url_key ?? $this->sourceUrlKey($video->url),
        );
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Model
     * @param  Collection<int, T>  $items
     * @return list<list<T>>
     */
    protected function buildUnionGroups(
        Collection $items,
        ?callable $hashResolver = null,
        ?callable ...$keyResolvers
    ): array {
        $parent = [];

        foreach ($items as $item) {
            $parent[$item->id] = $item->id;
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

        $maps = [];

        foreach ($items as $item) {
            $keys = [];

            if ($hashResolver !== null) {
                $hash = $hashResolver($item);
                if ($hash !== null) {
                    $keys[] = 'hash:'.$hash;
                }
            }

            foreach ($keyResolvers as $resolver) {
                if ($resolver === null) {
                    continue;
                }

                $key = $resolver($item);
                if ($key !== null) {
                    $keys[] = 'key:'.$key;
                }
            }

            foreach ($keys as $mapKey) {
                if (isset($maps[$mapKey])) {
                    $union($item->id, $maps[$mapKey]);
                } else {
                    $maps[$mapKey] = $item->id;
                }
            }
        }

        $groups = [];

        foreach ($items as $item) {
            $root = $find($item->id);
            $groups[$root][] = $item;
        }

        return array_values($groups);
    }

    /**
     * @param  list<CampaignAsset>  $assets
     */
    protected function pickBestStill(array $assets): CampaignAsset
    {
        usort($assets, fn (CampaignAsset $a, CampaignAsset $b) => $this->compareStillPreference($a, $b));

        return $assets[0];
    }

    /**
     * @param  list<CampaignVideo>  $videos
     */
    protected function pickBestVideo(array $videos): CampaignVideo
    {
        usort($videos, fn (CampaignVideo $a, CampaignVideo $b) => $this->compareVideoPreference($a, $b));

        return $videos[0];
    }

    protected function compareStillPreference(CampaignAsset $a, CampaignAsset $b): int
    {
        // Prefer original formats (jpg/png) over legacy converted WebP duplicates.
        $webpA = $a->isWebpFile() ? 1 : 0;
        $webpB = $b->isWebpFile() ? 1 : 0;

        if ($webpA !== $webpB) {
            return $webpA <=> $webpB;
        }

        $sizeA = $this->assetFileSize($a);
        $sizeB = $this->assetFileSize($b);

        if ($sizeA !== $sizeB) {
            return $sizeA <=> $sizeB;
        }

        if ($a->sort_order !== $b->sort_order) {
            return $a->sort_order <=> $b->sort_order;
        }

        return $a->id <=> $b->id;
    }

    protected function compareVideoPreference(CampaignVideo $a, CampaignVideo $b): int
    {
        $embedA = in_array($a->type, ['youtube', 'vimeo'], true) ? 0 : 1;
        $embedB = in_array($b->type, ['youtube', 'vimeo'], true) ? 0 : 1;

        if ($embedA !== $embedB) {
            return $embedA <=> $embedB;
        }

        if ($a->sort_order !== $b->sort_order) {
            return $a->sort_order <=> $b->sort_order;
        }

        return $a->id <=> $b->id;
    }

    protected function assetFileSize(CampaignAsset $asset): int
    {
        $path = $asset->normalizedFilePath();

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return PHP_INT_MAX;
        }

        return (int) Storage::disk('public')->size($path);
    }

    /**
     * @param  Collection<int, CampaignAsset>  $assets
     */
    protected function rebuildAssetSortOrder(Collection $assets): void
    {
        $order = 0;

        foreach ($assets->sortBy(fn (CampaignAsset $asset) => [$asset->sort_order, $asset->id]) as $asset) {
            if ($asset->sort_order !== $order) {
                $asset->update(['sort_order' => $order]);
            }

            $order++;
        }
    }

    /**
     * @param  Collection<int, CampaignVideo>  $videos
     */
    protected function rebuildVideoSortOrder(Collection $videos): void
    {
        $order = 0;

        foreach ($videos->sortBy(fn (CampaignVideo $video) => [$video->sort_order, $video->id]) as $video) {
            if ($video->sort_order !== $order) {
                $video->update(['sort_order' => $order]);
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

    protected function deleteVideoFile(CampaignVideo $video): bool
    {
        if ($video->type !== 'file' || empty($video->file_path)) {
            return false;
        }

        $path = $this->normalizeStoragePath($video->file_path);

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }

    /**
     * @return array<string, true>
     */
    protected function collectReferencedStoragePaths(?int $campaignId = null): array
    {
        $referenced = [];
        $add = function (?string $path) use (&$referenced) {
            $normalized = $this->galleryPathKey($path);
            if ($normalized !== null) {
                $referenced[$normalized] = true;
            }
        };

        $campaignQuery = Campaign::query()->with(['assets', 'videos']);

        if ($campaignId !== null) {
            $campaignQuery->whereKey($campaignId);
        }

        $campaignQuery->chunkById(50, function (Collection $campaigns) use ($add) {
            foreach ($campaigns as $campaign) {
                $add($campaign->thumbnail_path);

                foreach ($campaign->assets as $asset) {
                    $add($asset->file_path);
                }

                foreach ($campaign->videos as $video) {
                    $add($video->file_path);
                }

                $add($campaign->video_file_path);
            }
        });

        return $referenced;
    }

    protected function normalizeStoragePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));

        foreach (['public/storage/', 'public/', 'storage/app/public/', 'app/public/', 'storage/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        $path = ltrim($path, '/');

        return $path !== '' && ! str_contains($path, '..') ? $path : null;
    }

    protected function imageManager(): ImageManager
    {
        if ($this->imageManager === null) {
            $this->imageManager = new ImageManager(new GdDriver());
        }

        return $this->imageManager;
    }
}
