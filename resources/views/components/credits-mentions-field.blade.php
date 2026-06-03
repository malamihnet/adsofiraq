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
    $viteDiagnostics = $showMentionsDebug ? \App\Support\ViteBuildDiagnostics::collect() : null;
    $appJsBuild = $viteDiagnostics['manifest_app_file'] ?? 'missing from manifest';
    $viteAppAsset = $viteDiagnostics['vite_asset_url'] ?? null;
    $viteAppAssetError = $viteDiagnostics['vite_asset_error'] ?? null;
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
            <p class="mt-2 font-semibold text-amber-900">Vite build (Laravel)</p>
            <p>Manifest path: <code class="break-all">{{ $viteDiagnostics['manifest_path'] }}</code></p>
            <p>Manifest app.js file: <code>{{ $appJsBuild }}</code></p>
            <p>Manifest asset on disk: <strong>{{ $viteDiagnostics['manifest_asset_exists'] ? 'yes' : 'no' }}</strong></p>
            <p>Current app asset (Vite::asset): <code class="break-all">{{ $viteAppAsset ?? ('ERROR: '.$viteAppAssetError) }}</code></p>
            <p>Vite URL matches manifest: <strong>{{ $viteDiagnostics['vite_matches_manifest'] ? 'yes' : 'no' }}</strong></p>
            <p>Laravel public_path(): <code class="break-all">{{ $viteDiagnostics['public_path'] }}</code></p>
            <p class="mt-2 font-semibold text-amber-900">Build folders on server</p>
            <ul class="list-inside list-disc space-y-1">
                @foreach($viteDiagnostics['directory_checks'] as $label => $check)
                    <li>
                        <span class="font-medium">{{ $label }}</span>:
                        @if(! $check['exists'])
                            missing
                        @elseif(! $check['manifest_readable'])
                            no manifest
                        @else
                            <code>{{ $check['manifest_app_js'] ?? '?' }}</code>
                            — asset {{ $check['asset_file_exists'] ? 'exists' : 'MISSING' }}
                        @endif
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-amber-950">Page source should load exactly one app-*.js matching Vite::asset above. Search console for <code>CREDITS MENTIONS FILE LOADED</code>.</p>
            <p>Load marker in DOM: <span data-debug-marker>no</span></p>
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
