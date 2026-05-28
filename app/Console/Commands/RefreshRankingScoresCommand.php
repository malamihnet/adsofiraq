<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Services\RankingScoreService;
use Illuminate\Console\Command;

class RefreshRankingScoresCommand extends Command
{
    protected $signature = 'authority:refresh-rankings';

    protected $description = 'Recalculate ranking scores for campaigns, agencies, and brands';

    public function handle(RankingScoreService $rankings): int
    {
        Campaign::query()->chunkById(100, function ($campaigns) use ($rankings) {
            foreach ($campaigns as $campaign) {
                $rankings->refreshCampaign($campaign);
            }
        });

        Agency::query()->chunkById(50, function ($agencies) use ($rankings) {
            foreach ($agencies as $agency) {
                $rankings->refreshAgency($agency);
            }
        });

        Brand::query()->chunkById(50, function ($brands) use ($rankings) {
            foreach ($brands as $brand) {
                $rankings->refreshBrand($brand);
            }
        });

        $this->info('Ranking scores refreshed.');

        return self::SUCCESS;
    }
}
