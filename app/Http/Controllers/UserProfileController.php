<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserProfileController extends Controller
{
    public function show(User $user): View
    {
        $campaigns = $user->approvedCampaigns()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latestOnPlatform()
            ->paginate(24);

        $isFollowing = auth()->check() && auth()->user()->isFollowing($user);

        return view('users.show', compact('user', 'campaigns', 'isFollowing'));
    }
}
