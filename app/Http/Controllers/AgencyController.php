<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Industry;
use App\Services\StructuredDataService;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function __construct(
        protected StructuredDataService $structuredData,
    ) {}

    public function index(): View
    {
        $agencies = Agency::withCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->orderByDesc('ranking_score')
            ->paginate(48);

        return view('agencies.index', compact('agencies'));
    }

    public function show(Agency $agency): View
    {
        $agency->loadCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)]);

        $stats = $agency->aggregateStats();

        $campaigns = $agency->approvedCampaignsQuery()
            ->with(['brands', 'agencies', 'mediumTypes', 'industries'])
            ->latestOnPlatform()
            ->paginate(24);

        $featuredCampaigns = $agency->approvedCampaignsQuery()
            ->where(function ($q) {
                $q->where('is_featured', true)->orWhereNotNull('editorial_label');
            })
            ->with(['brands', 'agencies'])
            ->latestOnPlatform()
            ->limit(6)
            ->get();

        $industries = Industry::query()
            ->whereHas('campaigns', fn ($q) => $q->whereHas('agencies', fn ($a) => $a->where('agencies.id', $agency->id)))
            ->withCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->orderByDesc('campaigns_count')
            ->limit(12)
            ->get();

        $collaboratingBrands = Brand::query()
            ->whereHas('campaigns', fn ($q) => $q->whereHas('agencies', fn ($a) => $a->where('agencies.id', $agency->id)))
            ->withCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->orderByDesc('campaigns_count')
            ->limit(12)
            ->get();

        $relatedAgencies = Agency::query()
            ->where('id', '!=', $agency->id)
            ->whereHas('campaigns', fn ($q) => $q->approved()->where('is_draft', false))
            ->orderByDesc('ranking_score')
            ->limit(8)
            ->get();

        $canonicalUrl = route('agency.show', $agency);
        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Agencies', 'url' => route('agencies.index')],
                ['name' => $agency->name, 'url' => $canonicalUrl],
            ]),
            $this->structuredData->organizationAgency($agency, $canonicalUrl),
        ];

        return view('agencies.show', compact(
            'agency',
            'campaigns',
            'stats',
            'featuredCampaigns',
            'industries',
            'collaboratingBrands',
            'relatedAgencies',
            'canonicalUrl',
            'schema',
        ));
    }
}
