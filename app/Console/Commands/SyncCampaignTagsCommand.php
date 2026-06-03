<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CampaignTagService;
use Illuminate\Console\Command;

class SyncCampaignTagsCommand extends Command
{
    protected $signature = 'campaigns:sync-tags {--campaign= : Sync a single campaign ID}';

    protected $description = 'Rebuild auto tags for approved public campaigns';

    public function handle(CampaignTagService $tags): int
    {
        $query = Campaign::public()->orderBy('id');

        if ($id = $this->option('campaign')) {
            $query->whereKey((int) $id);
        }

        $count = 0;

        $query->chunkById(50, function ($campaigns) use ($tags, &$count) {
            foreach ($campaigns as $campaign) {
                $tags->syncForCampaign($campaign);
                $count++;
            }
        });

        $this->info("Synced tags for {$count} campaign(s).");

        return self::SUCCESS;
    }
}
