<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PositionCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminPositionRequest;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PositionController extends Controller
{
    public function index(Request $request): View
    {
        $positions = Position::query()
            ->search($request->input('search'))
            ->when(
                $request->filled('category') && Position::hasCategoryColumn(),
                fn ($query) => $query->where('category', $request->input('category')),
            )
            ->ordered()
            ->get()
            ->map(function (Position $position) {
                $position->setAttribute('people_count', $position->peopleCount());
                $position->setAttribute('campaign_credits_count', $position->campaignCreditsCount());

                return $position;
            });

        return view('admin.positions.index', [
            'positions' => $positions,
            'categories' => PositionCategory::options(),
            'categoryColumnReady' => Position::hasCategoryColumn(),
        ]);
    }

    public function create(): View
    {
        return view('admin.positions.create', [
            'categories' => PositionCategory::options(),
        ]);
    }

    public function store(AdminPositionRequest $request): RedirectResponse
    {
        $attributes = [
            'name' => $request->name,
            'slug' => Position::generateUniqueSlug($request->name),
            'sort_order' => $request->integer('sort_order', (int) (Position::query()->max('sort_order') ?? 0) + 1),
        ];

        if (Position::hasCategoryColumn()) {
            $attributes['category'] = $request->category;
        }

        $position = Position::create($attributes);

        return redirect()->route('admin.positions.index')
            ->with('success', "Position \"{$position->name}\" created.");
    }

    public function edit(Position $position): View
    {
        return view('admin.positions.edit', [
            'position' => $position,
            'categories' => PositionCategory::options(),
        ]);
    }

    public function update(AdminPositionRequest $request, Position $position): RedirectResponse
    {
        $attributes = [
            'name' => $request->name,
            'sort_order' => $request->integer('sort_order', $position->sort_order),
        ];

        if (Position::hasCategoryColumn()) {
            $attributes['category'] = $request->category;
        }

        $position->update($attributes);

        return redirect()->route('admin.positions.index')
            ->with('success', "Position \"{$position->name}\" updated.");
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->isInUse()) {
            return redirect()->route('admin.positions.index')
                ->with('error', "Cannot delete \"{$position->name}\" — it is used by people or campaign credits.");
        }

        $position->delete();

        return redirect()->route('admin.positions.index')
            ->with('success', 'Position deleted.');
    }
}
