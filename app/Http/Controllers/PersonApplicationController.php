<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonApplicationRequest;
use App\Models\Person;
use App\Services\PersonPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PersonApplicationController extends Controller
{
    public function __construct(
        protected PersonPhotoService $photos,
    ) {}

    public function create(): View
    {
        return view('people.apply');
    }

    public function store(StorePersonApplicationRequest $request): RedirectResponse
    {
        Person::create([
            'name' => $request->name,
            'position' => $request->position,
            'photo_path' => $this->photos->store($request->file('photo')),
            'bio' => $request->bio,
            'website_url' => $request->website_url,
            'official_profile_url' => $request->official_profile_url,
            'work_1' => $request->work_1,
            'work_2' => $request->work_2,
            'work_3' => $request->work_3,
            'submission_notes' => $request->submission_notes,
            'status' => 'pending',
            'submitted_by' => $request->user()?->id,
        ]);

        return redirect()->route('people.apply')
            ->with('success', 'Your profile has been submitted. Please wait for admin approval before it appears publicly.');
    }
}
