@extends('layouts.app')

@section('title', 'Edit Profile — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-12 md:px-8">
    <p class="section-label mb-2">Account</p>
    <h1 class="section-title mb-2">Edit Profile</h1>
    <p class="mb-8 text-sm text-archive-gray">Update your public profile, avatar, and social links.</p>

    @if(session('success'))
        <p class="mb-6 border border-archive-border bg-archive-light px-4 py-3 text-sm">{{ session('success') }}</p>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <x-profile-form-fields :user="$user" />

        <div class="flex flex-wrap items-center gap-4 border-t border-archive-border pt-6">
            <button type="submit" class="btn-primary">Save changes</button>
            <a href="{{ route('profile.campaigns') }}" class="btn-outline text-xs">My Campaigns</a>
            <a href="{{ route('users.show', $user) }}" class="text-sm underline">View public profile</a>
        </div>
    </form>

    @if($user->avatar)
        <form method="POST" action="{{ route('profile.avatar.destroy') }}" class="mt-4" onsubmit="return confirm('Remove your avatar?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs uppercase tracking-widest text-archive-gray underline hover:text-archive-black">Remove avatar</button>
        </form>
    @endif
</div>
@endsection
