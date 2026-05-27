<?php

namespace App\Services;

use App\Models\Campaign;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PublicStorageSyncService
{
    /**
     * @return array{copied: int, skipped: int, failed: int, target: ?string}
     */
    public function emptyStats(): array
    {
        return [
            'copied' => 0,
            'skipped' => 0,
            'failed' => 0,
            'target' => $this->targetRoot(),
        ];
    }

    public function sourceRoot(): string
    {
        return str_replace('\\', '/', storage_path('app/public'));
    }

    public function targetRoot(): ?string
    {
        $configured = config('filesystems.public_sync_path');

        if (is_string($configured) && trim($configured) !== '') {
            return rtrim(str_replace('\\', '/', $configured), '/');
        }

        $publicStorage = public_path('storage');

        if (is_link($publicStorage)) {
            $resolved = realpath($publicStorage);
            $appPublic = realpath($this->sourceRoot());

            if ($resolved && $appPublic && $resolved === $appPublic) {
                return null;
            }
        }

        foreach ([
            base_path('public_html/storage'),
            dirname(base_path()).'/public_html/storage',
        ] as $candidate) {
            if (is_dir($candidate)) {
                return realpath($candidate) ?: str_replace('\\', '/', $candidate);
            }
        }

        if (is_dir($publicStorage) && ! is_link($publicStorage)) {
            $appPublic = realpath($this->sourceRoot());
            $publicReal = realpath($publicStorage);

            if ($appPublic && $publicReal && $appPublic !== $publicReal) {
                return $publicReal;
            }
        }

        return null;
    }

    public function isSyncRequired(): bool
    {
        $target = $this->targetRoot();

        if ($target === null) {
            return false;
        }

        $source = realpath($this->sourceRoot());
        $targetReal = realpath($target) ?: $target;

        return ! ($source && $source === $targetReal);
    }

    /**
     * Copy a file from storage/app/public to the web-accessible storage root when missing or stale.
     */
    public function syncRelativePath(string $relativePath): bool
    {
        if (! $this->isSyncRequired()) {
            return false;
        }

        $relativePath = $this->normalizeRelativePath($relativePath);

        if ($relativePath === null) {
            return false;
        }

        $sourceFile = $this->sourceRoot().'/'.$relativePath;
        $targetFile = $this->targetRoot().'/'.$relativePath;

        if (! is_file($sourceFile)) {
            return false;
        }

        if (is_file($targetFile)
            && filesize($targetFile) === filesize($sourceFile)
            && filemtime($targetFile) >= filemtime($sourceFile)) {
            return false;
        }

        return $this->copyFile($sourceFile, $targetFile, $relativePath);
    }

    /**
     * @return array{copied: int, skipped: int, failed: int, target: ?string}
     */
    public function syncDirectory(string $relativeDirectory): array
    {
        $stats = $this->emptyStats();

        if (! $this->isSyncRequired()) {
            return $stats;
        }

        $relativeDirectory = trim(str_replace('\\', '/', $relativeDirectory), '/');
        $sourceDir = $this->sourceRoot().'/'.$relativeDirectory;

        if (! is_dir($sourceDir)) {
            return $stats;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $relative = $this->normalizeRelativePath(
                ltrim(str_replace($this->sourceRoot(), '', str_replace('\\', '/', $file->getPathname())), '/'),
            );

            if ($relative === null) {
                continue;
            }

            if ($this->syncRelativePath($relative)) {
                $stats['copied']++;
            } else {
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    /**
     * @return array{copied: int, skipped: int, failed: int, target: ?string}
     */
    public function syncCampaign(Campaign $campaign): array
    {
        $stats = $this->emptyStats();

        if (! $this->isSyncRequired()) {
            return $stats;
        }

        $campaign->loadMissing(['assets', 'videos']);

        $paths = [];

        if (! empty($campaign->thumbnail_path) && ! filter_var($campaign->thumbnail_path, FILTER_VALIDATE_URL)) {
            $paths[] = $campaign->thumbnail_path;
        }

        foreach ($campaign->assets as $asset) {
            if (! empty($asset->file_path)) {
                $paths[] = $asset->file_path;
            }
        }

        foreach ($campaign->videos as $video) {
            if (! empty($video->file_path)) {
                $paths[] = $video->file_path;
            }
        }

        $campaignPrefix = config('upload.campaign_path', 'campaigns').'/'.$campaign->id.'/';
        $stats = $this->mergeStats($stats, $this->syncDirectory(rtrim($campaignPrefix, '/')));

        foreach (array_unique($paths) as $path) {
            $normalized = $this->normalizeRelativePath($path);

            if ($normalized === null || str_starts_with($normalized, $campaignPrefix)) {
                continue;
            }

            if ($this->syncRelativePath($normalized)) {
                $stats['copied']++;
            } else {
                $stats['skipped']++;
            }
        }

        Log::info('Public storage sync: campaign complete.', [
            'campaign_id' => $campaign->id,
            'copied' => $stats['copied'],
            'skipped' => $stats['skipped'],
            'failed' => $stats['failed'],
            'target' => $stats['target'],
        ]);

        return $stats;
    }

    /**
     * @return array{copied: int, skipped: int, failed: int, target: ?string}
     */
    public function syncAll(?int $campaignId = null): array
    {
        if ($campaignId !== null) {
            $campaign = Campaign::query()->with(['assets', 'videos'])->find($campaignId);

            return $campaign ? $this->syncCampaign($campaign) : $this->emptyStats();
        }

        $stats = $this->emptyStats();
        $base = config('upload.campaign_path', 'campaigns');

        $stats = $this->mergeStats($stats, $this->syncDirectory($base));

        foreach (['campaigns/thumbnails', 'campaigns/assets', 'campaigns/videos'] as $legacyDir) {
            $stats = $this->mergeStats($stats, $this->syncDirectory($legacyDir));
        }

        Log::info('Public storage sync: full sync complete.', $stats);

        return $stats;
    }

    /**
     * @param  array{copied: int, skipped: int, failed: int, target: ?string}  $a
     * @param  array{copied: int, skipped: int, failed: int, target: ?string}  $b
     * @return array{copied: int, skipped: int, failed: int, target: ?string}
     */
    public function mergeStats(array $a, array $b): array
    {
        return [
            'copied' => $a['copied'] + $b['copied'],
            'skipped' => $a['skipped'] + $b['skipped'],
            'failed' => $a['failed'] + $b['failed'],
            'target' => $a['target'] ?? $b['target'],
        ];
    }

    public function formatStatsMessage(array $stats): string
    {
        if (! $this->isSyncRequired()) {
            return 'Public storage symlink is active; no copy sync was required.';
        }

        return sprintf(
            'Public storage synced (%d copied, %d already present, %d failed) → %s',
            $stats['copied'],
            $stats['skipped'],
            $stats['failed'],
            $stats['target'] ?? 'unknown',
        );
    }

    protected function copyFile(string $sourceFile, string $targetFile, string $relativePath): bool
    {
        $targetDir = dirname($targetFile);

        if (! is_dir($targetDir) && ! @mkdir($targetDir, 0755, true) && ! is_dir($targetDir)) {
            Log::warning('Public storage sync: could not create directory.', [
                'directory' => $targetDir,
                'relative' => $relativePath,
            ]);

            return false;
        }

        if (! @copy($sourceFile, $targetFile)) {
            Log::warning('Public storage sync: copy failed.', [
                'from' => $sourceFile,
                'to' => $targetFile,
                'relative' => $relativePath,
            ]);

            return false;
        }

        @chmod($targetFile, 0644);

        Log::info('Public storage sync: copied file.', [
            'relative' => $relativePath,
            'public_path' => 'storage/'.$relativePath,
            'target' => $targetFile,
        ]);

        return true;
    }

    protected function normalizeRelativePath(string $path): ?string
    {
        $path = str_replace('\\', '/', trim($path));

        if ($path === '' || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        foreach (['public/storage/', 'storage/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        return ltrim($path, '/');
    }
}
