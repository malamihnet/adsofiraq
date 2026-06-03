@props([
    'credits' => '',
    'mentions' => [],
])

@php
    use App\Enums\PositionCategory;
    use App\Models\Position;
    use Illuminate\Support\Facades\Schema;

    $initialMentions = collect($mentions)->values()->all();
    $creditsValue = (string) old('credits', $credits ?? '');
    $mentionsJsonOld = old('credits_mentions_json', old('credit_mentions'));
    if ($mentionsJsonOld === null) {
        $mentionsJsonOld = json_encode($initialMentions);
    }
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $peopleSearchUrl = $isAdmin ? route('admin.api.people.search') : route('api.people.search');
    $peopleStoreUrl = $isAdmin ? route('admin.api.people.store') : route('api.people.store');
    $positionsUrl = $isAdmin ? route('admin.api.positions.index') : route('api.positions.index');

    $positionsEmbed = ['data' => [], 'categories' => PositionCategory::options()];
    if (auth()->check() && Schema::hasTable('positions')) {
        try {
            $hasCategory = Position::hasCategoryColumn();
            $query = Position::query();
            $positionsEmbed['data'] = ($hasCategory ? $query->ordered() : $query->orderBy('sort_order')->orderBy('name'))
                ->limit(500)
                ->get($hasCategory ? ['id', 'name', 'slug', 'category', 'sort_order'] : ['id', 'name', 'slug', 'sort_order'])
                ->map(fn (Position $position) => [
                    'id' => $position->id,
                    'name' => $position->name,
                    'slug' => $position->slug,
                    'category' => $hasCategory ? $position->category : PositionCategory::Other->value,
                    'category_label' => $hasCategory ? $position->categoryLabel() : PositionCategory::Other->label(),
                ])
                ->values()
                ->all();
        } catch (\Throwable) {
            $positionsEmbed = ['data' => [], 'categories' => PositionCategory::options()];
        }
    }
@endphp

<div
    class="credits-mentions-field"
    data-mentions-bound="false"
    data-people-search-url="{{ $peopleSearchUrl }}"
    data-people-store-url="{{ $peopleStoreUrl }}"
    data-positions-url="{{ $positionsUrl }}"
    data-positions-json="{{ e(json_encode($positionsEmbed, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)) }}"
    data-is-admin="{{ $isAdmin ? '1' : '0' }}"
    data-mentions-debug="{{ config('app.debug') ? '1' : '0' }}"
>
    <label class="section-label mb-2 block" for="credits">Credits</label>
    <p class="mb-2 text-xs text-archive-gray">
        Type <code class="text-archive-black">@</code> to tag people and link their profiles. Example:
        <span class="text-archive-black">Director: @Mustafa Amer</span>
        Plain text without @ stays unlinked.
    </p>

    <div class="relative">
        <textarea
            id="credits"
            data-mentions-enabled="true"
            name="credits"
            rows="6"
            class="input-field text-sm"
            placeholder="Director: Mustafa Amer&#10;Editor: @Ali Hassan"
        >{{ $creditsValue }}</textarea>

        <input
            type="hidden"
            name="credits_mentions_json"
            id="credits_mentions_json"
            value="{{ htmlspecialchars((string) $mentionsJsonOld, ENT_QUOTES, 'UTF-8') }}"
        >
    </div>

    @error('credits')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('credits_mentions_json')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('credit_mentions')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
