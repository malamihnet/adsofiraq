@extends('layouts.admin')

@section('title', 'Archive Delay — Admin')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="section-title">Archive Delay</h1>
        <p class="mt-2 max-w-2xl text-sm text-archive-gray">
            Campaigns will not appear before their start page/position on <code>/campaigns</code> (Latest sort). Newer approved campaigns push delayed items down on each request (not stored as fixed slots). Refresh this page after approving new campaigns to verify calculated positions.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.archive-placements.clear-legacy-manual-order') }}" onsubmit="return confirm('Clear all legacy manual_order values?');">
            @csrf
            <button type="submit" class="btn-outline text-xs">Clear legacy manual_order</button>
        </form>
        <form method="POST" action="{{ route('admin.archive-placements.clear-all') }}" onsubmit="return confirm('Clear all archive delays?');">
            @csrf
            <button type="submit" class="btn-outline text-xs">Clear all delays</button>
        </form>
    </div>
</div>

<div class="mb-10 border border-archive-border p-6">
    <h2 class="section-label mb-4">Quick delay</h2>
    <form method="POST" action="{{ route('admin.archive-placements.store') }}" class="grid gap-4 md:grid-cols-4">
        @csrf
        <div class="md:col-span-2">
            <label class="section-label mb-2 block" for="campaign_id">Campaign ID</label>
            <input type="number" name="campaign_id" id="campaign_id" value="{{ old('campaign_id') }}" min="1" required class="input-field">
            @error('campaign_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="section-label mb-2 block" for="archive_page">Start page</label>
            <input type="number" name="archive_page" id="archive_page" value="{{ old('archive_page', 1) }}" min="1" required class="input-field">
            @error('archive_page')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="section-label mb-2 block" for="archive_position">Start position</label>
            <input type="number" name="archive_position" id="archive_position" value="{{ old('archive_position', 1) }}" min="1" max="100" required class="input-field">
            @error('archive_position')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-4">
            <p class="mb-3 text-xs text-archive-gray">
                Campaign will not appear before this page/position. Newer campaigns can still push it down naturally.
            </p>
            <button type="submit" class="btn-primary text-xs">Save delay</button>
        </div>
    </form>
</div>

<div class="overflow-x-auto border border-archive-border">
    <table class="w-full text-sm">
        <thead class="border-b border-archive-border bg-archive-light">
            <tr>
                <th class="px-4 py-3 text-left">Campaign</th>
                <th class="px-4 py-3 text-left">Start Page</th>
                <th class="px-4 py-3 text-left">Start Position</th>
                <th class="px-4 py-3 text-left">Start Index</th>
                <th class="px-4 py-3 text-left">Current Index</th>
                <th class="px-4 py-3 text-left">Current Page</th>
                <th class="px-4 py-3 text-left">Current Position</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($campaigns as $campaign)
                <tr class="border-b border-archive-border">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="underline">{{ $campaign->title }}</a>
                        <span class="ml-2 text-xs text-archive-gray">#{{ $campaign->id }}</span>
                    </td>
                    <td class="px-4 py-3">{{ $campaign->archive_page }}</td>
                    <td class="px-4 py-3">{{ $campaign->archive_position }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $campaign->archive_start_index ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-xs">{{ $campaign->calculated_index ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $campaign->estimated_archive_page ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $campaign->estimated_archive_slot ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="underline">Edit</a>
                        <form method="POST" action="{{ route('admin.archive-placements.destroy', $campaign) }}" class="inline" onsubmit="return confirm('Remove this archive delay?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ml-3 underline">Remove delay</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-12 text-center text-archive-gray">No archive delays configured yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
