<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Award;
use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Person;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = collect([
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => route('campaigns.index'), 'priority' => '0.9'],
            ['loc' => route('agencies.index'), 'priority' => '0.8'],
            ['loc' => route('brands.index'), 'priority' => '0.8'],
            ['loc' => route('people.index'), 'priority' => '0.8'],
            ['loc' => route('rankings.index'), 'priority' => '0.7'],
            ['loc' => route('made-by-iraq.index'), 'priority' => '0.8'],
            ['loc' => route('awards.index'), 'priority' => '0.7'],
        ]);

        Campaign::public()->select(['slug', 'updated_at'])->orderByDesc('updated_at')->chunk(200, function ($chunk) use ($urls) {
            foreach ($chunk as $campaign) {
                $urls->push([
                    'loc' => route('campaigns.show', $campaign->slug),
                    'lastmod' => $campaign->updated_at?->toAtomString(),
                    'priority' => '0.8',
                ]);
            }
        });

        foreach (Agency::orderBy('name')->get(['slug', 'updated_at']) as $agency) {
            $urls->push([
                'loc' => route('agency.show', $agency->slug),
                'lastmod' => $agency->updated_at?->toAtomString(),
                'priority' => '0.7',
            ]);
        }

        foreach (Brand::orderBy('name')->get(['slug', 'updated_at']) as $brand) {
            $urls->push([
                'loc' => route('brand.show', $brand->slug),
                'lastmod' => $brand->updated_at?->toAtomString(),
                'priority' => '0.7',
            ]);
        }

        Person::public()->orderBy('name')->get(['slug', 'updated_at'])->each(function (Person $person) use ($urls) {
            $urls->push([
                'loc' => route('person.show', $person->slug),
                'lastmod' => $person->updated_at?->toAtomString(),
                'priority' => '0.6',
            ]);
        });

        Award::published()->get(['slug', 'updated_at'])->each(function (Award $award) use ($urls) {
            $urls->push([
                'loc' => route('awards.show', $award->slug),
                'lastmod' => $award->updated_at?->toAtomString(),
                'priority' => '0.6',
            ]);
        });

        $xml = view('sitemap.xml', ['urls' => $urls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
