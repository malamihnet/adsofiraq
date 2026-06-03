<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\MediumType;
use App\Models\Person;
use Illuminate\Support\Collection;

class SeoLandingDataService
{
    public function __construct(
        protected RankingScoreService $rankings,
        protected PersonRankingService $personRankings,
    ) {}

    /**
     * @return array{
     *     title: string,
     *     intro: string,
     *     meta_description: string,
     *     latestCampaigns: Collection,
     *     topAgencies: Collection,
     *     productionHouses: Collection,
     *     brands: Collection,
     *     people: Collection,
     * }
     */
    public function iraqiAdvertising(): array
    {
        return [
            'title' => 'Iraqi Advertising | Ads Of Iraq',
            'intro' => 'Ads Of Iraq documents the creative output of Iraq\'s advertising industry: TV spots, digital campaigns, print, and branded content from agencies, production houses, and brands across the country.',
            'meta_description' => 'Explore Iraqi advertising campaigns, agencies, production companies, and creative talent on Ads Of Iraq.',
            'latestCampaigns' => $this->latestCampaigns(),
            'topAgencies' => $this->rankings->topAgencies(8),
            'productionHouses' => $this->rankings->topProductionHouses(8),
            'brands' => $this->topBrands(8),
            'people' => $this->featuredPeople(8),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function iraqAgencies(): array
    {
        $agencies = $this->rankings->topAgencies(12);

        return [
            'title' => 'Iraq Advertising Agencies | Ads Of Iraq',
            'intro' => 'Leading Iraqi advertising agencies ranked by approved campaigns, audience engagement, and editorial recognition on Ads Of Iraq.',
            'meta_description' => 'Discover top advertising agencies in Iraq and browse their campaigns on Ads Of Iraq.',
            'latestCampaigns' => $this->latestCampaignsForAgencies($agencies),
            'topAgencies' => $agencies,
            'productionHouses' => collect(),
            'brands' => collect(),
            'people' => collect(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function iraqProductionHouses(): array
    {
        $houses = $this->rankings->topProductionHouses(12);

        return [
            'title' => 'Iraq Production Houses | Ads Of Iraq',
            'intro' => 'Production companies and post houses behind Iraq\'s TV commercials, branded films, and campaign content.',
            'meta_description' => 'Browse Iraq production houses and the campaigns they helped create on Ads Of Iraq.',
            'latestCampaigns' => $this->latestCampaigns(),
            'topAgencies' => collect(),
            'productionHouses' => $houses,
            'brands' => collect(),
            'people' => collect(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function iraqCommercials(): array
    {
        return [
            'title' => 'Iraq Commercials | Ads Of Iraq',
            'intro' => 'Television and film commercials from Iraq\'s advertising archive, ranked by views, saves, and editorial picks.',
            'meta_description' => 'Watch Iraqi TV commercials and branded films on Ads Of Iraq.',
            'latestCampaigns' => $this->commercialCampaigns(12),
            'topAgencies' => $this->rankings->topAgencies(6),
            'productionHouses' => $this->rankings->topProductionHouses(6),
            'brands' => $this->topBrands(6),
            'people' => collect(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function iraqTvCommercials(): array
    {
        return [
            'title' => 'Iraq TV Commercials | Ads Of Iraq',
            'intro' => 'Broadcast and television advertising from Iraq: spots, promos, and campaign films tagged for TV and video.',
            'meta_description' => 'Browse Iraqi TV commercials and broadcast advertising on Ads Of Iraq.',
            'latestCampaigns' => $this->tvCommercialCampaigns(12),
            'topAgencies' => $this->rankings->topAgencies(6),
            'productionHouses' => $this->rankings->topProductionHouses(6),
            'brands' => collect(),
            'people' => collect(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function iraqCreativeIndustry(): array
    {
        return [
            'title' => 'Iraq Creative Industry | Ads Of Iraq',
            'intro' => 'Agencies, directors, editors, brands, and production partners shaping Iraq\'s creative advertising landscape.',
            'meta_description' => 'Explore Iraq\'s creative advertising industry: people, agencies, brands, and campaigns on Ads Of Iraq.',
            'latestCampaigns' => $this->latestCampaigns(8),
            'topAgencies' => $this->rankings->topAgencies(6),
            'productionHouses' => $this->rankings->topProductionHouses(6),
            'brands' => $this->topBrands(6),
            'people' => $this->personRankings->topDirectors(8),
        ];
    }

    /**
     * @return Collection<int, Campaign>
     */
    protected function latestCampaigns(int $limit = 12): Collection
    {
        return Campaign::public()
            ->with(['brands', 'agencies', 'productionHouses'])
            ->latestOnPlatform()
            ->limit($limit)
            ->get();
    }

    /**
     * @param  Collection<int, Agency>  $agencies
     * @return Collection<int, Campaign>
     */
    protected function latestCampaignsForAgencies(Collection $agencies): Collection
    {
        $ids = $agencies->pluck('id');

        if ($ids->isEmpty()) {
            return $this->latestCampaigns();
        }

        return Campaign::public()
            ->whereHas('agencies', fn ($q) => $q->whereIn('agencies.id', $ids))
            ->with(['brands', 'agencies'])
            ->latestOnPlatform()
            ->limit(12)
            ->get();
    }

    /**
     * @return Collection<int, Brand>
     */
    protected function topBrands(int $limit): Collection
    {
        return Brand::query()
            ->withCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->having('campaigns_count', '>', 0)
            ->orderByDesc('ranking_score')
            ->orderByDesc('campaigns_count')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Person>
     */
    protected function featuredPeople(int $limit): Collection
    {
        return Person::public()
            ->orderByDesc('ranking_score')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Campaign>
     */
    protected function commercialCampaigns(int $limit): Collection
    {
        return $this->campaignsByMediumPatterns(['commercial', 'tv', 'television', 'film', 'video'], $limit);
    }

    /**
     * @return Collection<int, Campaign>
     */
    protected function tvCommercialCampaigns(int $limit): Collection
    {
        return $this->campaignsByMediumPatterns(['tv', 'television', 'broadcast'], $limit);
    }

    /**
     * @param  list<string>  $patterns
     * @return Collection<int, Campaign>
     */
    protected function campaignsByMediumPatterns(array $patterns, int $limit): Collection
    {
        $mediumIds = MediumType::query()
            ->where(function ($query) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $query->orWhere('name', 'like', '%'.$pattern.'%')
                        ->orWhere('slug', 'like', '%'.$pattern.'%');
                }
            })
            ->pluck('id');

        $query = Campaign::public()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->orderByDesc('ranking_score');

        if ($mediumIds->isNotEmpty()) {
            $query->whereHas('mediumTypes', fn ($q) => $q->whereIn('medium_types.id', $mediumIds));
        }

        return $query->limit($limit)->get();
    }
}
