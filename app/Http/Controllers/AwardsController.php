<?php

namespace App\Http\Controllers;

use App\Models\Award;
use Illuminate\View\View;

class AwardsController extends Controller
{
    public function index(): View
    {
        $awards = Award::published()
            ->orderByDesc('year')
            ->get();

        return view('awards.index', compact('awards'));
    }

    public function show(Award $award): View
    {
        if (! $award->is_published) {
            abort(404);
        }

        $award->load(['categories.winners.campaign' => fn ($q) => $q->public()]);

        return view('awards.show', compact('award'));
    }
}
