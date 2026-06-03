@extends('layouts.admin')

@section('title', $campaign->title . ' — Admin')

@section('content')
<div class="mb-8 flex flex-wrap items-center gap-4">
    <h1 class="section-title inline-flex items-center gap-2">
        {{ $campaign->title }}
        <x-verified-badge :verified="$campaign->is_verified" />
    </h1>
    <x-status-badge :status="$campaign->status" />
    @if($campaign->is_featured)
        <span class="text-xs uppercase tracking-wider">Editor's Pick</span>
    @endif
    @if($campaign->is_hero)
        <span class="border border-archive-black px-2 py-0.5 text-xs uppercase tracking-wider">Hero</span>
    @endif
</div>

<div class="mb-8 flex flex-wrap gap-3">
    @if($campaign->status !== 'approved')
        <form method="POST" action="{{ route('admin.campaigns.approve', $campaign) }}">@csrf<button class="btn-primary text-xs">Approve</button></form>
    @endif
    @if($campaign->status !== 'rejected')
        <form method="POST" action="{{ route('admin.campaigns.reject', $campaign) }}">@csrf<button class="btn-outline text-xs">Reject</button></form>
    @endif
    <form method="POST" action="{{ route('admin.campaigns.feature', $campaign) }}">@csrf<button class="btn-outline text-xs">{{ $campaign->is_featured ? "Remove Editor's Pick" : "Add Editor's Pick" }}</button></form>
    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn-outline text-xs">Edit</a>
    <form method="POST" action="{{ route('admin.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete this campaign?')">
        @csrf @method('DELETE')
        <button class="btn-outline text-xs text-red-600">Delete</button>
    </form>
</div>

<div class="mb-8 max-w-xl border border-archive-border p-6">
    <p class="section-label mb-4">Homepage hero slider</p>
    <form method="POST" action="{{ route('admin.campaigns.hero', $campaign) }}">
        @csrf
        @method('PUT')
        <label class="mb-4 flex cursor-pointer items-center gap-3">
            <input
                type="checkbox"
                name="is_hero"
                value="1"
                class="rounded border-archive-border text-archive-black focus:ring-archive-black"
                @checked(old('is_hero', $campaign->is_hero))
                @disabled($campaign->status !== 'approved')
            >
            <span class="text-sm">Show in homepage hero slider</span>
        </label>
        @error('is_hero')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror
        @if($campaign->status !== 'approved')
            <p class="mb-4 text-sm text-archive-gray">
                Only approved campaigns can be featured in the homepage slider.
            </p>
        @endif
        <div class="mb-4">
            <label for="hero_order" class="section-label mb-2 block">Hero order</label>
            <input
                type="number"
                name="hero_order"
                id="hero_order"
                min="1"
                max="99"
                value="{{ old('hero_order', $campaign->hero_order) }}"
                class="input-field max-w-[120px]"
                placeholder="1"
                @disabled($campaign->status !== 'approved')
            >
            <p class="mt-1 text-xs text-archive-gray">Hero order is optional. By default, the latest campaign approved on Ads of Iraq appears first.</p>
        </div>
        @error('hero_order')
            <p class="mb-4 text-sm text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" class="btn-primary text-xs" @disabled($campaign->status !== 'approved')>Save hero settings</button>
    </form>
</div>

<x-admin.verification-form
    :action="route('admin.campaigns.verification', $campaign)"
    :model="$campaign"
/>

<div class="grid gap-8 lg:grid-cols-2">
    <div>
        @if($campaign->thumbnail_url)
            <img src="{{ $campaign->thumbnail_url }}" alt="" class="mb-6 max-h-64 object-cover">
        @endif
        <x-video-embed :campaign="$campaign" />
        @if($campaign->galleryStills()->isNotEmpty())
            <div class="mt-8">
                <h2 class="section-label mb-4">Stills / Assets ({{ $campaign->galleryStills()->count() }})</h2>
                <x-campaign-gallery :stills="$campaign->galleryStills()" :title="$campaign->title" />
            </div>
        @endif
        <div class="mt-6 whitespace-pre-line text-sm">{{ $campaign->description }}</div>
        @if($campaign->submission_notes)
            <div class="mt-6 border border-archive-border p-4">
                <p class="section-label mb-2">Submission Notes</p>
                <p class="text-sm">{{ $campaign->submission_notes }}</p>
            </div>
        @endif
        @if($campaign->admin_notes)
            <div class="mt-6 border border-archive-border p-4">
                <p class="section-label mb-2">Admin Notes</p>
                <p class="text-sm whitespace-pre-line">{{ $campaign->admin_notes }}</p>
            </div>
        @endif
    </div>
    <div class="text-sm space-y-2">
        <p>
            <strong>Submitted by:</strong>
            @if($campaign->user)
                <a href="{{ route('users.show', $campaign->user) }}" class="underline">
                    {{ $campaign->user->username ?? $campaign->user->name }}
                </a>
            @else
                —
            @endif
        </p>
        @php($campaignVideos = app(\App\Services\CampaignVideoService::class)->listForAdmin($campaign))
        <div>
            <p><strong>Videos:</strong>
                @if(empty($campaignVideos))
                    None
                @else
                    {{ count($campaignVideos) }} video(s)
                @endif
            </p>
            @if(! empty($campaignVideos))
                <ul class="mt-2 space-y-2 text-archive-gray">
                    @foreach($campaignVideos as $index => $video)
                        <li>
                            <span class="text-archive-black">{{ $index + 1 }}.</span>
                            {{ $video['label'] }}
                            @if($video['title'])
                                — {{ $video['title'] }}
                            @endif
                            @if($video['type'] === 'file' && $video['file_url'])
                                (<a href="{{ $video['file_url'] }}" target="_blank" rel="noopener" class="underline">file</a>)
                            @elseif($video['url'])
                                (<span class="break-all">{{ $video['url'] }}</span>)
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <p><strong>Brands:</strong> {{ $campaign->brands->pluck('name')->join(', ') ?: '—' }}</p>
        <p><strong>Agencies:</strong> {{ $campaign->agencies->pluck('name')->join(', ') ?: '—' }}</p>
        <p><strong>Industries:</strong> {{ $campaign->industries->pluck('name')->join(', ') ?: '—' }}</p>
        <p><strong>Medium types:</strong> {{ $campaign->mediumTypes->pluck('name')->join(', ') ?: '—' }}</p>
        <p><strong>Countries:</strong> {{ $campaign->countries->pluck('name')->join(', ') ?: '—' }}</p>
        @if($campaign->source_url)
            <p>
                <strong>Imported from:</strong>
                <a href="{{ $campaign->source_url }}" target="_blank" rel="noopener noreferrer" class="break-all underline">
                    {{ $campaign->source_url }}
                </a>
            </p>
        @endif
        <p><strong>Student:</strong> {{ $campaign->is_student ? 'Yes' : 'No' }}</p>
        <p><strong>NSFW:</strong> {{ $campaign->is_nsfw ? 'Yes' : 'No' }}</p>
        <a href="{{ route('campaigns.show', $campaign) }}" class="inline-block mt-4 underline">View public page</a>
    </div>
</div>
@endsection
