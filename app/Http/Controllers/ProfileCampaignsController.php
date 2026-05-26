<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileCampaignsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $campaigns = Campaign::query()
            ->where('user_id', $user->id)
            ->with(['brands', 'agencies', 'pendingRevision'])
            ->latest('updated_at')
            ->get();

        $tab = (string) $request->query('tab', 'all');

        $filtered = $campaigns->filter(function (Campaign $campaign) use ($tab) {
            return match ($tab) {
                'approved' => $campaign->status === 'approved',
                'pending' => $campaign->status === 'pending',
                'rejected' => $campaign->status === 'rejected',
                'updates-pending' => $campaign->status === 'approved' && $campaign->pendingRevision !== null,
                default => true,
            };
        })->values();

        return view('profile.campaigns.index', [
            'tab' => $tab,
            'campaigns' => $filtered,
            'counts' => [
                'all' => $campaigns->count(),
                'approved' => $campaigns->where('status', 'approved')->count(),
                'pending' => $campaigns->where('status', 'pending')->count(),
                'rejected' => $campaigns->where('status', 'rejected')->count(),
                'updates-pending' => $campaigns->filter(fn (Campaign $c) => $c->status === 'approved' && $c->pendingRevision !== null)->count(),
            ],
        ]);
    }
}

