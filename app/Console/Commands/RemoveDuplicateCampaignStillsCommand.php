<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CampaignMediaDeduplicationService;
use Illuminate\Console\Command;

class RemoveDuplicateCampaignStillsCommand extends Command
{
    protected $signature = 'campaigns:remove-duplicate-stills
                            {--campaign= : Process a single campaign by ID}
                            {--all : Process all campaigns}
                            {--limit= : Maximum number of campaigns when using --all}';

    protected $description = 'Remove duplicate imported campaign still assets (by source URL and content hash)';

    public function handle(CampaignMediaDeduplicationService $dedupService): int
    {
        $campaignId = $this->option('campaign');

        if ($campaignId) {
            $campaign = Campaign::query()->with('assets')->findOrFail($campaignId);
            $result = $dedupService->cleanCampaign($campaign, dryRun: false, deleteFiles: true);

            $this->info(sprintf(
                'Campaign #%d: removed %d still(s), %d video(s), deleted %d file(s).',
                $campaign->id,
                $result['stills_removed'] + $result['thumbnail_stills_removed'],
                $result['videos_removed'],
                $result['files_deleted'],
            ));

            return self::SUCCESS;
        }

        if (! $this->option('all')) {
            $this->error('Specify --campaign=ID or --all');

            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $stats = $dedupService->cleanAllCampaigns(dryRun: false, deleteFiles: true, limit: $limit);

        $this->info(sprintf(
            'Done. %d campaign(s) affected, %d still(s), %d video(s), %d file(s) deleted.',
            $stats['campaigns_affected'],
            $stats['stills_removed'] + $stats['thumbnail_stills_removed'],
            $stats['videos_removed'],
            $stats['files_deleted'],
        ));

        return self::SUCCESS;
    }
}
