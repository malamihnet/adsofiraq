<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookmarkController extends Controller
{
    public function index(Request $request): View
    {
        $campaigns = $request->user()
            ->bookmarkedCampaigns()
            ->public()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latest('bookmarks.created_at')
            ->paginate(24);

        $campaigns->getCollection()->loadExists([
            'watchers as is_watched' => fn ($query) => $query->where('user_id', $request->user()->id),
        ])->each(fn ($campaign) => $campaign->setAttribute('is_bookmarked', true));

        return view('bookmarks.index', compact('campaigns'));
    }

    public function store(Request $request, Campaign $campaign): RedirectResponse
    {
        if ($campaign->status !== 'approved') {
            abort(404);
        }

        $bookmark = $request->user()->bookmarks()->firstOrCreate([
            'campaign_id' => $campaign->id,
        ]);

        if ($bookmark->wasRecentlyCreated) {
            $campaign->increment('bookmarks_count');
        }

        return back()->with('success', 'Campaign bookmarked.');
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $deleted = $request->user()->bookmarks()->where('campaign_id', $campaign->id)->delete();

        if ($deleted && $campaign->bookmarks_count > 0) {
            $campaign->decrement('bookmarks_count');
        }

        return back()->with('success', 'Bookmark removed.');
    }
}
