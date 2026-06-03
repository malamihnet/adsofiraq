<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Industry;
use App\Models\MediumType;
use App\Models\Person;
use Illuminate\Support\Collection;

class SitemapService
{
    /**
     * @return list<array{loc: string, lastmod?: string, priority?: string, changefreq?: string}>
     */
    public function indexEntries(): array
    {
        $sitemaps = config('seo.sitemaps', []);
        $entries = [];

        foreach ($sitemaps as $key => $filename) {
            $entries[] = [
                'loc' => url('/'.$filename),
                'lastmod' => now()->toAtomString(),
            ];
        }

        return $entries;
    }

    /**
     * @return Collection<int, array{loc: string, lastmod?: string, priority?: string, changefreq?: string}>
     */
    public function staticPages(): Collection
    {
        return collect(config('seo.static_pages', []))->map(function (array $page) {
            return [
                'loc' => route($page['route']),
                'priority' => $page['priority'] ?? '0.6',
                'changefreq' => $page['changefreq'] ?? 'monthly',
                'lastmod' => now()->toAtomString(),
            ];
        });
    }

    /**
     * @return Collection<int, array{loc: string, lastmod?: string, priority?: string}>
     */
    public function campaigns(): Collection
    {
        $urls = collect();

        Campaign::public()
            ->select(['slug', 'updated_at'])
            ->orderByDesc('updated_at')
            ->chunk(200, function ($chunk) use ($urls) {
                foreach ($chunk as $campaign) {
                    $urls->push([
                        'loc' => route('campaigns.show', $campaign->slug),
                        'lastmod' => $campaign->updated_at?->toAtomString(),
                        'priority' => '0.8',
                        'changefreq' => 'weekly',
                    ]);
                }
            });

        return $urls;
    }

    /**
     * @return Collection<int, array{loc: string, lastmod?: string, priority?: string}>
     */
    public function agencies(): Collection
    {
        return Agency::query()
            ->forTopAgencies()
            ->orderBy('name')
            ->get(['slug', 'updated_at'])
            ->map(fn (Agency $agency) => [
                'loc' => route('agency.show', $agency->slug),
                'lastmod' => $agency->updated_at?->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ]);
    }

    /**
     * @return Collection<int, array{loc: string, lastmod?: string, priority?: string}>
     */
    public function productionHouses(): Collection
    {
        return Agency::query()
            ->forTopProductionHouses()
            ->orderBy('name')
            ->get(['slug', 'updated_at'])
            ->map(fn (Agency $agency) => [
                'loc' => route('agency.show', $agency->slug),
                'lastmod' => $agency->updated_at?->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ]);
    }

    /**
     * @return Collection<int, array{loc: string, lastmod?: string, priority?: string}>
     */
    public function brands(): Collection
    {
        return Brand::query()
            ->orderBy('name')
            ->get(['slug', 'updated_at'])
            ->map(fn (Brand $brand) => [
                'loc' => route('brand.show', $brand->slug),
                'lastmod' => $brand->updated_at?->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ]);
    }

    /**
     * @return Collection<int, array{loc: string, lastmod?: string, priority?: string}>
     */
    public function people(): Collection
    {
        return Person::public()
            ->orderBy('name')
            ->get(['slug', 'updated_at'])
            ->map(fn (Person $person) => [
                'loc' => route('person.show', $person->slug),
                'lastmod' => $person->updated_at?->toAtomString(),
                'priority' => '0.6',
                'changefreq' => 'monthly',
            ]);
    }

    /**
     * @return Collection<int, array{loc: string, lastmod?: string, priority?: string}>
     */
    public function categories(): Collection
    {
        $urls = collect();

        MediumType::query()
            ->whereHas('campaigns', fn ($q) => $q->approved())
            ->orderBy('name')
            ->get(['slug', 'name', 'updated_at'])
            ->each(function (MediumType $medium) use ($urls) {
                $urls->push([
                    'loc' => route('campaigns.index', ['medium' => $medium->slug]),
                    'lastmod' => ($medium->updated_at ?? $medium->created_at)?->toAtomString(),
                    'priority' => '0.6',
                    'changefreq' => 'weekly',
                ]);
            });

        Industry::query()
            ->whereHas('campaigns', fn ($q) => $q->approved())
            ->orderBy('name')
            ->get(['slug', 'name', 'updated_at'])
            ->each(function (Industry $industry) use ($urls) {
                $urls->push([
                    'loc' => route('campaigns.index', ['industry' => $industry->slug]),
                    'lastmod' => ($industry->updated_at ?? $industry->created_at)?->toAtomString(),
                    'priority' => '0.6',
                    'changefreq' => 'weekly',
                ]);
            });

        return $urls;
    }
}
