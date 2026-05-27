<?php

namespace App\Console\Commands;

use App\Services\CampaignMediaDeduplicationService;
use Illuminate\Console\Command;

class CleanupOrphanCampaignMediaCommand extends Command
{
    protected $signature = 'campaigns:cleanup-orphan-media
                            {--campaign= : Limit scan to one campaign folder ID}
                            {--dry-run : List orphan files without deleting}
                            {--execute : Delete orphan files (required to remove files)}';

    protected $description = 'Remove unreferenced files under storage/app/public/campaigns';

    public function handle(CampaignMediaDeduplicationService $mediaDedup): int
    {
        $dryRun = ! $this->option('execute') || (bool) $this->option('dry-run');
        $campaignId = $this->option('campaign') !== null ? (int) $this->option('campaign') : null;

        $result = $mediaDedup->cleanupOrphanFiles($dryRun, $campaignId);

        $this->info(sprintf(
            '%s. Orphans found: %d. Files deleted: %d.',
            $dryRun ? 'Dry run' : 'Done',
            count($result['orphans']),
            $result['deleted'],
        ));

        if ($this->output->isVerbose() && $result['orphans'] !== []) {
            foreach (array_slice($result['orphans'], 0, 50) as $path) {
                $this->line('  '.$path);
            }

            if (count($result['orphans']) > 50) {
                $this->line('  ... and '.(count($result['orphans']) - 50).' more');
            }
        }

        return self::SUCCESS;
    }
}
