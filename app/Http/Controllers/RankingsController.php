<?php

namespace App\Http\Controllers;

use App\Services\RankingScoreService;
use Illuminate\View\View;

class RankingsController extends Controller
{
    public function __construct(
        protected RankingScoreService $rankings,
    ) {}

    public function index(): View
    {
        return view('rankings.index');
    }

    public function topAgencies(): View
    {
        $agencies = $this->rankings->topAgencies(30);

        return view('rankings.top-agencies', compact('agencies'));
    }

    public function topProductionHouses(): View
    {
        $agencies = $this->rankings->topProductionHouses(30);

        return view('rankings.top-production-houses', compact('agencies'));
    }

    public function mostViewed(): View
    {
        $campaigns = $this->rankings->topCampaignsByViews(36);

        return view('rankings.most-viewed', compact('campaigns'));
    }

    public function trending(): View
    {
        $campaigns = $this->rankings->trendingCampaigns(36);

        return view('rankings.trending', compact('campaigns'));
    }

    public function mostAppreciated(): View
    {
        $campaigns = $this->rankings->mostAppreciatedCampaigns(36);

        return view('rankings.most-appreciated', compact('campaigns'));
    }
}
