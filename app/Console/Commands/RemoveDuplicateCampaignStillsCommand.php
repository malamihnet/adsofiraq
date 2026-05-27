<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CampaignAssetDedupService;
use Illuminate\Console\Command;

class RemoveDuplicateCampaignStillsCommand extends Command
{
    protected $signature = 'campaigns:remove-duplicate-stills
                            {--campaign= : Process a single campaign by ID}
                            {--all : Process all campaigns}
                            {--limit= : Maximum number of campaigns when using --all}';

    protected $description = 'Remove duplicate imported campaign still assets (by source URL and content hash)';

    public function handle(CampaignAssetDedupService $dedupService): int
    {
        $campaignId = $this->option('campaign');

        if ($campaignId) {
            $campaign = Campaign::query()->with('assets')->findOrFail($campaignId);
            $result = $dedupService->removeDuplicateStillsForCampaign($campaign);

            $this->info(sprintf(
                'Campaign #%d: removed %d duplicate still(s), deleted %d file(s).',
                $campaign->id,
                $result['removed'],
                $result['files_deleted'],
            ));

            return self::SUCCESS;
        }

        if (! $this->option('all')) {
            $this->error('Specify --campaign=ID or --all');

            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $stats = $dedupService->removeDuplicateStillsAll($limit);

        $this->info(sprintf(
            'Done. %d campaign(s) cleaned, %d duplicate still(s) removed, %d file(s) deleted.',
            $stats['campaigns'],
            $stats['removed'],
            $stats['files_deleted'],
        ));

        return self::SUCCESS;
    }
}
