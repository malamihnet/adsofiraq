@props([
    'credits' => '',
    'mentions' => [],
])

@php
    $initialMentions = collect($mentions)->values()->all();
    $creditsValue = (string) old('credits', $credits ?? '');
    $mentionsJsonOld = old('credits_mentions_json', old('credit_mentions'));
    if ($mentionsJsonOld === null) {
        $mentionsJsonOld = json_encode($initialMentions);
    }
    $peopleSearchUrl = auth()->check() && auth()->user()->isAdmin()
        ? route('admin.api.people.search')
        : route('api.people.search');
    $showMentionsDebug = config('app.debug') || (auth()->check() && auth()->user()->isAdmin());
@endphp

<div
    class="credits-mentions-field"
    data-mentions-bound="false"
    data-people-search-url="{{ $peopleSearchUrl }}"
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

    @if($showMentionsDebug)
        <div
            data-mentions-debug
            class="mt-3 rounded border border-amber-300 bg-amber-50 p-3 font-mono text-xs text-archive-black"
        >
            <p class="mb-1 font-semibold text-amber-900">Mentions debug</p>
            <p>Mentions JS loaded: <span data-debug-js>no</span></p>
            <p>Textarea found: <span data-debug-textarea>no</span></p>
            <p>Last query: <span data-debug-query>—</span></p>
            <p>Results count: <span data-debug-results>0</span></p>
            <button
                type="button"
                data-mentions-test-btn
                class="mt-2 rounded border border-archive-border bg-white px-3 py-1.5 text-xs hover:bg-neutral-50"
            >
                Test people dropdown
            </button>
        </div>
    @endif

    @error('credits')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('credits_mentions_json')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    @error('credit_mentions')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
