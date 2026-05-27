<?php

namespace App\Console\Commands;

use App\Services\CampaignMediaDeduplicationService;
use Illuminate\Console\Command;

class CleanDuplicateCampaignMediaCommand extends Command
{
    protected $signature = 'campaigns:clean-duplicate-media
                            {--campaign= : Process a single campaign by ID}
                            {--all : Process all campaigns}
                            {--limit= : Maximum campaigns when using --all}
                            {--dry-run : Report duplicates without deleting}
                            {--no-delete-files : Keep duplicate files on disk when deleting records}';

    protected $description = 'Remove duplicate campaign stills, videos, and thumbnail-as-still duplicates';

    public function handle(CampaignMediaDeduplicationService $mediaDedup): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deleteFiles = ! $this->option('no-delete-files') && ! $dryRun;
        $campaignId = $this->option('campaign') !== null ? (int) $this->option('campaign') : null;

        if ($campaignId === null && ! $this->option('all')) {
            $this->error('Specify --campaign=ID or --all');

            return self::FAILURE;
        }

        $stats = $mediaDedup->cleanAllCampaigns(
            dryRun: $dryRun,
            deleteFiles: $deleteFiles,
            limit: $this->option('limit') !== null ? (int) $this->option('limit') : null,
            campaignId: $campaignId,
        );

        $this->info(sprintf(
            '%s. Processed %d, affected %d. Stills %d, videos %d, thumbnail-stills %d, files deleted %d.',
            $dryRun ? 'Dry run' : 'Done',
            $stats['campaigns_processed'],
            $stats['campaigns_affected'],
            $stats['stills_removed'],
            $stats['videos_removed'],
            $stats['thumbnail_stills_removed'],
            $stats['files_deleted'],
        ));

        return self::SUCCESS;
    }
}
