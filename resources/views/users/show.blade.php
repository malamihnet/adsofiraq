@extends('layouts.app')

@section('title', $user->name . ' (@' . $user->username . ') — Ads of Iraq')
@section('meta_description', $user->bio ? \Illuminate\Support\Str::limit($user->bio, 160) : 'Profile of ' . $user->name . ' on Ads of Iraq')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <div class="flex flex-col gap-8 border-b border-archive-border pb-12 md:flex-row md:items-start">
        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-32 w-32 shrink-0 rounded-full object-cover">
        <div class="flex-1">
            <h1 class="font-display text-3xl inline-flex items-center gap-2 flex-wrap">
                {{ $user->name }}
                <x-verified-badge :verified="$user->is_verified" />
            </h1>
            <p class="mt-1 text-archive-gray">{{ '@' . $user->username }}</p>
            @if($user->bio)
                <p class="mt-4 max-w-xl leading-relaxed">{{ $user->bio }}</p>
            @endif
            <x-user-social-links :user="$user" class="mt-4" />
            <div class="mt-6 flex flex-wrap gap-6 text-sm">
                <span><strong>{{ $user->followers()->count() }}</strong> followers</span>
                <span><strong>{{ $user->following()->count() }}</strong> following</span>
                <span><strong>{{ $user->approvedCampaigns()->count() }}</strong> campaigns</span>
            </div>
            <div class="mt-6 flex flex-wrap items-center gap-4">
                @auth
                    @if(auth()->id() === $user->id)
                        <a href="{{ route('profile.edit') }}" class="btn-primary text-xs">Edit profile</a>
                    @else
                        <x-follow-button :user="$user" :is-following="$isFollowing" />
                    @endif
                @endauth
            </div>
        </div>
    </div>

    <section class="mt-12">
        <h2 class="section-title mb-8">Campaigns</h2>
        @if($campaigns->count())
            <x-campaign-grid :campaigns="$campaigns" />
        @else
            <p class="text-archive-gray">No published campaigns yet.</p>
        @endif
    </section>
</div>
@endsection
