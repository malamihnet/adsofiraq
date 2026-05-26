<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Industry;
use App\Models\MediumType;
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

        $featuredCampaigns = Campaign::public()
            ->featured()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latest('published_at')
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

        $featuredAgencies = Agency::whereHas('campaigns', fn ($q) => $q->approved())
            ->withCount(['campaigns' => fn ($q) => $q->approved()])
            ->orderByDesc('campaigns_count')
            ->take(6)
            ->get();

        return view('home', compact(
            'heroCampaigns',
            'featuredCampaigns',
            'latestCampaigns',
            'popularCategories',
            'featuredAgencies'
        ));
    }
}
