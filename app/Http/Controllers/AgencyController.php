<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function __construct(
        protected StructuredDataService $structuredData,
        protected SeoService $seo,
    ) {}

    public function index(): View
    {
        $agencies = Agency::withCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->orderByDesc('ranking_score')
            ->paginate(48);

        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Agencies', 'url' => route('agencies.index')],
            ]),
        ];

        return view('agencies.index', compact('agencies', 'schema'));
    }

    public function show(Agency $agency): View
    {
        $agency->load(['roles']);

        $stats = $agency->aggregateStats();

        $campaigns = $agency->approvedCampaignsQuery()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latestOnPlatform()
            ->paginate(24);

        $canonicalUrl = route('agency.show', $agency);
        $parentLabel = $agency->isProductionHouse() && ! $agency->isAgency()
            ? 'Production Houses'
            : 'Agencies';
        $parentUrl = $agency->isProductionHouse() && ! $agency->isAgency()
            ? route('rankings.top-production-houses')
            : route('agencies.index');

        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => $parentLabel, 'url' => $parentUrl],
                ['name' => $agency->name, 'url' => $canonicalUrl],
            ]),
            $this->structuredData->organizationAgency($agency, $canonicalUrl),
        ];

        $seo = $this->seo->forAgency($agency, $canonicalUrl);

        return view('agencies.show', compact(
            'agency',
            'campaigns',
            'stats',
            'canonicalUrl',
            'parentLabel',
            'parentUrl',
            'schema',
            'seo',
        ));
    }
}
