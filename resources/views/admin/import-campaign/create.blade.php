@extends('layouts.admin')

@section('title', 'Import Campaign — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.campaigns.index') }}" class="text-sm underline">&larr; All campaigns</a>
    <h1 class="section-title mt-4">Import Campaign</h1>
    <p class="mt-2 max-w-2xl text-sm text-archive-gray">
        Paste a campaign URL (for example, an Ads of the World page). Click Import to create a
        <strong>pending</strong> campaign immediately and open it for review. Nothing is published automatically.
    </p>
</div>

@if(session('error'))
    <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ session('error') }}
        @if(session('duplicate_campaign_id'))
            @php $duplicate = \App\Models\Campaign::find(session('duplicate_campaign_id')); @endphp
            @if($duplicate)
                <p class="mt-2">
                    <a href="{{ route('admin.campaigns.edit', $duplicate) }}" class="underline">Open existing campaign</a>
                </p>
            @endif
        @endif
    </div>
@endif

<form method="POST" action="{{ route('admin.import-campaign.store') }}" class="max-w-2xl border border-archive-border bg-white p-6">
    @csrf

    <label for="url" class="section-label mb-2 block">Campaign URL</label>
    <input
        type="url"
        name="url"
        id="url"
        value="{{ old('url') }}"
        required
        placeholder="https://www.adsoftheworld.com/campaigns/..."
        class="input-field"
    >
    @error('url')
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror

    <p class="mt-3 text-xs text-archive-gray">
        Imported media is stored for admin review. Please verify usage rights before approving.
    </p>

    <button type="submit" class="btn-primary mt-6">Import Campaign</button>
</form>

<div class="mt-10 max-w-3xl border border-archive-border bg-white p-6">
    <h2 class="font-display text-lg">Debug AOTW Media Parser</h2>
    <p class="mt-2 text-sm text-archive-gray">
        Fetch a live Ads of the World campaign URL and preview extracted thumbnail, gallery stills, and videos
        without importing. Compare results with the live AOTW page before running a bulk import.
    </p>

    <form method="POST" action="{{ route('admin.import-campaign.debug-parse') }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <label for="debug_url" class="section-label mb-2 block">Campaign URL</label>
            <input
                type="url"
                name="url"
                id="debug_url"
                value="{{ old('url') }}"
                required
                placeholder="https://www.adsoftheworld.com/campaigns/..."
                class="input-field"
            >
            @error('debug_url')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="btn-secondary">Preview Extracted Media</button>
    </form>

    @if(!empty($debugPreview))
        <div class="mt-6 space-y-4 border-t border-archive-border pt-6 text-sm">
            <p class="text-archive-gray">
                Source:
                <a href="{{ $debugPreview['source_url'] ?? '#' }}" class="underline break-all" target="_blank" rel="noopener">
                    {{ $debugPreview['source_url'] ?? '—' }}
                </a>
            </p>
            <div>
                <p class="section-label mb-1">Thumbnail URL</p>
                <p class="font-mono text-xs break-all text-archive-black">{{ $debugPreview['thumbnail_url'] ?? '—' }}</p>
            </div>
            <div>
                <p class="section-label mb-1">Gallery stills ({{ count($debugPreview['still_urls'] ?? []) }})</p>
                @forelse($debugPreview['still_urls'] ?? [] as $stillUrl)
                    <p class="font-mono text-xs break-all text-archive-black">{{ $stillUrl }}</p>
                @empty
                    <p class="text-archive-gray">None extracted.</p>
                @endforelse
            </div>
            <div>
                <p class="section-label mb-1">Videos ({{ count($debugPreview['video_urls'] ?? []) }})</p>
                @forelse($debugPreview['video_urls'] ?? [] as $videoUrl)
                    <p class="font-mono text-xs break-all text-archive-black">{{ $videoUrl }}</p>
                @empty
                    <p class="text-archive-gray">None extracted.</p>
                @endforelse
            </div>
            @if(!empty($debugPreview['dom_inspection']))
                <div>
                    <p class="section-label mb-1">DOM inspection</p>
                    <p class="text-xs text-archive-gray">
                        #main found: {{ ($debugPreview['dom_inspection']['main_found'] ?? false) ? 'yes' : 'no' }}
                        @if(!empty($debugPreview['dom_inspection']['main_selector']))
                            ({{ $debugPreview['dom_inspection']['main_selector'] }})
                        @endif
                        · Media block candidates: {{ count($debugPreview['dom_inspection']['media_block_candidates'] ?? []) }}
                    </p>
                    @if(!empty($debugPreview['dom_inspection']['media_block_candidates']))
                        <pre class="mt-2 overflow-x-auto rounded border border-archive-border bg-archive-cream p-3 text-xs">{{ json_encode($debugPreview['dom_inspection']['media_block_candidates'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif
                    @if(!empty($debugPreview['dom_inspection']['images_in_main']))
                        <p class="mt-3 text-xs font-semibold">Images inside #main (first {{ count($debugPreview['dom_inspection']['images_in_main']) }})</p>
                        <pre class="mt-1 overflow-x-auto rounded border border-archive-border bg-archive-cream p-3 text-xs">{{ json_encode($debugPreview['dom_inspection']['images_in_main'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif
                    @if(!empty($debugPreview['dom_inspection']['container_classes']))
                        <p class="mt-3 text-xs font-semibold">Container classes under #main (first 20)</p>
                        <pre class="mt-1 overflow-x-auto rounded border border-archive-border bg-archive-cream p-3 text-xs">{{ json_encode($debugPreview['dom_inspection']['container_classes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif
                </div>
            @endif
            <div>
                <p class="section-label mb-1">Raw media blocks ({{ $debugPreview['raw_media_block_count'] ?? 0 }})</p>
                @forelse($debugPreview['media_blocks'] ?? [] as $block)
                    <div class="mb-3 rounded border border-archive-border bg-archive-cream p-3">
                        <p class="text-xs font-semibold">Block #{{ ($block['index'] ?? 0) + 1 }} — {{ $block['type'] ?? 'unknown' }}</p>
                        @if(!empty($block['urls']))
                            <ul class="mt-1 list-inside list-disc font-mono text-xs break-all">
                                @foreach($block['urls'] as $blockUrl)
                                    <li>{{ $blockUrl }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @empty
                    <p class="text-archive-gray">No media blocks detected.</p>
                @endforelse
            </div>
            <div>
                <p class="section-label mb-1">Skipped URLs ({{ count($debugPreview['skipped_urls'] ?? []) }})</p>
                @forelse($debugPreview['skipped_urls'] ?? [] as $skipped)
                    <p class="font-mono text-xs break-all text-archive-black">
                        <span class="text-red-700">[{{ $skipped['reason'] ?? 'unknown' }}]</span>
                        {{ $skipped['url'] ?? '' }}
                    </p>
                @empty
                    <p class="text-archive-gray">None skipped.</p>
                @endforelse
            </div>
            @if(!empty($debugPreview['debug']))
                <div>
                    <p class="section-label mb-1">Parser debug (raw)</p>
                    <pre class="overflow-x-auto rounded border border-archive-border bg-archive-cream p-3 text-xs">{{ json_encode($debugPreview['debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            @endif
        </div>
    @endif
</div>

<div class="mt-10 max-w-2xl border border-archive-border bg-white p-6">
    <h2 class="font-display text-lg">Repair Missing Media</h2>
    <p class="mt-2 text-sm text-archive-gray">
        Re-fetch Ads of the World pages for imported campaigns and download missing thumbnails and stills into local storage.
    </p>

    @if(session('success'))
        <div class="mt-4 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.import-campaign.repair-media') }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <label for="campaign_id" class="section-label mb-2 block">Single campaign ID (optional)</label>
            <input type="number" name="campaign_id" id="campaign_id" min="1" class="input-field" placeholder="Leave empty to scan all imported campaigns">
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="replace" value="1" class="rounded border-archive-border">
            Replace existing imported media files before re-downloading
        </label>
        <button type="submit" class="btn-secondary">Repair Missing Media</button>
    </form>
</div>

<div class="mt-6 max-w-2xl border border-archive-border bg-white p-6">
    <h2 class="font-display text-lg">Remove Duplicate Stills</h2>
    <p class="mt-2 text-sm text-archive-gray">
        Remove duplicate imported stills (same source URL or identical image content). Keeps WebP when available,
        then lowest sort order. Deletes duplicate database rows and storage files.
    </p>

    <form method="POST" action="{{ route('admin.import-campaign.remove-duplicate-stills') }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <label for="dedup_campaign_id" class="section-label mb-2 block">Single campaign ID (optional)</label>
            <input type="number" name="campaign_id" id="dedup_campaign_id" min="1" class="input-field" placeholder="Leave empty to scan all campaigns">
        </div>
        <div>
            <label for="dedup_limit" class="section-label mb-2 block">Limit (optional, all campaigns only)</label>
            <input type="number" name="limit" id="dedup_limit" min="1" class="input-field" placeholder="No limit">
        </div>
        <button type="submit" class="btn-secondary">Remove Duplicate Stills</button>
    </form>
</div>

<div class="mt-6 max-w-2xl border border-archive-border bg-white p-6">
    <h2 class="font-display text-lg">Sync Public Storage</h2>
    <p class="mt-2 text-sm text-archive-gray">
        Copy files from <code class="text-xs">storage/app/public</code> to the web-facing storage root
        (<code class="text-xs">public_html/storage</code> on cPanel when
        <code class="text-xs">PUBLIC_STORAGE_SYNC_PATH</code> is set). Includes campaigns,
        campaign-revisions, agency logos, and agency covers.
    </p>

    <form method="POST" action="{{ route('admin.sync-public-storage') }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <label for="sync_campaign_id" class="section-label mb-2 block">Single campaign ID (optional)</label>
            <input type="number" name="campaign_id" id="sync_campaign_id" min="1" class="input-field" placeholder="Leave empty to sync all directories">
        </div>
        <button type="submit" class="btn-secondary">Sync Public Storage</button>
    </form>
</div>
@endsection
