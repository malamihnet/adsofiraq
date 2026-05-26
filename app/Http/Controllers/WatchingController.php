<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WatchingController extends Controller
{
    public function index(Request $request): View
    {
        $campaigns = $request->user()
            ->watchingCampaigns()
            ->public()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latest('campaign_watchers.created_at')
            ->paginate(24);

        $campaigns->getCollection()->loadExists([
            'bookmarks as is_bookmarked' => fn ($query) => $query->where('user_id', $request->user()->id),
        ])->each(fn ($campaign) => $campaign->setAttribute('is_watched', true));

        return view('following.index', compact('campaigns'));
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== 'approved') {
            abort(404);
        }

        $watch = $request->user()->campaignWatchers()->firstOrCreate([
            'campaign_id' => $campaign->id,
        ]);

        if ($watch->wasRecentlyCreated) {
            $campaign->increment('watchers_count');
        }

        return back()->with('success', 'You are now watching this campaign.');
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $deleted = $request->user()->campaignWatchers()->where('campaign_id', $campaign->id)->delete();

        if ($deleted && $campaign->watchers_count > 0) {
            $campaign->decrement('watchers_count');
        }

        return back()->with('success', 'Campaign removed from watching.');
    }
}
