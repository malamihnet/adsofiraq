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
    <h2 class="font-display text-lg">Sync Public Storage</h2>
    <p class="mt-2 text-sm text-archive-gray">
        Copy campaign files from <code class="text-xs">storage/app/public</code> to the web-facing
        <code class="text-xs">public_html/storage</code> folder (for hosts without a storage symlink).
        Use this after repair if images still do not appear on the live site.
    </p>

    <form method="POST" action="{{ route('admin.import-campaign.sync-public-storage') }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <label for="sync_campaign_id" class="section-label mb-2 block">Single campaign ID (optional)</label>
            <input type="number" name="campaign_id" id="sync_campaign_id" min="1" class="input-field" placeholder="Leave empty to sync all campaign media">
        </div>
        <button type="submit" class="btn-secondary">Sync Public Storage</button>
    </form>
</div>
@endsection
