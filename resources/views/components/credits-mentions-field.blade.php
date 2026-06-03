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
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
    $peopleSearchUrl = $isAdmin ? route('admin.api.people.search') : route('api.people.search');
    $peopleStoreUrl = $isAdmin ? route('admin.api.people.store') : route('api.people.store');
@endphp

<div
    class="credits-mentions-field"
    data-mentions-bound="false"
    data-people-search-url="{{ $peopleSearchUrl }}"
    data-people-store-url="{{ $peopleStoreUrl }}"
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

@auth
    @push('modals')
        @once('credits-mention-create-person-modal')
            <x-credits-mention-create-person-modal />
        @endonce
    @endpush
@endauth
