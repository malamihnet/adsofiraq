<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(
        protected StructuredDataService $structuredData,
        protected SeoService $seo,
    ) {}

    public function index(): View
    {
        $brands = Brand::withCount(['campaigns' => fn ($q) => $q->approved()->where('is_draft', false)])
            ->orderByDesc('ranking_score')
            ->paginate(48);

        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Brands', 'url' => route('brands.index')],
            ]),
        ];

        return view('brands.index', compact('brands', 'schema'));
    }

    public function show(Brand $brand): View
    {
        $stats = $brand->aggregateStats();

        $campaigns = $brand->approvedCampaignsQuery()
            ->with(['brands', 'agencies', 'mediumTypes'])
            ->latestOnPlatform()
            ->paginate(24);

        $canonicalUrl = route('brand.show', $brand);
        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Brands', 'url' => route('brands.index')],
                ['name' => $brand->name, 'url' => $canonicalUrl],
            ]),
            $this->structuredData->organizationBrand($brand, $canonicalUrl),
        ];

        $seo = $this->seo->forBrand($brand, $canonicalUrl);

        return view('brands.show', compact(
            'brand',
            'campaigns',
            'stats',
            'canonicalUrl',
            'schema',
            'seo',
        ));
    }
}
