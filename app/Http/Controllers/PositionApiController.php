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
        $hasCategory = Position::hasCategoryColumn();

        $builder = Position::query()
            ->when($query !== '', function ($q) use ($query, $hasCategory) {
                $q->where(function ($inner) use ($query, $hasCategory) {
                    $inner->where('name', 'like', '%'.$query.'%');

                    if ($hasCategory) {
                        $inner->orWhere('category', 'like', '%'.$query.'%');
                    }
                });
            });

        $builder->ordered();

        $columns = $hasCategory
            ? ['id', 'name', 'slug', 'category', 'sort_order']
            : ['id', 'name', 'slug', 'sort_order'];

        $positions = $builder
            ->when($query !== '', fn ($q) => $q->limit(50), fn ($q) => $q->limit(500))
            ->get($columns);

        return response()->json([
            'data' => $positions->map(fn (Position $position) => [
                'id' => $position->id,
                'name' => $position->name,
                'slug' => $position->slug,
                'category' => $hasCategory ? $position->category : PositionCategory::Other->value,
                'category_label' => $hasCategory ? $position->categoryLabel() : PositionCategory::Other->label(),
            ]),
            'categories' => PositionCategory::options(),
        ]);
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $attributes = [
            'name' => $request->name,
            'slug' => Position::generateUniqueSlug($request->name),
            'sort_order' => (int) (Position::query()->max('sort_order') ?? 0) + 1,
        ];

        if (Position::hasCategoryColumn()) {
            $attributes['category'] = $request->input('category', PositionCategory::Other->value);
        }

        $position = Position::create($attributes);

        return response()->json([
            'data' => [
                'id' => $position->id,
                'name' => $position->name,
                'slug' => $position->slug,
                'category' => Position::hasCategoryColumn() ? $position->category : PositionCategory::Other->value,
                'category_label' => Position::hasCategoryColumn() ? $position->categoryLabel() : PositionCategory::Other->label(),
            ],
        ], 201);
    }
}
