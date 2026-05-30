<?php

namespace App\Support;

use App\Enums\AgencyCampaignRole;
use App\Enums\AgencyCompanyRole;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

trait AgencyRankableProductionHouseCampaigns
{
    protected static function applyRankableProductionHouseCampaignConstraints(Builder $query): void
    {
        $productionHousePivot = AgencyCampaignRole::ProductionHouse->value;
        $agencyPivot = AgencyCampaignRole::Agency->value;
        $productionHouseCompanyRole = AgencyCompanyRole::ProductionHouse->value;

        $query
            ->where('campaigns.status', 'approved')
            ->where('campaigns.is_draft', false)
            ->where(function (Builder $roleQuery) use ($productionHousePivot, $agencyPivot, $productionHouseCompanyRole) {
                $roleQuery->where('agency_campaign.role', $productionHousePivot)
                    ->orWhere(function (Builder $legacy) use ($agencyPivot, $productionHouseCompanyRole) {
                        $legacy->where('agency_campaign.role', $agencyPivot)
                            ->where(function (Builder $phAgency) use ($productionHouseCompanyRole) {
                                $phAgency->where('agencies.is_production_house', true)
                                    ->orWhereExists(function (Builder $roles) use ($productionHouseCompanyRole) {
                                        $roles->select(DB::raw(1))
                                            ->from('agency_roles')
                                            ->whereColumn('agency_roles.agency_id', 'agencies.id')
                                            ->where('agency_roles.role', $productionHouseCompanyRole);
                                    });
                            });
                    });
            });
    }

    protected static function rankableProductionHouseCampaignIdsSubquery(int $agencyId): Builder
    {
        return DB::table('agency_campaign')
            ->join('campaigns', 'campaigns.id', '=', 'agency_campaign.campaign_id')
            ->join('agencies', 'agencies.id', '=', 'agency_campaign.agency_id')
            ->where('agency_campaign.agency_id', $agencyId)
            ->where(function (Builder $query) {
                static::applyRankableProductionHouseCampaignConstraints($query);
            })
            ->select('campaigns.id')
            ->distinct();
    }
}
