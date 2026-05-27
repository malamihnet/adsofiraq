<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Campaign;
use App\Models\CampaignAsset;
use App\Models\CampaignRevision;
use App\Models\CampaignVideo;
use App\Models\CampaignWatcher;
use App\Models\ImportBatch;
use App\Models\ImportQueueItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampaignResetService
{
    public const CONFIRMATION_PHRASE = 'DELETE ALL CAMPAIGNS';

    public const CACHE_PREFIX = 'campaign_reset:';

    public const CACHE_TTL_SECONDS = 86400;

    public function __construct(
        protected PublicStorageSyncService $publicStorageSync,
    ) {}

    /**
     * @return array{
     *     campaigns: int,
     *     assets: int,
     *     videos: int,
     *     bookmarks: int,
     *     watchers: int,
     *     revisions: int,
     *     import_batches: int,
     *     import_queue: int,
     *     media_files: int,
     * }
     */
    public function gatherCounts(): array
    {
        return [
            'campaigns' => Campaign::withTrashed()->count(),
            'assets' => CampaignAsset::query()->count(),
            'videos' => CampaignVideo::query()->count(),
            'bookmarks' => Bookmark::query()->count(),
            'watchers' => CampaignWatcher::query()->count(),
            'revisions' => CampaignRevision::query()->count(),
            'import_batches' => ImportBatch::query()->count(),
            'import_queue' => ImportQueueItem::query()->count(),
            'media_files' => $this->countMediaFiles(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function startSession(int $userId, bool $dryRun = false): array
    {
        $sessionId = (string) Str::uuid();
        $counts = $this->gatherCounts();

        $state = [
            'id' => $sessionId,
            'user_id' => $userId,
            'dry_run' => $dryRun,
            'status' => $dryRun ? 'completed' : 'running',
            'paused' => false,
            'phase' => $dryRun ? 'done' : 'import_data',
            'counts' => $counts,
            'processed' => [
                'import_batches' => 0,
                'import_queue' => 0,
                'campaigns' => 0,
                'storage_files' => 0,
            ],
            'total_campaigns' => $counts['campaigns'],
            'last_campaign_id' => null,
            'last_action' => $dryRun ? 'dry_run_complete' : 'started',
            'last_error' => null,
            'completed' => $dryRun,
            'completed_at' => $dryRun ? now()->toIso8601String() : null,
            'storage_file_queue' => [],
            'storage_queue_built' => false,
        ];

        if ($dryRun) {
            $state['message'] = 'Dry run complete. No data was deleted.';
        }

        $this->saveSession($sessionId, $state);

        Log::info('Campaign reset session started', [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'dry_run' => $dryRun,
            'counts' => $counts,
        ]);

        return $state;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSession(string $sessionId): ?array
    {
        return Cache::get(self::CACHE_PREFIX.$sessionId);
    }

    /**
     * @return array{ok: bool, progress: array<string, mixed>, error?: string}
     */
    public function tick(string $sessionId): array
    {
        $state = $this->getSession($sessionId);

        if ($state === null) {
            return ['ok' => false, 'progress' => [], 'error' => 'Reset session not found or expired.'];
        }

        if ($state['completed'] ?? false) {
            return ['ok' => true, 'progress' => $this->progressArray($state)];
        }

        if ($state['paused'] ?? false) {
            return ['ok' => true, 'progress' => $this->progressArray($state)];
        }

        if ($state['dry_run'] ?? false) {
            return ['ok' => true, 'progress' => $this->progressArray($state)];
        }

        try {
            $state = match ($state['phase']) {
                'import_data' => $this->tickImportData($state),
                'campaigns' => $this->tickDeleteCampaign($state),
                'storage' => $this->tickStorageWipe($state),
                'caches' => $this->tickClearCaches($state),
                'done' => $state,
                default => $this->tickImportData($state),
            };

            if (($state['phase'] ?? '') === 'done') {
                $state['completed'] = true;
                $state['completed_at'] = now()->toIso8601String();
                $state['status'] = 'completed';
                $state['last_action'] = 'completed';

                Log::warning('Campaign reset completed', [
                    'session_id' => $sessionId,
                    'processed' => $state['processed'] ?? [],
                ]);
            }

            $this->saveSession($sessionId, $state);

            return ['ok' => true, 'progress' => $this->progressArray($state)];
        } catch (\Throwable $e) {
            $state['last_error'] = $e->getMessage();
            $state['last_action'] = 'error';
            $this->saveSession($sessionId, $state);

            Log::error('Campaign reset tick failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'progress' => $this->progressArray($state), 'error' => $e->getMessage()];
        }
    }

    public function pause(string $sessionId): ?array
    {
        $state = $this->getSession($sessionId);

        if ($state === null) {
            return null;
        }

        $state['paused'] = true;
        $state['status'] = 'paused';
        $this->saveSession($sessionId, $state);

        return $this->progressArray($state);
    }

    public function resume(string $sessionId): ?array
    {
        $state = $this->getSession($sessionId);

        if ($state === null) {
            return null;
        }

        $state['paused'] = false;
        $state['status'] = 'running';
        $this->saveSession($sessionId, $state);

        return $this->progressArray($state);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected function tickImportData(array $state): array
    {
        if (($state['processed']['import_queue'] ?? 0) === 0) {
            $count = ImportQueueItem::query()->count();

            if (! ($state['dry_run'] ?? false)) {
                ImportQueueItem::query()->delete();
            }

            $state['processed']['import_queue'] = $count;
            $state['last_action'] = ($state['dry_run'] ?? false)
                ? 'would_clear_import_queue'
                : 'cleared_import_queue';

            Log::info('Campaign reset: import queue cleared', ['rows' => $count, 'dry_run' => $state['dry_run'] ?? false]);

            return $state;
        }

        $batchCount = ImportBatch::query()->count();

        if (! ($state['dry_run'] ?? false)) {
            ImportBatch::query()->delete();
        }

        $state['processed']['import_batches'] = $batchCount;
        $state['phase'] = 'campaigns';
        $state['last_action'] = ($state['dry_run'] ?? false)
            ? 'would_clear_import_batches'
            : 'cleared_import_batches';

        Log::info('Campaign reset: import batches cleared', ['rows' => $batchCount, 'dry_run' => $state['dry_run'] ?? false]);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected function tickDeleteCampaign(array $state): array
    {
        $campaign = Campaign::withTrashed()->orderBy('id')->first();

        if ($campaign === null) {
            $state['phase'] = 'storage';
            $state['storage_queue_built'] = false;
            $state['last_action'] = 'campaigns_deleted';

            return $state;
        }

        $campaignId = $campaign->id;

        if (! ($state['dry_run'] ?? false)) {
            $campaign->forceDelete();
        }

        $state['processed']['campaigns'] = ($state['processed']['campaigns'] ?? 0) + 1;
        $state['last_campaign_id'] = $campaignId;
        $state['last_action'] = ($state['dry_run'] ?? false)
            ? 'would_delete_campaign'
            : 'deleted_campaign';

        Log::info('Campaign reset: campaign removed', [
            'campaign_id' => $campaignId,
            'dry_run' => $state['dry_run'] ?? false,
        ]);

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected function tickStorageWipe(array $state): array
    {
        if (! ($state['storage_queue_built'] ?? false)) {
            $state['storage_file_queue'] = $this->buildStorageDeletionQueue();
            $state['storage_queue_built'] = true;
            $state['last_action'] = 'storage_queue_built';

            if ($state['storage_file_queue'] === []) {
                $state['phase'] = 'caches';
                $state['last_action'] = 'storage_empty';

                return $state;
            }

            return $state;
        }

        $queue = $state['storage_file_queue'] ?? [];
        $batchSize = 40;
        $slice = array_splice($queue, 0, $batchSize);
        $deleted = 0;

        foreach ($slice as $path) {
            if ($state['dry_run'] ?? false) {
                $deleted++;

                continue;
            }

            if ($this->deleteStoragePath($path)) {
                $deleted++;
            }
        }

        $state['storage_file_queue'] = $queue;
        $state['processed']['storage_files'] = ($state['processed']['storage_files'] ?? 0) + $deleted;
        $state['last_action'] = ($state['dry_run'] ?? false)
            ? 'would_delete_storage_files'
            : 'deleted_storage_files';

        if ($queue === []) {
            if (! ($state['dry_run'] ?? false)) {
                $this->removeEmptyMediaDirectories();
            }

            $state['phase'] = 'caches';
            $state['last_action'] = 'storage_wiped';
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    protected function tickClearCaches(array $state): array
    {
        if (! ($state['dry_run'] ?? false)) {
            app(CampaignArchiveOrderingService::class)->clearCache();
            Cache::forget('hero_campaigns');
        }

        $state['phase'] = 'done';
        $state['last_action'] = ($state['dry_run'] ?? false)
            ? 'would_clear_caches'
            : 'cleared_caches';

        return $state;
    }

    /**
     * @return list<string> Absolute or disk-relative paths to delete
     */
    protected function buildStorageDeletionQueue(): array
    {
        $paths = [];

        $diskDirs = [
            'campaigns',
            'campaign-revisions',
            'campaigns/thumbnails',
            'campaigns/assets',
            'campaigns/videos',
        ];

        foreach ($diskDirs as $dir) {
            if (! Storage::disk('public')->exists($dir)) {
                continue;
            }

            foreach (Storage::disk('public')->allFiles($dir) as $file) {
                $paths[] = 'disk:'.$file;
            }
        }

        foreach ($this->publicSyncMediaRoots() as $root) {
            if (! is_dir($root)) {
                continue;
            }

            foreach (['campaigns', 'campaign-revisions'] as $subdir) {
                $full = $root.'/'.$subdir;

                if (! is_dir($full)) {
                    continue;
                }

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($full, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST,
                );

                foreach ($iterator as $item) {
                    if ($item->isFile()) {
                        $paths[] = 'abs:'.str_replace('\\', '/', $item->getPathname());
                    }
                }
            }
        }

        return array_values(array_unique($paths));
    }

    protected function deleteStoragePath(string $path): bool
    {
        if (str_starts_with($path, 'disk:')) {
            $relative = substr($path, 5);

            return Storage::disk('public')->exists($relative)
                && Storage::disk('public')->delete($relative);
        }

        if (str_starts_with($path, 'abs:')) {
            $absolute = substr($path, 4);

            if (! is_file($absolute)) {
                return false;
            }

            return @unlink($absolute);
        }

        return false;
    }

    protected function removeEmptyMediaDirectories(): void
    {
        foreach (['campaigns', 'campaign-revisions', 'campaigns/thumbnails', 'campaigns/assets', 'campaigns/videos'] as $dir) {
            if (Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->deleteDirectory($dir);
            }
        }

        foreach ($this->publicSyncMediaRoots() as $root) {
            foreach (['campaigns', 'campaign-revisions'] as $subdir) {
                $full = $root.'/'.$subdir;

                if (is_dir($full)) {
                    File::deleteDirectory($full);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function publicSyncMediaRoots(): array
    {
        $roots = [];
        $target = $this->publicStorageSync->targetRoot();

        if ($target !== null) {
            $roots[] = rtrim(str_replace('\\', '/', $target), '/');
        }

        $publicStorage = public_path('storage');

        if (is_dir($publicStorage)) {
            $resolved = realpath($publicStorage) ?: $publicStorage;
            $appPublic = realpath(storage_path('app/public'));

            if (! $appPublic || $resolved !== $appPublic) {
                $roots[] = str_replace('\\', '/', $resolved);
            }
        }

        return array_values(array_unique($roots));
    }

    protected function countMediaFiles(): int
    {
        $count = 0;

        foreach (['campaigns', 'campaign-revisions', 'campaigns/thumbnails', 'campaigns/assets', 'campaigns/videos'] as $dir) {
            if (Storage::disk('public')->exists($dir)) {
                $count += count(Storage::disk('public')->allFiles($dir));
            }
        }

        foreach ($this->publicSyncMediaRoots() as $root) {
            foreach (['campaigns', 'campaign-revisions'] as $subdir) {
                $full = $root.'/'.$subdir;

                if (! is_dir($full)) {
                    continue;
                }

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($full, \FilesystemIterator::SKIP_DOTS),
                );

                foreach ($iterator as $item) {
                    if ($item->isFile()) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected function saveSession(string $sessionId, array $state): void
    {
        Cache::put(self::CACHE_PREFIX.$sessionId, $state, self::CACHE_TTL_SECONDS);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public function progressArray(array $state): array
    {
        $totalCampaigns = max(1, (int) ($state['total_campaigns'] ?? 1));
        $deletedCampaigns = (int) ($state['processed']['campaigns'] ?? 0);
        $queueTotal = count($state['storage_file_queue'] ?? [])
            + (int) ($state['processed']['storage_files'] ?? 0);
        $storageDone = (int) ($state['processed']['storage_files'] ?? 0);

        $phase = $state['phase'] ?? 'import_data';
        $phaseWeights = [
            'import_data' => 5,
            'campaigns' => 70,
            'storage' => 20,
            'caches' => 3,
            'done' => 2,
        ];

        $percent = 0;

        if ($state['completed'] ?? false) {
            $percent = 100;
        } elseif ($phase === 'import_data') {
            $percent = min(5, (int) (($state['processed']['import_queue'] ?? 0) > 0 ? 3 : 0)
                + (($state['processed']['import_batches'] ?? 0) > 0 ? 5 : 0));
        } elseif ($phase === 'campaigns') {
            $percent = 5 + (int) round(($deletedCampaigns / $totalCampaigns) * 70);
        } elseif ($phase === 'storage') {
            $percent = 75 + ($queueTotal > 0
                ? (int) round(($storageDone / max(1, $queueTotal)) * 20)
                : 20);
        } elseif ($phase === 'caches') {
            $percent = 97;
        }

        return [
            'session_id' => $state['id'] ?? null,
            'dry_run' => (bool) ($state['dry_run'] ?? false),
            'status' => $state['status'] ?? 'running',
            'paused' => (bool) ($state['paused'] ?? false),
            'completed' => (bool) ($state['completed'] ?? false),
            'phase' => $phase,
            'percent' => min(100, $percent),
            'counts' => $state['counts'] ?? $this->gatherCounts(),
            'processed' => $state['processed'] ?? [],
            'last_campaign_id' => $state['last_campaign_id'] ?? null,
            'last_action' => $state['last_action'] ?? null,
            'last_error' => $state['last_error'] ?? null,
            'completed_at' => $state['completed_at'] ?? null,
            'storage_remaining' => count($state['storage_file_queue'] ?? []),
            'message' => $state['message'] ?? null,
        ];
    }
}
