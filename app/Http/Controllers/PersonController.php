<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\StructuredDataService;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function __construct(
        protected StructuredDataService $structuredData,
    ) {}

    public function index(): View
    {
        $people = Person::public()
            ->orderByDesc('ranking_score')
            ->orderBy('name')
            ->paginate(16);

        return view('people.index', compact('people'));
    }

    public function show(Person $person): View
    {
        if ($person->status !== 'approved') {
            abort(404);
        }

        $canonicalUrl = route('person.show', $person);
        $schema = [
            $this->structuredData->breadcrumb([
                ['name' => 'Home', 'url' => url('/')],
                ['name' => 'Creatives', 'url' => route('people.index')],
                ['name' => $person->name, 'url' => $canonicalUrl],
            ]),
            $this->structuredData->person($person, $canonicalUrl),
        ];

        return view('people.show', compact('person', 'canonicalUrl', 'schema'));
    }
}
