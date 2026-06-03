<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\PersonRelatedService;
use App\Services\SeoService;
use App\Services\StructuredDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function __construct(
        protected StructuredDataService $structuredData,
        protected SeoService $seo,
        protected PersonRelatedService $relatedPeople,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $people = Person::query()
            ->public()
            ->with('positionRelation:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('position', 'like', '%'.$search.'%')
                        ->orWhereHas('positionRelation', fn ($position) => $position->where('name', 'like', '%'.$search.'%'))
                        ->orWhereHas('campaigns', function ($campaigns) use ($search) {
                            $campaigns->public()
                                ->where('campaign_person.role', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('ranking_score')
            ->orderBy('name')
            ->paginate(16)
            ->withQueryString();

        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'People', 'url' => route('people.index')],
            ]),
        ];

        return view('people.index', compact('people', 'schema'));
    }

    public function show(Person $person): View
    {
        if ($person->status !== 'approved') {
            abort(404);
        }

        $canonicalUrl = route('person.show', $person);
        $person->load('positionRelation:id,name');

        $creditedCampaigns = $person->campaigns()
            ->public()
            ->with(['brands', 'agencies'])
            ->orderByDesc('campaigns.published_at')
            ->orderByDesc('campaigns.id')
            ->get();

        $creditedCampaignsCount = $creditedCampaigns->count();

        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Creatives', 'url' => route('people.index')],
                ['name' => $person->name, 'url' => $canonicalUrl],
            ]),
            $this->structuredData->person($person, $canonicalUrl, $creditedCampaignsCount),
        ];

        $seo = $this->seo->forPerson($person, $canonicalUrl, $creditedCampaignsCount);
        $relatedPeople = $this->relatedPeople->related($person);

        return view('people.show', compact(
            'person',
            'canonicalUrl',
            'schema',
            'seo',
            'creditedCampaigns',
            'creditedCampaignsCount',
            'relatedPeople',
        ));
    }
}
