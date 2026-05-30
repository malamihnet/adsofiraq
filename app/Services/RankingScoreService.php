<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Person;
use Illuminate\Support\Collection;

class RankingScoreService
{
    public function __construct(
        protected CompanyRankingService $companyRankings,
    ) {}
    /**
     * @return array<string, float>
     */
    protected function weights(): array
    {
        return config('authority.ranking.weights', []);
    }

    public function scoreCampaign(Campaign $campaign): float
    {
        $weights = $this->weights();
        $score = 0.0;

        $score += (int) $campaign->views_count * ($weights['views'] ?? 1);
        $score += (int) $campaign->bookmarks_count * ($weights['bookmarks'] ?? 5);
        $score += (int) $campaign->watchers_count * ($weights['watchers'] ?? 2);

        if ($campaign->is_featured) {
            $score += $weights['featured'] ?? 50;
        }

        if ($campaign->is_hero) {
            $score += $weights['hero'] ?? 75;
        }

        if ($campaign->editorial_label) {
            $score += $weights['editorial'] ?? 35;
        }

        if ($campaign->is_verified) {
            $score += $weights['verified'] ?? 15;
        }

        $score *= $this->recencyMultiplier($campaign->published_at?->timestamp ?? $campaign->approved_at?->timestamp);

        return round($score, 4);
    }

    protected function recencyMultiplier(?int $timestamp): float
    {
        if ($timestamp === null) {
            return 0.85;
        }

        $halfLife = (int) config('authority.ranking.recency_half_life_days', 180);
        $ageDays = max(0, (now()->timestamp - $timestamp) / 86400);

        return 0.5 + (0.5 * exp(-$ageDays / max(1, $halfLife)));
    }

    public function refreshCampaign(Campaign $campaign): void
    {
        $campaign->forceFill(['ranking_score' => $this->scoreCampaign($campaign)])->saveQuietly();
    }

    public function refreshAgency(Agency $agency): void
    {
        $score = $agency->approvedCampaignsForScoring()->sum(fn (Campaign $c) => $this->scoreCampaign($c));
        $agency->forceFill(['ranking_score' => round($score, 4)])->saveQuietly();
    }

    public function refreshBrand(Brand $brand): void
    {
        $score = $brand->approvedCampaignsForScoring()->sum(fn (Campaign $c) => $this->scoreCampaign($c));
        $brand->forceFill(['ranking_score' => round($score, 4)])->saveQuietly();
    }

    /**
     * @return Collection<int, Agency>
     */
    public function topAgencies(int $limit = 20): Collection
    {
        return Agency::query()
            ->forTopAgencies()
            ->withCount(['agencyCampaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->having('agency_campaigns_count', '>', 0)
            ->orderByDesc('ranking_score')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Agency>
     */
    public function topProductionHouses(int $limit = 20): Collection
    {
        return $this->companyRankings->topProductionHouses($limit);
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function topCampaignsByViews(int $limit = 24): Collection
    {
        return Campaign::public()
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function trendingCampaigns(int $limit = 24): Collection
    {
        return Campaign::public()
            ->orderByDesc('ranking_score')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function mostAppreciatedCampaigns(int $limit = 24): Collection
    {
        return Campaign::public()
            ->orderByDesc('bookmarks_count')
            ->limit($limit)
            ->get();
    }
}
