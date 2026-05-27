@extends('layouts.admin')

@section('title', 'Iraq Import — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-sm underline">&larr; Dashboard</a>
    <h1 class="section-title mt-4">Iraq Campaign Import</h1>
    <p class="mt-2 max-w-2xl text-sm text-archive-gray">
        Import campaigns from the Ads of the World Iraq archive. Use incremental check for day-to-day updates,
        or a full rebuild after reset to rebuild the archive oldest-first.
    </p>
</div>

<div class="grid max-w-3xl gap-6">
    <div class="border border-archive-border bg-white p-6">
        <h2 class="text-lg font-semibold">Check New Campaigns</h2>
        <p class="mt-2 text-sm text-archive-gray">
            Scans newest listing pages first and imports only campaigns not already on Ads of Iraq.
            Stops automatically after {{ config('import.new_import_stop_after_existing', 20) }} consecutive existing campaigns.
        </p>
        <form method="POST" action="{{ route('admin.check-new-campaigns.start') }}" class="mt-4">
            @csrf
            <button type="submit" class="btn-primary">Check New Campaigns</button>
        </form>

        @if($lastIncrementalBatch)
            <div class="mt-6 border-t border-archive-border pt-4 text-sm">
                <p class="section-label mb-2">Last incremental run</p>
                <p>Status: <strong>{{ $lastIncrementalBatch->status }}</strong></p>
                <p class="mt-1 text-archive-gray">
                    Imported: {{ $lastIncrementalBatch->imported_count }},
                    Failed: {{ $lastIncrementalBatch->failed_count }},
                    Skipped: {{ $lastIncrementalBatch->skipped_count }},
                    Existing: {{ $lastIncrementalBatch->existing_skipped_count ?? 0 }}
                </p>
                <p class="mt-3">
                    <a class="underline" href="{{ route('admin.check-new-campaigns.show', $lastIncrementalBatch) }}">View progress</a>
                </p>
            </div>
        @endif
    </div>

    <div class="border border-archive-border bg-white p-6">
        <h2 class="text-lg font-semibold">Fresh Iraq Import / Full Rebuild</h2>
        <p class="mt-2 text-sm text-archive-gray">
            After a full reset, use this to rebuild the archive in chronological order: oldest Iraq campaigns first,
            newest last. Starts from the last listing page and works backward. Does not stop after existing campaigns.
        </p>
        <form method="POST" action="{{ route('admin.check-new-campaigns.start-full-rebuild') }}" class="mt-4"
              onsubmit="return confirm('Start a full Iraq import from the oldest campaigns? This may take a long time. Use after Reset All Campaigns.');">
            @csrf
            <button type="submit" class="btn-outline">Fresh Iraq Import / Full Rebuild</button>
        </form>

        @if($lastFullRebuildBatch)
            <div class="mt-6 border-t border-archive-border pt-4 text-sm">
                <p class="section-label mb-2">Last full rebuild</p>
                <p>Status: <strong>{{ $lastFullRebuildBatch->status }}</strong></p>
                <p class="mt-1 text-archive-gray">
                    Imported: {{ $lastFullRebuildBatch->imported_count }},
                    Failed: {{ $lastFullRebuildBatch->failed_count }},
                    Skipped: {{ $lastFullRebuildBatch->skipped_count }},
                    Existing: {{ $lastFullRebuildBatch->existing_skipped_count ?? 0 }}
                </p>
                <p class="mt-3">
                    <a class="underline" href="{{ route('admin.check-new-campaigns.show', $lastFullRebuildBatch) }}">View progress</a>
                </p>
            </div>
        @endif
    </div>
</div>
@endsection
