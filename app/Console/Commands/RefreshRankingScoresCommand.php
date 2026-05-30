<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Services\CompanyRankingService;
use App\Services\RankingScoreService;
use Illuminate\Console\Command;

class RefreshRankingScoresCommand extends Command
{
    protected $signature = 'authority:refresh-rankings';

    protected $description = 'Recalculate ranking scores for campaigns, agencies, and brands';

    public function handle(RankingScoreService $rankings, CompanyRankingService $companyRankings): int
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

        $productionHouses = $companyRankings->refreshAllProductionHouses();

        Brand::query()->chunkById(50, function ($brands) use ($rankings) {
            foreach ($brands as $brand) {
                $rankings->refreshBrand($brand);
            }
        });

        $this->info('Ranking scores refreshed.');
        $this->info("Production house rankings recalculated for {$productionHouses} companies.");

        return self::SUCCESS;
    }
}
