<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\MediumType;
use App\Models\Person;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $heroCampaigns = Campaign::public()
            ->hero()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->orderedForHero()
            ->take(6)
            ->get();

        $editorsPickCampaigns = Campaign::public()
            ->editorsPick()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latestOnPlatform()
            ->take(6)
            ->get();

        $latestCampaigns = Campaign::public()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latestOnPlatform()
            ->take(16)
            ->get();

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
            ->take(18)
            ->get();

        $productionHouses = Agency::query()
            ->forTopProductionHouses()
            ->with(['roles'])
            ->withRankableProductionHouseCampaignCount()
            ->orderByDesc('production_house_ranking_score')
            ->orderByDesc('production_house_campaigns_count')
            ->orderBy('name')
            ->limit(36)
            ->get()
            ->filter(fn (Agency $agency) => (int) ($agency->production_house_campaigns_count ?? 0) > 0)
            ->take(18)
            ->values();

        $featuredPeople = Person::public()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->take(18)
            ->get();

        return view('home', compact(
            'heroCampaigns',
            'editorsPickCampaigns',
            'latestCampaigns',
            'popularCategories',
            'featuredAgencies',
            'productionHouses',
            'featuredPeople',
        ));
    }
}
