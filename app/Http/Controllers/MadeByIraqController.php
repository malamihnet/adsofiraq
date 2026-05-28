<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Person;
use Illuminate\View\View;

class MadeByIraqController extends Controller
{
    public function index(): View
    {
        $featuredCampaigns = Campaign::public()
            ->madeByIraq()
            ->where(function ($q) {
                $q->where('is_featured', true)->orWhereNotNull('editorial_label');
            })
            ->with(['brands', 'agencies'])
            ->orderByDesc('ranking_score')
            ->limit(8)
            ->get();

        $campaigns = Campaign::public()
            ->madeByIraq()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latestOnPlatform()
            ->paginate(24);

        $featuredCreatives = Person::public()
            ->where('is_verified', true)
            ->orderByDesc('ranking_score')
            ->limit(12)
            ->get();

        return view('made-by-iraq.index', compact('featuredCampaigns', 'campaigns', 'featuredCreatives'));
    }
}
