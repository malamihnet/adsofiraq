<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function index(): View
    {
        $agencies = Agency::withCount(['campaigns' => fn ($q) => $q->approved()])
            ->orderByDesc('campaigns_count')
            ->paginate(48);

        return view('agencies.index', compact('agencies'));
    }

    public function show(Agency $agency): View
    {
        $campaigns = $agency->campaigns()
            ->public()
            ->with(['agencies', 'brands', 'mediumTypes'])
            ->latestOnPlatform()
            ->paginate(24);

        return view('agencies.show', compact('agency', 'campaigns'));
    }
}
