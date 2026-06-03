<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePositionRequest;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        $positions = Position::query()
            ->when($query !== '', fn ($q) => $q->where('name', 'like', '%'.$query.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'data' => $positions,
        ]);
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = Position::create([
            'name' => $request->name,
            'slug' => Position::generateUniqueSlug($request->name),
            'sort_order' => (int) (Position::query()->max('sort_order') ?? 0) + 1,
        ]);

        return response()->json([
            'data' => [
                'id' => $position->id,
                'name' => $position->name,
                'slug' => $position->slug,
            ],
        ], 201);
    }
}
