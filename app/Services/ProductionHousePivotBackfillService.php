<?php

namespace App\Services;

use App\Enums\AgencyCampaignRole;
use App\Models\Agency;
use Illuminate\Support\Facades\DB;

class ProductionHousePivotBackfillService
{
    public function backfill(bool $dryRun = false): int
    {
        $created = 0;

        Agency::query()
            ->forTopProductionHouses()
            ->orderBy('id')
            ->chunkById(50, function ($agencies) use ($dryRun, &$created) {
                foreach ($agencies as $agency) {
                    $created += $this->backfillAgency($agency, $dryRun);
                }
            });

        return $created;
    }

    public function backfillAgency(Agency $agency, bool $dryRun = false): int
    {
        if (! $agency->isProductionHouse()) {
            return 0;
        }

        $legacyRows = DB::table('agency_campaign')
            ->where('agency_id', $agency->id)
            ->where('role', AgencyCampaignRole::Agency->value)
            ->get(['campaign_id', 'agency_id', 'created_at']);

        $inserted = 0;
        $now = now();

        foreach ($legacyRows as $row) {
            $exists = DB::table('agency_campaign')
                ->where('campaign_id', $row->campaign_id)
                ->where('agency_id', $row->agency_id)
                ->where('role', AgencyCampaignRole::ProductionHouse->value)
                ->exists();

            if ($exists) {
                continue;
            }

            if (! $dryRun) {
                DB::table('agency_campaign')->insert([
                    'campaign_id' => $row->campaign_id,
                    'agency_id' => $row->agency_id,
                    'role' => AgencyCampaignRole::ProductionHouse->value,
                    'created_at' => $row->created_at ?? $now,
                    'updated_at' => $now,
                ]);
            }

            $inserted++;
        }

        return $inserted;
    }
}
