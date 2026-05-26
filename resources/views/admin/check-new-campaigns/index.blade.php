@extends('layouts.admin')

@section('title', 'Check New Campaigns — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.dashboard') }}" class="text-sm underline">&larr; Dashboard</a>
    <h1 class="section-title mt-4">Check for New Iraq Campaigns</h1>
    <p class="mt-2 max-w-2xl text-sm text-archive-gray">
        Checks Ads of the World Iraq archive and imports only campaigns that do not already exist on Ads of Iraq.
    </p>
</div>

<div class="max-w-3xl border border-archive-border bg-white p-6">
    <form method="POST" action="{{ route('admin.check-new-campaigns.start') }}">
        @csrf
        <button type="submit" class="btn-primary">Check &amp; Import New Campaigns</button>
    </form>

    @if($lastBatch)
        <div class="mt-8 border-t border-archive-border pt-6 text-sm">
            <p class="section-label mb-2">Last run</p>
            <p>Status: <strong>{{ $lastBatch->status }}</strong></p>
            <p class="mt-1 text-archive-gray">
                Imported: {{ $lastBatch->imported_count }},
                Failed: {{ $lastBatch->failed_count }},
                Skipped: {{ $lastBatch->skipped_count }},
                Existing: {{ $lastBatch->existing_skipped_count ?? 0 }}
            </p>
            <p class="mt-3">
                <a class="underline" href="{{ route('admin.check-new-campaigns.show', $lastBatch) }}">View progress</a>
            </p>
        </div>
    @endif
</div>
@endsection

