<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SeoService;
use App\Services\SitemapService;
use Illuminate\View\View;

class SeoReportController extends Controller
{
    public function __construct(
        protected SeoService $seo,
        protected SitemapService $sitemaps,
    ) {}

    public function index(): View
    {
        return view('admin.seo-report.index', [
            'sitemapUrls' => collect(config('seo.sitemaps', []))->map(fn ($file) => url('/'.$file)),
            'arabicContexts' => config('seo.arabic_keywords', []),
            'noindexRoutes' => [
                'All /admin/* routes',
                'Login, register, password reset',
                'Profile, bookmarks, following',
                'Campaign create/edit and pending-review (auth)',
                'Filtered or paginated listing URLs (page > 1, sort, search, filters)',
            ],
            'structuredData' => [
                'WebSite + Organization (homepage)',
                'CreativeWork + VideoObject (campaigns)',
                'Organization (agencies, brands, production houses)',
                'Person (people profiles)',
                'BreadcrumbList (entity show pages)',
            ],
            'titleFormulas' => [
                'Homepage' => 'Ads Of Iraq | Iraqi Advertising Archive',
                'Campaign' => '{Title} | Ads Of Iraq',
                'Agency' => '{Name} | Agency Profile | Ads Of Iraq',
                'Production House' => '{Name} | Production House | Ads Of Iraq',
                'Person' => '{Name} | {Role} | Ads Of Iraq',
                'Brand' => '{Name} | Brand Profile | Ads Of Iraq',
                'Category' => '{Category} Campaigns | Ads Of Iraq',
            ],
        ]);
    }
}
