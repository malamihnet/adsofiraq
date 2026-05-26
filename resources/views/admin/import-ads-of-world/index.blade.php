@extends('layouts.admin')

@section('title', 'Bulk Import — Ads of the World')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-sm underline">&larr; Dashboard</a>
    <h1 class="section-title mt-4">Bulk Import (Ads of the World)</h1>
    <p class="mt-2 max-w-2xl text-sm text-archive-gray">
        Import all campaigns from an Ads of the World country listing (e.g. Iraq). Campaigns are queued oldest-first,
        imported as <strong>approved</strong> with hero slider enabled. Images are saved as WebP; videos download without WebM conversion by default (faster on cPanel).
    </p>
</div>

@if(session('success'))
    <div class="mb-6 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

<div class="grid gap-8 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.import.queue.store') }}" class="border border-archive-border bg-white p-6">
        @csrf
        <p class="section-label mb-4">Start import</p>

        <label for="country_url" class="section-label mb-2 block">Country page URL</label>
        <input
            type="url"
            name="country_url"
            id="country_url"
            value="{{ old('country_url', $defaultCountryUrl) }}"
            required
            class="input-field"
            placeholder="https://www.adsoftheworld.com/countries/iraq"
        >
        @error('country_url')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <p class="mt-3 text-xs text-archive-gray">
            Discovers all pages and queues campaigns <strong>oldest first</strong> (last page → first page, last card → first card on each page).
            After you submit, keep the progress page open — imports run automatically in the browser, one campaign at a time.
        </p>

        <button type="submit" class="btn-primary mt-6">Start Bulk Import</button>
    </form>

    <div class="border border-archive-border bg-white p-6">
        <p class="section-label mb-4">Delete last import</p>
        <p class="text-sm text-archive-gray">
            Removes only the most recent bulk import batch — campaigns, videos, stills, thumbnails, and queue entries.
            Manual campaigns are not affected.
        </p>

        @if($lastBatch && ! $lastBatch->isDeleted())
            <p class="mt-4 text-xs text-archive-gray">
                Last batch: {{ $lastBatch->created_at->format('Y-m-d H:i') }} —
                {{ $lastBatch->imported_count }} imported,
                {{ $lastBatch->failed_count }} failed,
                {{ $lastBatch->skipped_count }} skipped
            </p>
        @endif

        <form method="POST" action="{{ route('admin.import.delete-last') }}" class="mt-6" onsubmit="return confirm('Delete the last Iraq bulk import and all its media? This cannot be undone.');">
            @csrf
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="btn-outline text-red-600">Delete Last Iraq Import</button>
        </form>
    </div>
</div>

@if($lastBatch)
    <div class="mt-8 border border-archive-border bg-archive-light p-6 text-sm">
        <p class="section-label mb-2">Recent batch</p>
        <p>Status: <strong>{{ $lastBatch->status }}</strong></p>
        <p class="mt-2">
            <a href="{{ route('admin.import-ads-of-world.show', $lastBatch) }}" class="underline">View progress / resume</a>
        </p>
    </div>
@endif
@endsection
