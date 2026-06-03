<?php

namespace App\Http\Controllers;

use App\Services\SeoLandingDataService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\View\View;

class SeoLandingController extends Controller
{
    public function __construct(
        protected SeoLandingDataService $landingData,
        protected SeoService $seo,
        protected StructuredDataService $structuredData,
    ) {}

    public function iraqiAdvertising(): View
    {
        return $this->render('landing.iraqi-advertising', $this->landingData->iraqiAdvertising());
    }

    public function iraqAgencies(): View
    {
        return $this->render('landing.iraq-agencies', $this->landingData->iraqAgencies());
    }

    public function iraqProductionHouses(): View
    {
        return $this->render('landing.iraq-production-houses', $this->landingData->iraqProductionHouses());
    }

    public function iraqCommercials(): View
    {
        return $this->render('landing.iraq-commercials', $this->landingData->iraqCommercials());
    }

    public function iraqTvCommercials(): View
    {
        return $this->render('landing.iraq-tv-commercials', $this->landingData->iraqTvCommercials());
    }

    public function iraqCreativeIndustry(): View
    {
        return $this->render('landing.iraq-creative-industry', $this->landingData->iraqCreativeIndustry());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function render(string $routeName, array $data): View
    {
        $canonicalUrl = route($routeName);
        $seo = $this->seo->forLandingPage($data['title'], $data['meta_description'], $canonicalUrl);
        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => $data['title'], 'url' => $canonicalUrl],
            ]),
        ];

        return view('landing.show', array_merge($data, [
            'canonicalUrl' => $canonicalUrl,
            'seo' => $seo,
            'schema' => $schema,
        ]));
    }
}
