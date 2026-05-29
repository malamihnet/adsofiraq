<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Person;
use Illuminate\Support\Collection;

class StructuredDataService
{
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
            'description' => $agency->seo_description,
        ];

        if ($isProductionHouse) {
            $data['additionalType'] = 'https://schema.org/ProductionCompany';
        }

        if ($agency->logo_url) {
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
            'description' => $brand->seo_description,
        ];

        if ($brand->logo_url) {
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
            'description' => $person->seo_description,
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
            'description' => $campaign->seo_description,
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
