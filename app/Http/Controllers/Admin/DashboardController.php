<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'totalCampaigns' => Campaign::count(),
            'pendingCampaigns' => Campaign::pending()->count(),
            'approvedCampaigns' => Campaign::approved()->count(),
            'recentPending' => Campaign::pending()
                ->with(['user', 'brands', 'agencies'])
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }
}
