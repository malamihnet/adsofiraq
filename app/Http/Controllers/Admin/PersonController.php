<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminPersonRequest;
use App\Models\Person;
use App\Services\PersonPhotoService;
use App\Services\PlatformVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function __construct(
        protected PersonPhotoService $photos,
        protected PlatformVerificationService $verificationService,
    ) {}

    public function index(Request $request): View
    {
        $query = Person::with(['submittedBy', 'approvedBy'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }

        $query->statusFilter($request->input('status'));

        return view('admin.people.index', [
            'people' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.people.create');
    }

    public function store(AdminPersonRequest $request): RedirectResponse
    {
        $data = $this->personAttributes($request, approve: $request->status === 'approved', admin: $request->user());
        $data['photo_path'] = $this->photos->store($request->file('photo'));

        $person = Person::create($data);

        $this->verificationService->update($person->fresh(), $request->user(), $request->boolean('is_verified'));

        return redirect()->route('admin.people.show', $person)
            ->with('success', 'Person created successfully.');
    }

    public function show(Person $person): View
    {
        $person->load(['submittedBy', 'approvedBy', 'verifiedBy']);

        return view('admin.people.show', compact('person'));
    }

    public function edit(Person $person): View
    {
        return view('admin.people.edit', compact('person'));
    }

    public function update(AdminPersonRequest $request, Person $person): RedirectResponse
    {
        $wasApproved = $person->status === 'approved';
        $data = $this->personAttributes($request, approve: $request->status === 'approved', admin: $request->user(), existing: $person);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->photos->replace($person->photo_path, $request->file('photo'));
        } elseif ($request->boolean('remove_photo')) {
            $this->photos->delete($person->photo_path);
            $data['photo_path'] = null;
        }

        if ($request->status !== 'approved' && $wasApproved) {
            $data['approved_at'] = null;
            $data['approved_by'] = null;
        }

        $person->update($data);

        $this->verificationService->update($person->fresh(), $request->user(), $request->boolean('is_verified'));

        return redirect()->route('admin.people.show', $person)
            ->with('success', 'Person updated successfully.');
    }

    public function approve(Request $request, Person $person): RedirectResponse
    {
        $person->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Profile approved and published.');
    }

    public function reject(Request $request, Person $person): RedirectResponse
    {
        $person->update([
            'status' => 'rejected',
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return back()->with('success', 'Profile rejected.');
    }

    public function destroy(Person $person): RedirectResponse
    {
        $this->photos->delete($person->photo_path);
        $person->delete();

        return redirect()->route('admin.people.index')
            ->with('success', 'Person deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function personAttributes(AdminPersonRequest $request, bool $approve, $admin, ?Person $existing = null): array
    {
        $data = $request->safe()->only([
            'name',
            'position',
            'bio',
            'website_url',
            'official_profile_url',
            'work_1',
            'work_2',
            'work_3',
            'status',
            'submission_notes',
        ]);

        if ($approve && ($existing === null || $existing->status !== 'approved')) {
            $data['approved_at'] = now();
            $data['approved_by'] = $admin->id;
        }

        return $data;
    }
}
