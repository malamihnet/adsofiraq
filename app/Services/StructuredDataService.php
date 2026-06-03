<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Person;
use Illuminate\Support\Collection;

class StructuredDataService
{
    public function __construct(
        protected SeoService $seo,
    ) {}

    public function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('seo.site_name', 'Ads Of Iraq'),
            'url' => url('/'),
            'description' => $this->seo->withArabicContext(
                'Iraqi advertising archive for campaigns, agencies, production houses, brands, and creative professionals.',
                'global',
            ),
            'inLanguage' => ['en', 'ar'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('campaigns.index', ['search' => '{search_term_string}']),
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public function siteOrganization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('seo.site_name', 'Ads Of Iraq'),
            'url' => url('/'),
            'description' => $this->seo->withArabicContext(
                'Independent archive documenting Iraqi advertising, film, design, and creative culture.',
                'global',
            ),
            'logo' => url(config('seo.default_og_image', '/favicon-96x96.png')),
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     */
    public function breadcrumb(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
        ];
    }

    public function organizationAgency(Agency $agency, string $url): array
    {
        $isProductionHouse = $agency->isProductionHouse();
        $isAgency = $agency->isAgency();

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => match (true) {
                $isAgency && $isProductionHouse => $agency->name.' Creative Agency & Production House',
                $isProductionHouse => $agency->name.' Production House',
                $isAgency => $agency->name.' Agency',
                default => $agency->name,
            },
            'url' => $url,
            'description' => $this->seo->withArabicContext(
                $agency->meta_description ?: sprintf(
                    'Explore campaigns, creative work, and profile information for %s on %s.',
                    $agency->name,
                    config('seo.site_name', 'Ads Of Iraq'),
                ),
                $agency->isProductionHouse() && ! $agency->isAgency() ? 'production_houses' : 'agencies',
            ),
        ];

        if ($isProductionHouse) {
            $data['additionalType'] = 'https://schema.org/ProductionCompany';
        }

        if ($agency->hasLogo()) {
            $data['logo'] = $agency->logo_url;
        }

        if ($agency->website_url) {
            $data['sameAs'] = array_values(array_filter([
                $agency->website_url,
                $agency->instagram_url,
                $agency->linkedin_url,
                $agency->twitter_url,
            ]));
        }

        return $data;
    }

    public function organizationBrand(Brand $brand, string $url): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $brand->name,
            'url' => $url,
            'description' => $this->seo->withArabicContext(
                $brand->meta_description ?: sprintf(
                    'Discover advertising campaigns and creative projects from %s.',
                    $brand->name,
                ),
                'brands',
            ),
        ];

        if ($brand->hasLogo()) {
            $data['logo'] = $brand->logo_url;
        }

        return $data;
    }

    public function person(Person $person, string $url): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $person->name,
            'url' => $url,
            'jobTitle' => $person->position,
            'description' => $this->seo->withArabicContext(
                $person->meta_description ?: sprintf(
                    'Explore creative work, campaigns, and professional profile of %s.',
                    $person->name,
                ),
                'people',
            ),
            'image' => $person->photo_url,
            'sameAs' => array_values(array_filter([
                $person->website_url,
                $person->official_profile_url,
                $person->instagram_url,
                $person->linkedin_url,
                $person->twitter_url,
            ])),
        ]);
    }

    public function creativeWork(Campaign $campaign, string $url): array
    {
        $images = $campaign->galleryStills()
            ->map(fn ($asset) => $asset->url)
            ->filter()
            ->values()
            ->all();

        if ($campaign->thumbnail_url) {
            array_unshift($images, $campaign->thumbnail_url);
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $campaign->title,
            'url' => $url,
            'description' => $this->seo->forCampaign($campaign)['description'],
            'datePublished' => $campaign->published_at?->toDateString(),
            'image' => $images ?: ($campaign->thumbnail_url ? [$campaign->thumbnail_url] : []),
            'keywords' => $campaign->industries->pluck('name')
                ->merge($campaign->mediumTypes->pluck('name'))
                ->unique()
                ->values()
                ->all(),
        ];

        if ($campaign->relationLoaded('agencies') && $campaign->agencies->isNotEmpty()) {
            $data['creator'] = [
                '@type' => 'Organization',
                'name' => $campaign->agencies->first()->name,
            ];
        } elseif ($campaign->relationLoaded('productionHouses') && $campaign->productionHouses->isNotEmpty()) {
            $data['creator'] = [
                '@type' => 'Organization',
                'name' => $campaign->productionHouses->first()->name,
                'additionalType' => 'https://schema.org/ProductionCompany',
            ];
        }

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function campaignMedia(Campaign $campaign): array
    {
        $graphs = [];

        foreach ($campaign->videos as $video) {
            if ($video->type === 'direct' && $video->url) {
                $graphs[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'VideoObject',
                    'name' => $campaign->title,
                    'contentUrl' => $video->url,
                    'thumbnailUrl' => $campaign->thumbnail_url,
                ];
            } elseif ($video->embed_url) {
                $graphs[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'VideoObject',
                    'name' => $campaign->title,
                    'embedUrl' => $video->embed_url,
                    'thumbnailUrl' => $campaign->thumbnail_url,
                ];
            } elseif ($video->file_url) {
                $graphs[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'VideoObject',
                    'name' => $campaign->title,
                    'contentUrl' => $video->file_url,
                    'thumbnailUrl' => $campaign->thumbnail_url,
                ];
            }
        }

        foreach ($campaign->galleryStills() as $still) {
            if ($still->url) {
                $graphs[] = [
                    '@context' => 'https://schema.org',
                    '@type' => 'ImageObject',
                    'contentUrl' => $still->url,
                    'name' => $campaign->title,
                ];
            }
        }

        return $graphs;
    }

    /**
     * @param  list<array<string, mixed>>  $graphs
     */
    public function toScriptTag(array $graphs): string
    {
        if ($graphs === []) {
            return '';
        }

        $payload = count($graphs) === 1
            ? $graphs[0]
            : ['@context' => 'https://schema.org', '@graph' => $graphs];

        return '<script type="application/ld+json">'
            .json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            .'</script>';
    }
}
