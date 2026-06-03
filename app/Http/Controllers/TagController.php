<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\CampaignArchiveOrderingService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function __construct(
        protected SeoService $seo,
        protected StructuredDataService $structuredData,
        protected CampaignArchiveOrderingService $archiveOrdering,
    ) {}

    public function show(Request $request, Tag $tag): View
    {
        $query = $tag->campaigns()->public()
            ->with(['brands', 'agencies', 'productionHouses', 'mediumTypes']);

        $perPage = max(12, min(48, (int) $request->query('per_page', 24)));

        $campaigns = $this->archiveOrdering->paginate(
            $query,
            perPage: $perPage,
            usePlacementOrdering: true,
            eagerLoads: ['brands', 'agencies', 'productionHouses', 'mediumTypes'],
        );

        $canonicalUrl = route('tags.show', $tag);
        $seo = $this->seo->forTag($tag, $canonicalUrl);
        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Campaigns', 'url' => route('campaigns.index')],
                ['name' => $tag->name, 'url' => $canonicalUrl],
            ]),
        ];

        return view('tags.show', compact('tag', 'campaigns', 'canonicalUrl', 'seo', 'schema'));
    }
}
