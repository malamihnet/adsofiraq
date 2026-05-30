<?php

namespace App\Services;

use App\Enums\CompanyRankingProfile;
use App\Models\Agency;
use App\Models\Campaign;
use App\Support\AgencyRankableProductionHouseCampaigns;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CompanyRankingService
{
    use AgencyRankableProductionHouseCampaigns;

    /**
     * @return array{
     *     campaign_count: int,
     *     total_views: int,
     *     bookmarks: int,
     *     featured_campaigns: int,
     *     recent_campaign_count: int,
     *     ranking_score: float
     * }
     */
    public function aggregateProductionHouseStats(Agency $agency): array
    {
        $campaignIds = static::rankableProductionHouseCampaignIdsSubquery($agency->id);

        $row = DB::table('campaigns')
            ->whereIn('id', $campaignIds)
            ->selectRaw('COUNT(*) as campaign_count')
            ->selectRaw('COALESCE(SUM(views_count), 0) as total_views')
            ->selectRaw('COALESCE(SUM(bookmarks_count), 0) as bookmarks')
            ->selectRaw('COALESCE(SUM(CASE WHEN is_featured = 1 OR editorial_label IS NOT NULL THEN 1 ELSE 0 END), 0) as featured_campaigns')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN COALESCE(published_at, approved_at) >= ? THEN 1 ELSE 0 END), 0) as recent_campaign_count',
                [now()->subMonths($this->recentMonths())->toDateTimeString()],
            )
            ->first();

        $metrics = [
            'campaign_count' => (int) ($row->campaign_count ?? 0),
            'total_views' => (int) ($row->total_views ?? 0),
            'bookmarks' => (int) ($row->bookmarks ?? 0),
            'featured_campaigns' => (int) ($row->featured_campaigns ?? 0),
            'recent_campaign_count' => (int) ($row->recent_campaign_count ?? 0),
        ];

        $metrics['ranking_score'] = $this->calculateProductionHouseScore($metrics, $agency);

        return $metrics;
    }

    /**
     * @param  array<string, int|float>  $metrics
     */
    public function calculateProductionHouseScore(array $metrics, Agency $agency): float
    {
        $weights = $this->weights(CompanyRankingProfile::ProductionHouse);

        $score = 0.0;
        $score += $metrics['campaign_count'] * ($weights['campaign_count'] ?? 10);
        $score += $metrics['total_views'] * ($weights['views'] ?? 0.02);
        $score += $metrics['bookmarks'] * ($weights['bookmarks'] ?? 3);
        $score += $metrics['featured_campaigns'] * ($weights['featured_campaign'] ?? 15);

        if ($agency->is_verified) {
            $score += $weights['verified_bonus'] ?? 25;
        }

        $recentCount = (int) ($metrics['recent_campaign_count'] ?? 0);

        if ($recentCount > 0) {
            $base = $weights['recent_activity_bonus'] ?? 15;
            $perCampaign = $weights['recent_campaign_bonus'] ?? 3;
            $cap = $weights['recent_campaign_bonus_cap'] ?? 5;
            $score += $base + (min($cap, $recentCount) * $perCampaign);
        }

        return round($score, 4);
    }

    public function refreshProductionHouse(Agency $agency): void
    {
        if (! $agency->isProductionHouse()) {
            $agency->forceFill(['production_house_ranking_score' => 0])->saveQuietly();

            return;
        }

        $metrics = $this->aggregateProductionHouseStats($agency);

        $agency->forceFill([
            'production_house_ranking_score' => $metrics['ranking_score'],
        ])->saveQuietly();
    }

    public function refreshAllProductionHouses(): int
    {
        $count = 0;

        Agency::query()
            ->forTopProductionHouses()
            ->orderBy('id')
            ->chunkById(50, function ($agencies) use (&$count) {
                foreach ($agencies as $agency) {
                    $this->refreshProductionHouse($agency);
                    $count++;
                }
            });

        $this->clearProductionHouseCache();

        return $count;
    }

    /**
     * @return Collection<int, Agency>
     */
    public function topProductionHouses(int $limit = 30): Collection
    {
        $profile = CompanyRankingProfile::ProductionHouse;
        $cacheKey = $profile->cacheKey($limit);
        $ttl = (int) config('authority.company_ranking.cache_ttl_seconds', 3600);

        return Cache::remember($cacheKey, $ttl, function () use ($limit) {
            $agencies = Agency::query()
                ->forTopProductionHouses()
                ->with(['roles'])
                ->withRankableProductionHouseCampaignCount()
                ->having('production_house_campaigns_count', '>', 0)
                ->orderByDesc('production_house_ranking_score')
                ->orderByDesc('production_house_campaigns_count')
                ->orderBy('name')
                ->limit($limit)
                ->get();

            return $this->enrichProductionHouseListings($agencies);
        });
    }

    /**
     * @param  Collection<int, Agency>  $agencies
     * @return Collection<int, Agency>
     */
    protected function enrichProductionHouseListings(Collection $agencies): Collection
    {
        if ($agencies->isEmpty()) {
            return $agencies;
        }

        $featuredByAgency = $this->featuredPreviewsForAgencies($agencies->pluck('id')->all());

        return $agencies->map(function (Agency $agency) use ($featuredByAgency) {
            $metrics = $this->aggregateProductionHouseStats($agency);

            $agency->setAttribute('ranking_campaign_count', $metrics['campaign_count']);
            $agency->setAttribute('ranking_total_views', $metrics['total_views']);
            $agency->setAttribute('ranking_total_bookmarks', $metrics['bookmarks']);
            $agency->setAttribute('ranking_featured_campaigns', $metrics['featured_campaigns']);
            $agency->setAttribute('ranking_display_score', (float) $agency->production_house_ranking_score);
            $agency->setAttribute('featured_preview_campaign', $featuredByAgency->get($agency->id));

            return $agency;
        });
    }

    /**
     * @param  list<int>  $agencyIds
     * @return Collection<int, Campaign>
     */
    protected function featuredPreviewsForAgencies(array $agencyIds): Collection
    {
        $previews = collect();

        foreach ($agencyIds as $agencyId) {
            $campaignId = DB::table('agency_campaign')
                ->join('campaigns', 'campaigns.id', '=', 'agency_campaign.campaign_id')
                ->join('agencies', 'agencies.id', '=', 'agency_campaign.agency_id')
                ->where('agency_campaign.agency_id', $agencyId)
                ->where(function (Builder $query) {
                    static::applyRankableProductionHouseCampaignConstraints($query);
                })
                ->where(function (Builder $query) {
                    $query->where('campaigns.is_featured', true)
                        ->orWhereNotNull('campaigns.editorial_label');
                })
                ->orderByDesc('campaigns.published_at')
                ->orderByDesc('campaigns.approved_at')
                ->value('campaigns.id');

            if ($campaignId) {
                $previews->put($agencyId, (int) $campaignId);
            }
        }

        if ($previews->isEmpty()) {
            return collect();
        }

        $campaigns = Campaign::query()
            ->whereIn('id', $previews->values())
            ->with(['brands', 'agencies'])
            ->get()
            ->keyBy('id');

        return $previews->map(fn (int $campaignId) => $campaigns->get($campaignId))->filter();
    }

    public function clearProductionHouseCache(): void
    {
        foreach ([20, 30, 50] as $limit) {
            Cache::forget(CompanyRankingProfile::ProductionHouse->cacheKey($limit));
        }
    }

    protected function recentMonths(): int
    {
        return (int) config('authority.company_ranking.recent_months', 12);
    }

    /**
     * @return array<string, float|int>
     */
    protected function weights(CompanyRankingProfile $profile): array
    {
        return config("authority.company_ranking.profiles.{$profile->configKey()}", []);
    }
}
