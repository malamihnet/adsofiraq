<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SeoReportController extends Controller
{
    public function index(): View
    {
        return view('admin.seo-report.index', [
            'sitemapUrls' => collect(config('seo.sitemaps', []))->map(fn ($file) => url('/'.$file)),
            'faviconUrls' => collect(config('seo.favicon_urls', []))->map(fn ($path) => url($path)),
            'arabicContexts' => config('seo.arabic_keywords', []),
            'noindexRoutes' => [
                'All /admin/* routes',
                'Login, register, password reset',
                'Profile, bookmarks, following',
                'Campaign create/edit and pending-review (auth)',
                'Filtered or paginated listing URLs (page > 1, sort, search, filters)',
            ],
            'structuredData' => [
                'Homepage: WebSite + Organization (@graph, linked publisher)',
                'Campaign: CreativeWork + VideoObject + BreadcrumbList',
                'Agency / Production House: Organization + BreadcrumbList',
                'Brand: Organization + BreadcrumbList',
                'Person: Person + BreadcrumbList',
                'Tag pages: BreadcrumbList + campaign listings',
                'SEO landing pages: BreadcrumbList + live data blocks',
                'Hub pages: BreadcrumbList on campaigns, agencies, brands, people indexes',
            ],
            'sitemapChildren' => [
                '/sitemap-campaigns.xml',
                '/sitemap-agencies.xml',
                '/sitemap-production-houses.xml',
                '/sitemap-brands.xml',
                '/sitemap-people.xml',
                '/sitemap-categories.xml',
                '/sitemap-tags.xml',
                '/sitemap-rankings.xml',
                '/sitemap-landing-pages.xml',
                '/sitemap-pages.xml',
            ],
            'authorityFeatures' => [
                'Campaign people credits (campaign_person pivot + form UI)',
                'Auto tags from taxonomies and credits (/tag/{slug})',
                'Person rankings: directors, editors, creative directors',
                'Brand and commercial rankings',
                'SEO landing pages: /iraqi-advertising, /iraq-agencies, etc.',
                'Image SEO on upload: alt, title, dimensions, WebP variants',
                'Open Graph: campaign thumbnail, agency logo, person photo',
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
            'googleSearchConsoleChecklist' => [
                'Test favicon: open /favicon.ico and /favicon-32x32.png (expect HTTP 200)',
                'Test sitemap: submit https://adsofiraq.com/sitemap.xml in Search Console',
                'Validate structured data: Rich Results Test on homepage, one campaign, one agency',
                'Request indexing for homepage and top hub pages after deploy',
                'Check Enhancements > Breadcrumbs in Search Console after crawl',
                'Check Enhancements > Organization logo after crawl',
                'Review Coverage report for excluded noindex filter URLs',
            ],
        ]);
    }
}
