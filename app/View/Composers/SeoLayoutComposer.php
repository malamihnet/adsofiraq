<?php

namespace App\View\Composers;

use App\Services\SeoService;
use Illuminate\View\View;

class SeoLayoutComposer
{
    public function __construct(
        protected SeoService $seo,
    ) {}

    public function compose(View $view): void
    {
        $request = request();

        $view->with('seoNoindex', $this->seo->shouldNoindexListing($request));
        $view->with('seoCanonical', $this->seo->canonicalForRequest($request));
    }
}
