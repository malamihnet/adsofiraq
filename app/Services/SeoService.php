<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Industry;
use App\Models\MediumType;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SeoService
{
    public function siteName(): string
    {
        return (string) config('seo.site_name', 'Ads Of Iraq');
    }

    /**
     * @return array{title: string, description: string, og_type: string, og_image: ?string, canonical: ?string, robots: ?string}
     */
    public function forHomepage(): array
    {
        $title = $this->siteName().' | '.config('seo.site_tagline', 'Iraqi Advertising Archive');
        $description = $this->withArabicContext(
            'Explore Iraqi advertising campaigns, agencies, production houses, creative professionals, and brands. Ads Of Iraq is the curated archive for creative work from Iraq and the region.',
            'global',
        );

        return $this->pack($title, $description, 'website', url(config('seo.default_og_image', '/favicon-96x96.png')));
    }

    /**
     * @return array{title: string, description: string, og_type: string, og_image: ?string, canonical: ?string, robots: ?string}
     */
    public function forCampaign(Campaign $campaign, ?string $canonical = null): array
    {
        $title = $campaign->title.' | '.$this->siteName();
        $agencyName = $campaign->relationLoaded('agencies')
            ? ($campaign->agencies->first()?->name ?? 'Iraqi creatives')
            : 'Iraqi creatives';

        $description = $this->withArabicContext(
            sprintf(
                'Watch %s by %s. Explore credits, production details, and creative work on %s.',
                $campaign->title,
                $agencyName,
                $this->siteName(),
            ),
            'campaigns',
        );

        if ($campaign->description) {
            $stripped = Str::limit(strip_tags($campaign->description), 120);
            if (strlen($stripped) > 40) {
                $description = $this->withArabicContext($stripped, 'campaigns');
            }
        }

        return $this->pack(
            $title,
            $description,
            'article',
            $campaign->thumbnail_url,
            $canonical ?? route('campaigns.show', $campaign),
        );
    }

    /**
     * @return array{title: string, description: string, og_type: string, og_image: ?string, canonical: ?string, robots: ?string}
     */
    public function forAgency(Agency $agency, ?string $canonical = null): array
    {
        $canonical ??= route('agency.show', $agency);

        if ($agency->isProductionHouse() && ! $agency->isAgency()) {
            $title = $agency->name.' | Production House | '.$this->siteName();
            $context = 'production_houses';
        } else {
            $title = $agency->name.' | Agency Profile | '.$this->siteName();
            $context = 'agencies';
        }

        $description = $agency->meta_description ?: sprintf(
            'Explore campaigns, creative work, and profile information for %s on %s.',
            $agency->name,
            $this->siteName(),
        );

        $ogImage = $agency->hasLogo() ? $agency->logo_url : null;

        return $this->pack($title, $this->withArabicContext($description, $context), 'profile', $ogImage, $canonical);
    }

    /**
     * @return array{title: string, description: string, og_type: string, og_image: ?string, canonical: ?string, robots: ?string}
     */
    public function forBrand(Brand $brand, ?string $canonical = null): array
    {
        $title = $brand->name.' | Brand Profile | '.$this->siteName();
        $description = $brand->meta_description ?: sprintf(
            'Discover advertising campaigns and creative projects from %s on %s.',
            $brand->name,
            $this->siteName(),
        );

        return $this->pack(
            $title,
            $this->withArabicContext($description, 'brands'),
            'profile',
            $brand->hasLogo() ? $brand->logo_url : null,
            $canonical ?? route('brand.show', $brand),
        );
    }

    /**
     * @return array{title: string, description: string, og_type: string, og_image: ?string, canonical: ?string, robots: ?string}
     */
    public function forPerson(Person $person, ?string $canonical = null): array
    {
        $role = $person->position ?: 'Creative Professional';
        $title = $person->name.' | '.$role.' | '.$this->siteName();
        $description = $person->meta_description ?: sprintf(
            'Explore creative work, campaigns, and professional profile of %s on %s.',
            $person->name,
            $this->siteName(),
        );

        return $this->pack(
            $title,
            $this->withArabicContext($description, 'people'),
            'profile',
            $person->photo_url ?? null,
            $canonical ?? route('person.show', $person),
        );
    }

    /**
     * @return array{title: string, description: string, og_type: string, og_image: ?string, canonical: ?string, robots: ?string}
     */
    public function forCategory(string $name, string $listingUrl): array
    {
        $title = $name.' Campaigns | '.$this->siteName();
        $description = $this->withArabicContext(
            sprintf('Browse %s campaigns from Iraq\'s advertising archive on %s.', $name, $this->siteName()),
            'categories',
        );

        return $this->pack($title, $description, 'website', null, $listingUrl);
    }

    /**
     * @return array{title: string, description: string, og_type: string, og_image: ?string, canonical: ?string, robots: ?string}
     */
    public function forPage(string $title, string $description, ?string $canonical = null): array
    {
        return $this->pack($title, $this->withArabicContext($description, 'global'), 'website', null, $canonical);
    }

    public function withArabicContext(string $description, string $contextKey): string
    {
        $arabic = config('seo.arabic_keywords.'.$contextKey)
            ?? config('seo.arabic_keywords.global', '');

        if ($arabic === '') {
            return Str::limit($description, 320);
        }

        return Str::limit(trim($description.' '.$arabic), 320);
    }

    public function shouldNoindexListing(Request $request): bool
    {
        if (! $request->routeIs(
            'campaigns.index',
            'agencies.index',
            'brands.index',
            'people.index',
            'rankings.*',
            'made-by-iraq.index',
            'featured.index',
            'awards.index',
        )) {
            return false;
        }

        if ((int) $request->query('page', 1) > 1) {
            return true;
        }

        foreach (config('seo.listing_noindex_params', []) as $param) {
            if ($param === 'page') {
                continue;
            }

            if ($request->filled($param)) {
                return true;
            }
        }

        return false;
    }

    public function canonicalForRequest(Request $request): ?string
    {
        if ($this->shouldNoindexListing($request)) {
            $route = $request->route();

            if ($route && $route->getName()) {
                return route($route->getName(), [], true);
            }
        }

        return null;
    }

    public function defaultOgImageUrl(): string
    {
        return url(config('seo.default_og_image', '/favicon-96x96.png'));
    }

    /**
     * @return array{title: string, description: string, og_type: string, og_image: ?string, canonical: ?string, robots: ?string}
     */
    protected function pack(
        string $title,
        string $description,
        string $ogType = 'website',
        ?string $ogImage = null,
        ?string $canonical = null,
        ?string $robots = null,
    ): array {
        return [
            'title' => $title,
            'description' => Str::limit($description, 320),
            'og_type' => $ogType,
            'og_image' => $ogImage,
            'canonical' => $canonical,
            'robots' => $robots,
        ];
    }
}
