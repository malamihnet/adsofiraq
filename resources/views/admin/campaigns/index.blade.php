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
        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
        <option value="pending" @selected(request('status') === 'pending')>Pending</option>
        <option value="approved" @selected(request('status') === 'approved')>Approved</option>
        <option value="needs_changes" @selected(request('status') === 'needs_changes')>Needs Changes</option>
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
    <table id="admin-campaigns-table" class="w-full text-sm">
        <thead class="border-b border-archive-border bg-archive-light">
            <tr>
                <th class="px-4 py-3 text-left">Title</th>
                <th class="px-4 py-3 text-left">User</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Hero</th>
                <th class="px-4 py-3 text-left">Editor's Pick</th>
                <th class="px-4 py-3 text-left">Archive placement</th>
                <th class="px-4 py-3 text-left">Verified</th>
            </tr>
        </thead>
        <tbody>
            @foreach($campaigns as $campaign)
                <tr class="border-b border-archive-border" data-campaign-id="{{ $campaign->id }}">
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="inline-flex items-center gap-2 underline">
                            {{ $campaign->title }}
                            <span data-verified-badge @class(['hidden' => ! $campaign->is_verified])>
                                <x-verified-badge :verified="$campaign->is_verified" />
                            </span>
                        </a>
                    </td>
                    <td class="px-4 py-3">{{ $campaign->user?->username ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <select
                            data-inline-field="status"
                            data-inline-url="{{ route('admin.campaigns.inline', $campaign) }}"
                            data-campaign-id="{{ $campaign->id }}"
                            data-previous-value="{{ $campaign->status }}"
                            class="input-field min-w-[9rem] py-1 text-xs"
                            aria-label="Status for {{ $campaign->title }}"
                        >
                            <option value="draft" @selected($campaign->status === 'draft')>Draft</option>
                            <option value="pending" @selected($campaign->status === 'pending')>Pending</option>
                            <option value="approved" @selected($campaign->status === 'approved')>Approved</option>
                            <option value="needs_changes" @selected($campaign->status === 'needs_changes')>Needs Changes</option>
                            <option value="rejected" @selected($campaign->status === 'rejected')>Rejected</option>
                        </select>
                    </td>
                    <td class="px-4 py-3">
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                data-inline-field="is_hero"
                                data-inline-url="{{ route('admin.campaigns.inline', $campaign) }}"
                                data-campaign-id="{{ $campaign->id }}"
                                data-previous-checked="{{ $campaign->is_hero ? '1' : '0' }}"
                                @checked($campaign->is_hero)
                                class="rounded border-archive-border"
                                aria-label="Hero slider for {{ $campaign->title }}"
                            >
                            <span class="text-xs text-archive-gray">{{ $campaign->is_hero ? 'On' : 'Off' }}</span>
                        </label>
                    </td>
                    <td class="px-4 py-3">
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                data-inline-field="is_featured"
                                data-inline-url="{{ route('admin.campaigns.inline', $campaign) }}"
                                data-campaign-id="{{ $campaign->id }}"
                                data-previous-checked="{{ $campaign->is_featured ? '1' : '0' }}"
                                @checked($campaign->is_featured)
                                class="rounded border-archive-border"
                                aria-label="Editor's Pick for {{ $campaign->title }}"
                            >
                            <span class="text-xs text-archive-gray">{{ $campaign->is_featured ? 'On' : 'Off' }}</span>
                        </label>
                    </td>
                    <td class="px-4 py-3">
                        @if($campaign->archive_placement_enabled && $campaign->archive_page && $campaign->archive_position)
                            <span class="border border-archive-black bg-archive-light px-2 py-0.5 text-xs uppercase tracking-wider">
                                Page {{ $campaign->archive_page }} / Pos {{ $campaign->archive_position }}
                            </span>
                        @else
                            <span class="text-xs text-archive-gray">Auto</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input
                                type="checkbox"
                                data-inline-field="is_verified"
                                data-inline-url="{{ route('admin.campaigns.inline', $campaign) }}"
                                data-campaign-id="{{ $campaign->id }}"
                                data-previous-checked="{{ $campaign->is_verified ? '1' : '0' }}"
                                @checked($campaign->is_verified)
                                class="rounded border-archive-border"
                                aria-label="Verified for {{ $campaign->title }}"
                            >
                            <span class="text-xs text-archive-gray">{{ $campaign->is_verified ? 'On' : 'Off' }}</span>
                        </label>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $campaigns->links() }}</div>
@endsection
