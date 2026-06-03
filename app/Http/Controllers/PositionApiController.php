<?php

namespace App\Http\Controllers;

use App\Enums\PositionCategory;
use App\Http\Requests\StorePositionRequest;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PositionApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $hasCategory = Schema::hasColumn('positions', 'category');

        $builder = Position::query()
            ->when($query !== '', function ($q) use ($query, $hasCategory) {
                $q->where(function ($inner) use ($query, $hasCategory) {
                    $inner->where('name', 'like', '%'.$query.'%');

                    if ($hasCategory) {
                        $inner->orWhere('category', 'like', '%'.$query.'%');
                    }
                });
            });

        if ($hasCategory) {
            $builder->ordered();
        } else {
            $builder->orderBy('sort_order')->orderBy('name');
        }

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
