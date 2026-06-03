<?php

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        protected SitemapService $sitemaps,
    ) {}

    public function index(): Response
    {
        return $this->xml('sitemap.index', [
            'sitemaps' => $this->sitemaps->indexEntries(),
        ]);
    }

    public function campaigns(): Response
    {
        return $this->urlset($this->sitemaps->campaigns());
    }

    public function agencies(): Response
    {
        return $this->urlset($this->sitemaps->agencies());
    }

    public function productionHouses(): Response
    {
        return $this->urlset($this->sitemaps->productionHouses());
    }

    public function brands(): Response
    {
        return $this->urlset($this->sitemaps->brands());
    }

    public function people(): Response
    {
        return $this->urlset($this->sitemaps->people());
    }

    public function categories(): Response
    {
        return $this->urlset($this->sitemaps->categories());
    }

    public function pages(): Response
    {
        return $this->urlset($this->sitemaps->staticPages());
    }

    public function tags(): Response
    {
        return $this->urlset($this->sitemaps->tags());
    }

    public function rankings(): Response
    {
        return $this->urlset($this->sitemaps->rankings());
    }

    public function landingPages(): Response
    {
        return $this->urlset($this->sitemaps->landingPages());
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, string>>|list<array<string, string>>  $urls
     */
    protected function urlset($urls): Response
    {
        return $this->xml('sitemap.urlset', ['urls' => $urls]);
    }

    protected function xml(string $view, array $data): Response
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'application/xml');
    }
}
