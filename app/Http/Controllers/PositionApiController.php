<?php

namespace App\Http\Controllers;

use App\Enums\PositionCategory;
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
            ->ordered()
            ->when($query !== '', fn ($q) => $q->limit(50), fn ($q) => $q->limit(500))
            ->get(['id', 'name', 'slug', 'category', 'sort_order']);

        return response()->json([
            'data' => $positions->map(fn (Position $position) => [
                'id' => $position->id,
                'name' => $position->name,
                'slug' => $position->slug,
                'category' => $position->category,
                'category_label' => $position->categoryLabel(),
            ]),
            'categories' => PositionCategory::options(),
        ]);
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = Position::create([
            'name' => $request->name,
            'slug' => Position::generateUniqueSlug($request->name),
            'category' => $request->input('category', PositionCategory::Other->value),
            'sort_order' => (int) (Position::query()->max('sort_order') ?? 0) + 1,
        ]);

        return response()->json([
            'data' => [
                'id' => $position->id,
                'name' => $position->name,
                'slug' => $position->slug,
                'category' => $position->category,
                'category_label' => $position->categoryLabel(),
            ],
        ], 201);
    }
}
