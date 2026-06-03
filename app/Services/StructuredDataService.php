<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Person;

class StructuredDataService
{
    public function __construct(
        protected SeoService $seo,
    ) {}

    public function homePageGraph(): array
    {
        $organizationId = $this->organizationId();
        $websiteId = $this->websiteId();

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $organizationId,
                    'name' => config('seo.site_name', 'Ads Of Iraq'),
                    'url' => $this->siteUrl(),
                    'description' => $this->seo->withArabicContext(
                        'Independent archive documenting Iraqi advertising, film, design, and creative culture.',
                        'global',
                    ),
                    'logo' => $this->organizationLogo(),
                    'sameAs' => $this->organizationSameAs(),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $websiteId,
                    'name' => config('seo.site_name', 'Ads Of Iraq'),
                    'url' => $this->siteUrl(),
                    'description' => $this->seo->withArabicContext(
                        'Iraqi advertising archive for campaigns, agencies, production houses, brands, and creative professionals.',
                        'global',
                    ),
                    'inLanguage' => ['en', 'ar'],
                    'publisher' => ['@id' => $organizationId],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => route('campaigns.index', ['search' => '{search_term_string}']),
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    }

    /** @deprecated Use homePageGraph() */
    public function website(): array
    {
        return $this->homePageGraph();
    }

    /** @deprecated Use homePageGraph() */
    public function siteOrganization(): array
    {
        return $this->homePageGraph();
    }

    /**
     * @param  list<array{name: string, url: ?string}>  $items
     */
    public function breadcrumb(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(function (array $item, int $i) {
                $element = [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $item['name'],
                ];

                if (! empty($item['url'])) {
                    $element['item'] = $item['url'];
                }

                return $element;
            })->all(),
        ];
    }

    public function organizationAgency(Agency $agency, string $url): array
    {
        $isProductionHouse = $agency->isProductionHouse();
        $isAgency = $agency->isAgency();

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $agency->name,
            'url' => $url,
            'description' => $this->seo->withArabicContext(
                $agency->meta_description ?: sprintf(
                    'Explore campaigns, creative work, and profile information for %s on %s.',
                    $agency->name,
                    config('seo.site_name', 'Ads Of Iraq'),
                ),
                $isProductionHouse && ! $isAgency ? 'production_houses' : 'agencies',
            ),
        ];

        if ($isProductionHouse) {
            $data['additionalType'] = 'https://schema.org/ProductionCompany';
        }

        if ($agency->hasLogo()) {
            $data['logo'] = $agency->logo_url;
            $data['image'] = $agency->logo_url;
        }

        $sameAs = array_values(array_filter([
            $agency->website_url,
            $agency->instagram_url,
            $agency->linkedin_url,
            $agency->twitter_url,
            $agency->facebook_url,
        ]));

        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
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
            $data['image'] = $brand->logo_url;
        }

        return $data;
    }

    public function person(Person $person, string $url, ?int $creditedCampaignsCount = null): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $person->name,
            'url' => $url,
            'jobTitle' => $person->display_position ?? $person->position,
            'description' => $this->seo->withArabicContext(
                $person->meta_description ?: sprintf(
                    'Explore campaigns and creative work by %s on Ads Of Iraq.',
                    $person->name,
                ),
                'people',
            ),
            'image' => $person->photo_url,
            'knowsAbout' => $creditedCampaignsCount !== null && $creditedCampaignsCount > 0
                ? ['Iraqi advertising', 'TV commercials', 'creative production']
                : null,
            'agentInteractionStatistic' => $creditedCampaignsCount !== null && $creditedCampaignsCount > 0 ? [
                '@type' => 'InteractionCounter',
                'interactionType' => 'https://schema.org/CreditAction',
                'userInteractionCount' => $creditedCampaignsCount,
            ] : null,
            'worksFor' => [
                '@type' => 'Organization',
                '@id' => $this->organizationId(),
                'name' => config('seo.site_name', 'Ads Of Iraq'),
            ],
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
        $thumbnail = $campaign->thumbnail_url;
        $images = $campaign->galleryStills()
            ->map(fn ($asset) => $asset->url)
            ->filter()
            ->values()
            ->all();

        if ($thumbnail) {
            array_unshift($images, $thumbnail);
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $campaign->title,
            'headline' => $campaign->title,
            'url' => $url,
            'mainEntityOfPage' => $url,
            'description' => $this->seo->forCampaign($campaign)['description'],
            'datePublished' => $campaign->published_at?->toIso8601String(),
            'dateModified' => $campaign->updated_at?->toIso8601String(),
            'thumbnailUrl' => $thumbnail,
            'image' => $images ?: ($thumbnail ? [$thumbnail] : []),
            'publisher' => [
                '@type' => 'Organization',
                '@id' => $this->organizationId(),
                'name' => config('seo.site_name', 'Ads Of Iraq'),
                'logo' => $this->organizationLogo(),
            ],
            'inLanguage' => 'en',
            'keywords' => $campaign->industries->pluck('name')
                ->merge($campaign->mediumTypes->pluck('name'))
                ->unique()
                ->values()
                ->all(),
        ];

        $creator = $this->campaignCreator($campaign);

        if ($creator !== null) {
            $data['creator'] = $creator;
        }

        return array_filter($data, fn ($value) => $value !== null && $value !== [] && $value !== '');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function campaignMedia(Campaign $campaign): array
    {
        $graphs = [];
        $description = $this->seo->forCampaign($campaign)['description'];
        $publisher = [
            '@type' => 'Organization',
            '@id' => $this->organizationId(),
            'name' => config('seo.site_name', 'Ads Of Iraq'),
        ];

        foreach ($campaign->videos as $video) {
            $base = [
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject',
                'name' => $campaign->title,
                'description' => $description,
                'thumbnailUrl' => $campaign->thumbnail_url,
                'uploadDate' => $campaign->published_at?->toIso8601String(),
                'publisher' => $publisher,
            ];

            if ($video->type === 'direct' && $video->url) {
                $graphs[] = array_merge($base, ['contentUrl' => $video->url]);
            } elseif ($video->embed_url) {
                $graphs[] = array_merge($base, ['embedUrl' => $video->embed_url]);
            } elseif ($video->file_url) {
                $graphs[] = array_merge($base, ['contentUrl' => $video->file_url]);
            }
        }

        if ($graphs === [] && $campaign->thumbnail_url) {
            $graphs[] = [
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject',
                'name' => $campaign->title,
                'description' => $description,
                'thumbnailUrl' => $campaign->thumbnail_url,
                'uploadDate' => $campaign->published_at?->toIso8601String(),
                'publisher' => $publisher,
            ];
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

        $normalized = [];

        foreach ($graphs as $graph) {
            if (isset($graph['@graph']) && is_array($graph['@graph'])) {
                $normalized = array_merge($normalized, $graph['@graph']);

                continue;
            }

            $normalized[] = $graph;
        }

        $payload = count($normalized) === 1
            ? $normalized[0]
            : ['@context' => 'https://schema.org', '@graph' => $this->ensureContextOnGraphNodes($normalized)];

        if (! isset($payload['@context']) && ! isset($payload['@graph'])) {
            $payload['@context'] = 'https://schema.org';
        }

        return '<script type="application/ld+json">'
            .json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            .'</script>';
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array<string, mixed>>
     */
    protected function ensureContextOnGraphNodes(array $nodes): array
    {
        return array_map(function (array $node) {
            if (! isset($node['@context'])) {
                unset($node['@context']);
            }

            return $node;
        }, $nodes);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function campaignCreator(Campaign $campaign): ?array
    {
        if ($campaign->relationLoaded('agencies') && $campaign->agencies->isNotEmpty()) {
            $agency = $campaign->agencies->first();

            return [
                '@type' => 'Organization',
                'name' => $agency->name,
                'url' => route('agency.show', $agency),
            ];
        }

        if ($campaign->relationLoaded('productionHouses') && $campaign->productionHouses->isNotEmpty()) {
            $house = $campaign->productionHouses->first();

            return [
                '@type' => 'Organization',
                'name' => $house->name,
                'url' => route('agency.show', $house),
                'additionalType' => 'https://schema.org/ProductionCompany',
            ];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function organizationLogo(): array
    {
        return [
            '@type' => 'ImageObject',
            'url' => $this->absoluteAsset(config('seo.organization.logo_path', '/web-app-manifest-512x512.png')),
            'width' => (int) config('seo.organization.logo_width', 512),
            'height' => (int) config('seo.organization.logo_height', 512),
        ];
    }

    /**
     * @return list<string>
     */
    protected function organizationSameAs(): array
    {
        return array_values(array_filter(
            config('seo.organization.same_as', []),
            static fn ($url) => is_string($url) && $url !== '',
        ));
    }

    protected function siteUrl(): string
    {
        return rtrim((string) config('seo.site_url', config('app.url')), '/');
    }

    protected function organizationId(): string
    {
        return $this->siteUrl().'/#organization';
    }

    protected function websiteId(): string
    {
        return $this->siteUrl().'/#website';
    }

    protected function absoluteAsset(string $path): string
    {
        return $this->siteUrl().'/'.ltrim($path, '/');
    }
}
