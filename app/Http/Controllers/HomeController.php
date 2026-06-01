<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\MediumType;
use App\Models\Person;
use App\Services\CampaignArchiveOrderingService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected CampaignArchiveOrderingService $archiveOrdering,
    ) {}

    public function index(): View
    {
        $heroCampaigns = Campaign::public()
            ->hero()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->orderedForHero()
            ->take(6)
            ->get();

        $featuredCampaigns = Campaign::public()
            ->featured()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $latestEagerLoads = ['brands', 'agencies', 'mediumTypes'];

        $latestCampaigns = $this->archiveOrdering->take(
            Campaign::public(),
            limit: 16,
            eagerLoads: $latestEagerLoads,
        );

        $popularCategories = MediumType::withCount(['campaigns' => fn ($q) => $q->approved()])
            ->orderByDesc('campaigns_count')
            ->take(8)
            ->get();

        $featuredAgencies = Agency::query()
            ->forTopAgencies()
            ->whereHas('campaigns', fn ($q) => $q->approved())
            ->with(['roles'])
            ->withCount(['campaigns' => fn ($q) => $q->approved()])
            ->orderByDesc('campaigns_count')
            ->take(12)
            ->get();

        $productionHouses = Agency::query()
            ->forTopProductionHouses()
            ->with(['roles'])
            ->withRankableProductionHouseCampaignCount()
            ->orderByDesc('production_house_ranking_score')
            ->orderByDesc('production_house_campaigns_count')
            ->orderBy('name')
            ->limit(24)
            ->get()
            ->filter(fn (Agency $agency) => (int) ($agency->production_house_campaigns_count ?? 0) > 0)
            ->take(12)
            ->values();

        $featuredPeople = Person::public()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->take(16)
            ->get();

        return view('home', compact(
            'heroCampaigns',
            'featuredCampaigns',
            'latestCampaigns',
            'popularCategories',
            'featuredAgencies',
            'productionHouses',
            'featuredPeople',
        ));
    }
}
