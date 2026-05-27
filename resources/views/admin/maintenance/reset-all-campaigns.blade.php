@extends('layouts.admin')

@section('title', 'Reset All Campaigns — Admin')

@section('content')
<h1 class="section-title mb-2">Maintenance</h1>
<p class="mb-6 text-sm text-archive-gray">Danger zone — full campaign wipe</p>

<div class="max-w-2xl border-2 border-red-600 bg-red-50 p-6 text-red-950">
    <h2 class="font-display text-xl">Reset All Campaigns</h2>
    <p class="mt-3 text-sm leading-relaxed">
        This permanently deletes <strong>every campaign</strong> on Ads of Iraq, including all stills, videos,
        revisions, bookmarks, watchers, import queue data, and campaign media files on disk.
    </p>
    <p class="mt-3 text-sm font-semibold">
        Users, profiles, people, agencies, brands, and taxonomy tables are NOT deleted.
    </p>
    <p class="mt-3 text-sm">
        Run a <strong>dry run</strong> first to review counts. To execute, type exactly:
        <code class="rounded bg-white px-1 py-0.5 text-xs">{{ $confirmationPhrase }}</code>
    </p>
</div>

<div class="mt-6 max-w-2xl border border-archive-border bg-white p-6">
    <h3 class="font-display text-lg">Current counts</h3>
    <dl class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
        <div>
            <dt class="text-archive-gray">Campaigns</dt>
            <dd class="text-xl font-medium">{{ number_format($counts['campaigns']) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Assets</dt>
            <dd class="text-xl font-medium">{{ number_format($counts['assets']) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Videos</dt>
            <dd class="text-xl font-medium">{{ number_format($counts['videos']) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Media files</dt>
            <dd class="text-xl font-medium">{{ number_format($counts['media_files']) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Bookmarks</dt>
            <dd class="text-xl font-medium">{{ number_format($counts['bookmarks']) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Watchers</dt>
            <dd class="text-xl font-medium">{{ number_format($counts['watchers']) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Revisions</dt>
            <dd class="text-xl font-medium">{{ number_format($counts['revisions']) }}</dd>
        </div>
        <div>
            <dt class="text-archive-gray">Import queue</dt>
            <dd class="text-xl font-medium">{{ number_format($counts['import_queue']) }}</dd>
        </div>
    </dl>

    <form method="POST" action="{{ route('admin.maintenance.reset-all-campaigns.dry-run') }}" class="mt-6">
        @csrf
        <button type="submit" class="btn-secondary">Dry run (counts only)</button>
    </form>
</div>

<div class="mt-6 max-w-2xl border-2 border-red-600 bg-white p-6">
    <h3 class="font-display text-lg text-red-800">Execute destructive reset</h3>

    <form method="POST" action="{{ route('admin.maintenance.reset-all-campaigns.start') }}" class="mt-4 space-y-4">
        @csrf
        <div>
            <label for="confirmation" class="section-label mb-2 block">
                Type <span class="font-mono">{{ $confirmationPhrase }}</span> to confirm
            </label>
            <input
                type="text"
                name="confirmation"
                id="confirmation"
                class="input-field font-mono"
                autocomplete="off"
                placeholder="{{ $confirmationPhrase }}"
                value="{{ old('confirmation') }}"
            >
            @error('confirmation')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="border border-red-700 bg-red-700 px-4 py-2 text-sm font-medium uppercase tracking-wider text-white hover:bg-red-800">
            Delete all campaigns
        </button>
    </form>
</div>

<p class="mt-6 text-sm">
    <a href="{{ route('admin.maintenance.clean-duplicate-media') }}" class="underline">← Clean duplicate media</a>
    <span class="mx-2 text-archive-gray">·</span>
    <a href="{{ route('admin.check-new-campaigns.index') }}" class="underline">Check New Campaigns (Iraq import)</a>
</p>
@endsection
