<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\Import\RepairImportedCampaignMedia;
use Illuminate\Console\Command;

class RepairImportedCampaignMediaCommand extends Command
{
    protected $signature = 'campaigns:repair-import-media
                            {--campaign= : Repair a single campaign by ID}
                            {--replace : Remove prior imported media files before re-downloading}
                            {--all : Repair every imported campaign that needs media}
                            {--limit= : Maximum number of campaigns to process}';

    protected $description = 'Re-download missing media for campaigns imported from external URLs';

    public function handle(RepairImportedCampaignMedia $repairService): int
    {
        $campaignId = $this->option('campaign');

        if ($campaignId) {
            $campaign = Campaign::query()->with('assets')->findOrFail($campaignId);
            $result = $repairService->repair($campaign, (bool) $this->option('replace'));

            $this->info(sprintf(
                'Campaign #%d: stills added %d, thumbnail %s%s',
                $campaign->id,
                $result['stills_added'],
                $result['thumbnail_updated'] ? 'updated' : 'unchanged',
                $result['message'] ? ' — '.$result['message'] : '',
            ));

            return self::SUCCESS;
        }

        if (! $this->option('all')) {
            $this->error('Specify --campaign=ID or --all');

            return self::FAILURE;
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $stats = $repairService->repairAll((bool) $this->option('replace'), $limit);

        $this->info(sprintf(
            'Done. Repaired: %d, skipped: %d, failed: %d.',
            $stats['repaired'],
            $stats['skipped'],
            $stats['failed'],
        ));

        return self::SUCCESS;
    }
}
