@extends('layouts.admin')

@section('title', 'Campaigns — Admin')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <h1 class="section-title">Campaign Moderation</h1>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.archive-placements.index') }}" class="btn-outline text-xs">Archive Placements</a>
        <a href="{{ route('admin.campaigns.create') }}" class="btn-primary text-xs">Add Campaign</a>
    </div>
</div>

<form method="GET" class="mb-6 flex flex-wrap gap-4">
    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search..." class="input-field max-w-xs">
    <select name="status" class="input-field max-w-xs">
        <option value="">All statuses</option>
        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
        <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
    </select>
    <x-admin.verification-filter />
    <select name="archive_placement" class="input-field max-w-xs">
        <option value="">All archive modes</option>
        <option value="placed" @selected(request('archive_placement') === 'placed')>Placed in archive</option>
        <option value="auto" @selected(request('archive_placement') === 'auto')>Auto archive</option>
    </select>
    <button type="submit" class="btn-primary">Filter</button>
</form>

<div class="overflow-x-auto border border-archive-border">
    <table class="w-full text-sm">
        <thead class="border-b border-archive-border bg-archive-light">
            <tr>
                <th class="px-4 py-3 text-left">Title</th>
                <th class="px-4 py-3 text-left">User</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Featured</th>
                <th class="px-4 py-3 text-left">Hero</th>
                <th class="px-4 py-3 text-left">Archive placement</th>
                <th class="px-4 py-3 text-left">Verified</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($campaigns as $campaign)
                <tr class="border-b border-archive-border">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-2">
                            {{ $campaign->title }}
                            <x-verified-badge :verified="$campaign->is_verified" />
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $campaign->user?->username ?? '—' }}</td>
                    <td class="px-4 py-3"><x-status-badge :status="$campaign->status" /></td>
                    <td class="px-4 py-3">{{ $campaign->is_featured ? 'Yes' : 'No' }}</td>
                    <td class="px-4 py-3">
                        @if($campaign->is_hero)
                            <span class="border border-archive-black px-2 py-0.5 text-xs uppercase tracking-wider">Hero</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($campaign->archive_placement_enabled && $campaign->archive_page && $campaign->archive_position)
                            <span class="border border-archive-black bg-archive-light px-2 py-0.5 text-xs uppercase tracking-wider">
                                Page {{ $campaign->archive_page }} / Pos {{ $campaign->archive_position }}
                            </span>
                        @else
                            Auto
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($campaign->is_verified)
                            <span class="text-xs uppercase tracking-wider text-[#1d9bf0]">Verified</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.campaigns.show', $campaign) }}" class="underline">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $campaigns->links() }}</div>
@endsection
