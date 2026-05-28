<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\Import\CleanNonGalleryImportedStillsService;
use Illuminate\Console\Command;

class CleanNonGalleryImportedStillsCommand extends Command
{
    protected $signature = 'campaigns:clean-non-gallery-stills
                            {--campaign= : Single campaign ID}
                            {--limit= : Max campaigns when scanning all}
                            {--dry-run : Report only}
                            {--keep-files : Do not delete files from storage}';

    protected $description = 'Remove imported stills that are not real gallery images on the source campaign page';

    public function handle(CleanNonGalleryImportedStillsService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deleteFiles = ! $this->option('keep-files');
        $campaignId = $this->option('campaign') ? (int) $this->option('campaign') : null;
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($campaignId) {
            $campaign = Campaign::query()->findOrFail($campaignId);
            $result = $service->cleanCampaign($campaign, $dryRun, $deleteFiles);

            if ($result['skipped']) {
                $this->warn($result['message'] ?? 'Skipped.');

                return self::FAILURE;
            }

            $this->info(sprintf(
                '%s campaign #%d: %d non-gallery still(s) removed (%d file(s) deleted). Source gallery URLs: %d.',
                $dryRun ? 'Dry run for' : 'Cleaned',
                $campaign->id,
                $result['stills_removed'],
                $result['files_deleted'],
                $result['gallery_urls'],
            ));

            return self::SUCCESS;
        }

        $stats = $service->cleanAll($dryRun, $deleteFiles, $limit);

        $this->info(sprintf(
            '%s. Processed %d campaign(s), %d affected, %d skipped. Removed %d still(s), %d file(s) deleted.',
            $dryRun ? 'Dry run complete' : 'Cleanup complete',
            $stats['campaigns_processed'],
            $stats['campaigns_affected'],
            $stats['skipped'],
            $stats['stills_removed'],
            $stats['files_deleted'],
        ));

        return self::SUCCESS;
    }
}
