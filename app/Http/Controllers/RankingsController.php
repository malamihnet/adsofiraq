<?php

namespace App\Http\Controllers;

use App\Services\PersonRankingService;
use App\Services\RankingScoreService;
use App\Services\SeoService;
use Illuminate\View\View;

class RankingsController extends Controller
{
    public function __construct(
        protected RankingScoreService $rankings,
        protected PersonRankingService $personRankings,
        protected SeoService $seo,
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

    public function topDirectors(): View
    {
        $people = $this->personRankings->topDirectors(30);
        $seo = $this->seo->forRankingPage(
            'Top Directors in Iraq | Ads Of Iraq',
            'Ranked list of Iraqi advertising directors by campaign count, views, saves, and editorial recognition.',
            route('rankings.top-directors'),
        );

        return view('rankings.top-people', [
            'people' => $people,
            'heading' => 'Top Directors in Iraq',
            'intro' => 'Directors ranked by approved campaign credits, audience engagement, and editorial picks.',
            'seo' => $seo,
        ]);
    }

    public function topEditors(): View
    {
        $people = $this->personRankings->topEditors(30);
        $seo = $this->seo->forRankingPage(
            'Top Editors in Iraq | Ads Of Iraq',
            'Ranked list of Iraqi advertising editors by campaign credits, views, and platform recognition.',
            route('rankings.top-editors'),
        );

        return view('rankings.top-people', [
            'people' => $people,
            'heading' => 'Top Editors in Iraq',
            'intro' => 'Editors ranked by approved campaign credits and engagement across Ads Of Iraq.',
            'seo' => $seo,
        ]);
    }

    public function topCreativeDirectors(): View
    {
        $people = $this->personRankings->topCreativeDirectors(30);
        $seo = $this->seo->forRankingPage(
            'Top Creative Directors in Iraq | Ads Of Iraq',
            'Ranked list of Iraqi creative directors by campaigns, views, saves, and editorial recognition.',
            route('rankings.top-creative-directors'),
        );

        return view('rankings.top-people', [
            'people' => $people,
            'heading' => 'Top Creative Directors in Iraq',
            'intro' => 'Creative directors ranked by campaign output and engagement on Ads Of Iraq.',
            'seo' => $seo,
        ]);
    }

    public function topBrands(): View
    {
        $brands = $this->rankings->topBrands(30);
        $seo = $this->seo->forRankingPage(
            'Top Brands in Iraq | Ads Of Iraq',
            'Ranked list of Iraqi brands by advertising campaigns, views, saves, and editorial recognition.',
            route('rankings.top-brands'),
        );

        return view('rankings.top-brands', compact('brands', 'seo'));
    }

    public function topCommercials(): View
    {
        $campaigns = $this->rankings->topCommercials(36);
        $seo = $this->seo->forRankingPage(
            'Top Commercials in Iraq | Ads Of Iraq',
            'Ranked Iraqi TV and film commercials by views, saves, editor picks, and platform scores.',
            route('rankings.top-commercials'),
        );

        return view('rankings.top-commercials', compact('campaigns', 'seo'));
    }
}
