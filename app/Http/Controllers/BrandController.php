<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Industry;
use App\Services\StructuredDataService;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(
        protected StructuredDataService $structuredData,
    ) {}

    public function index(): View
    {
        $brands = Brand::withCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->orderByDesc('ranking_score')
            ->paginate(48);

        return view('brands.index', compact('brands'));
    }

    public function show(Brand $brand): View
    {
        $brand->loadCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)]);

        $stats = $brand->aggregateStats();

        $campaigns = $brand->approvedCampaignsQuery()
            ->with(['brands', 'agencies', 'mediumTypes', 'industries'])
            ->latestOnPlatform()
            ->paginate(24);

        $featuredCampaigns = $brand->approvedCampaignsQuery()
            ->where(function ($q) {
                $q->where('is_featured', true)->orWhereNotNull('editorial_label');
            })
            ->with(['brands', 'agencies'])
            ->latestOnPlatform()
            ->limit(6)
            ->get();

        $industries = Industry::query()
            ->whereHas('campaigns', fn ($q) => $q->whereHas('brands', fn ($b) => $b->where('brands.id', $brand->id)))
            ->withCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->orderByDesc('campaigns_count')
            ->limit(12)
            ->get();

        $collaboratingAgencies = Agency::query()
            ->whereHas('campaigns', fn ($q) => $q->whereHas('brands', fn ($b) => $b->where('brands.id', $brand->id)))
            ->withCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->orderByDesc('campaigns_count')
            ->limit(12)
            ->get();

        $canonicalUrl = route('brand.show', $brand);
        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Brands', 'url' => route('brands.index')],
                ['name' => $brand->name, 'url' => $canonicalUrl],
            ]),
            $this->structuredData->organizationBrand($brand, $canonicalUrl),
        ];

        return view('brands.show', compact(
            'brand',
            'campaigns',
            'stats',
            'featuredCampaigns',
            'industries',
            'collaboratingAgencies',
            'canonicalUrl',
            'schema',
        ));
    }
}
