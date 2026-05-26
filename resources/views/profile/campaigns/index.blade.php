@extends('layouts.app')

@section('title', 'My Campaigns — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <div class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="section-label mb-2">Profile</p>
            <h1 class="section-title">My Campaigns</h1>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('campaigns.create') }}" class="btn-primary">Submit a Campaign</a>
            <a href="{{ route('profile.show.redirect') }}" class="btn-outline">Back to Profile</a>
        </div>
    </div>

    <div class="mb-8 flex flex-wrap gap-2">
        @php
            $tabs = [
                'all' => ['label' => 'All', 'count' => $counts['all']],
                'approved' => ['label' => 'Approved', 'count' => $counts['approved']],
                'pending' => ['label' => 'Pending Review', 'count' => $counts['pending']],
                'updates-pending' => ['label' => 'Updates Pending', 'count' => $counts['updates-pending']],
                'rejected' => ['label' => 'Rejected', 'count' => $counts['rejected']],
            ];
        @endphp

        @foreach($tabs as $key => $info)
            <a
                href="{{ route('profile.campaigns', ['tab' => $key]) }}"
                class="{{ $tab === $key ? 'btn-primary' : 'btn-outline' }} text-xs"
            >
                {{ $info['label'] }} ({{ $info['count'] }})
            </a>
        @endforeach
    </div>

    @if($campaigns->isEmpty())
        <div class="border border-archive-border bg-white p-12 text-center">
            <p class="text-archive-gray">No campaigns found for this filter.</p>
        </div>
    @else
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($campaigns as $campaign)
                <article class="border border-archive-border bg-white">
                    <a href="{{ route('campaigns.show', $campaign) }}" class="block">
                        <div class="aspect-[4/3] overflow-hidden border-b border-archive-border bg-archive-light">
                            @if($campaign->thumbnail_url)
                                <img src="{{ $campaign->thumbnail_url }}" alt="{{ $campaign->title }}"
                                     class="h-full w-full object-cover" loading="lazy">
                            @else
                                <div class="flex h-full items-center justify-center text-archive-gray">
                                    <span class="text-xs uppercase tracking-widest">No image</span>
                                </div>
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-display text-lg leading-snug">{{ $campaign->title }}</h3>
                                <x-status-badge :status="$campaign->status" />
                            </div>

                            <div class="mt-2 text-xs text-archive-gray">
                                @if($campaign->brands->isNotEmpty())
                                    <span>{{ $campaign->brands->pluck('name')->take(2)->join(', ') }}</span>
                                @endif
                                @if($campaign->agencies->isNotEmpty())
                                    <span>@if($campaign->brands->isNotEmpty()) &middot; @endif{{ $campaign->agencies->pluck('name')->take(2)->join(', ') }}</span>
                                @endif
                            </div>

                            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-archive-gray">
                                <span>Created: {{ $campaign->created_at->format('M d, Y') }}</span>
                                <span>Updated: {{ $campaign->updated_at->format('M d, Y') }}</span>
                            </div>

                            @if($campaign->status === 'approved' && $campaign->pendingRevision)
                                <p class="mt-3 text-xs text-archive-gray">
                                    <span class="font-medium text-archive-black">Updates pending:</span>
                                    Your changes are waiting for admin review.
                                </p>
                            @endif
                        </div>
                    </a>

                    <div class="border-t border-archive-border p-5">
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('campaigns.edit', $campaign) }}" class="btn-primary text-xs">Edit Campaign</a>

                            @if($campaign->status === 'pending' || $campaign->status === 'rejected' || ($campaign->status === 'approved' && $campaign->pendingRevision))
                                <a href="{{ route('campaigns.pending-review', $campaign) }}" class="btn-outline text-xs">View Pending Review</a>
                            @endif

                            @if($campaign->status === 'approved')
                                <a href="{{ route('campaigns.show', $campaign) }}" class="btn-outline text-xs">View Public Campaign</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection

