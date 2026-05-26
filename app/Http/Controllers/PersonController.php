<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function index(): View
    {
        $people = Person::public()
            ->orderBy('name')
            ->paginate(16);

        return view('people.index', compact('people'));
    }

    public function show(Person $person): View
    {
        if ($person->status !== 'approved') {
            abort(404);
        }

        return view('people.show', compact('person'));
    }
}
