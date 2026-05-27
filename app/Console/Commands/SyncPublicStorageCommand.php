<?php

namespace App\Console\Commands;

use App\Services\PublicStorageSyncService;
use Illuminate\Console\Command;

class SyncPublicStorageCommand extends Command
{
    protected $signature = 'storage:sync-public
                            {--campaign= : Sync media for a single campaign ID}';

    protected $description = 'Copy campaign media from storage/app/public to the web-accessible public storage path';

    public function handle(PublicStorageSyncService $syncService): int
    {
        if (! $syncService->isSyncRequired()) {
            $this->info('Public storage symlink is active; sync not required.');

            return self::SUCCESS;
        }

        $campaignId = $this->option('campaign') !== null ? (int) $this->option('campaign') : null;
        $stats = $syncService->syncAll($campaignId);

        $this->info($syncService->formatStatsMessage($stats));

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
