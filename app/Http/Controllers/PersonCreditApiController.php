<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonCreditRequest;
use App\Models\Person;
use App\Models\Position;
use App\Services\CreditsMentionService;
use App\Services\PersonPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonCreditApiController extends Controller
{
    public function __construct(
        protected PersonPhotoService $photos,
        protected CreditsMentionService $mentions,
    ) {}

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        $people = Person::query()
            ->when(
                $request->user()?->isAdmin(),
                fn ($q) => $q->whereIn('status', ['approved', 'pending']),
                fn ($q) => $q->approved(),
            )
            ->when($query !== '', fn ($q) => $q->where(function ($builder) use ($query) {
                $builder->where('name', 'like', '%'.$query.'%')
                    ->orWhere('position', 'like', '%'.$query.'%');
            }))
            ->orderBy('name')
            ->limit(15)
            ->with('positionRelation:id,name')
            ->get(['id', 'name', 'position', 'position_id', 'slug', 'photo_path']);

        return response()->json([
            'data' => $people->map(fn (Person $person) => [
                'id' => $person->id,
                'name' => $person->name,
                'position' => $person->positionRelation?->name ?? $person->position,
                'slug' => $person->slug,
                'photo_url' => $person->photo_url,
            ]),
        ]);
    }

    public function store(StorePersonCreditRequest $request): JsonResponse
    {
        $status = $request->user()->isAdmin() && $request->boolean('approve')
            ? 'approved'
            : 'pending';

        $positionName = $this->mentions->resolvePositionName(
            $request->integer('position_id'),
            $request->input('position'),
        );

        $data = [
            'name' => $request->name,
            'position' => $positionName,
            'position_id' => $request->integer('position_id'),
            'slug' => Person::generateUniqueSlug($request->name),
            'status' => $status,
            'submitted_by' => $request->user()->id,
        ];

        if ($status === 'approved') {
            $data['approved_at'] = now();
            $data['approved_by'] = $request->user()->id;
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $this->photos->store($request->file('photo'));
        }

        $person = Person::create($data);

        return response()->json([
            'data' => [
                'id' => $person->id,
                'name' => $person->name,
                'position' => $person->position,
                'slug' => $person->slug,
                'photo_url' => $person->photo_url,
                'status' => $person->status,
            ],
        ], 201);
    }
}
