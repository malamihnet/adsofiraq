@extends('layouts.admin')

@section('title', $user->name . ' — Admin')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <a href="{{ route('admin.users.index') }}" class="text-sm underline">&larr; All users</a>
    <div class="flex gap-3">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn-outline text-xs">Edit user</a>
        <a href="{{ route('users.show', $user) }}" class="btn-outline text-xs" target="_blank" rel="noopener">Public profile</a>
    </div>
</div>

@if(session('success'))
    <p class="mb-6 border border-archive-border bg-archive-light px-4 py-3 text-sm">{{ session('success') }}</p>
@endif
@if(session('error'))
    <p class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</p>
@endif

<div class="mb-8 flex flex-wrap items-start gap-6 border border-archive-border p-6">
    <img src="{{ $user->avatar_url }}" alt="" class="h-20 w-20 rounded-full object-cover">
    <div>
        <h1 class="section-title inline-flex items-center gap-2">
            {{ $user->name }}
            <x-verified-badge :verified="$user->is_verified" />
        </h1>
        <p class="mt-1 text-archive-gray">{{ '@'.$user->username }} · {{ $user->email }}</p>
        @if($user->bio)
            <p class="mt-4 max-w-xl text-sm leading-relaxed">{{ $user->bio }}</p>
        @endif
        <x-user-social-links :user="$user" class="mt-4" />
    </div>
</div>

<div class="mb-8 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
    <div class="border border-archive-border p-4">
        <p class="section-label mb-1">Role</p>
        <p class="capitalize">{{ $user->role }}</p>
    </div>
    <div class="border border-archive-border p-4">
        <p class="section-label mb-1">Campaigns</p>
        <p>{{ $user->campaigns_count }} ({{ $user->approved_campaigns_count ?? 0 }} approved)</p>
    </div>
    <div class="border border-archive-border p-4">
        <p class="section-label mb-1">Bookmarks</p>
        <p>{{ $user->bookmarks_count ?? 0 }}</p>
    </div>
    <div class="border border-archive-border p-4">
        <p class="section-label mb-1">Watching</p>
        <p>{{ $user->campaign_watchers_count ?? 0 }}</p>
    </div>
</div>

<div class="mb-8 grid gap-4 text-sm sm:grid-cols-2">
    <div class="border border-archive-border p-4 space-y-2">
        <p><strong>Email verified:</strong> {{ $user->email_verified_at ? $user->email_verified_at->format('M j, Y g:i A') : 'No' }}</p>
        <p><strong>Platform verified:</strong>
            @if($user->is_verified)
                Yes — {{ $user->verified_at?->format('M j, Y g:i A') }}
                @if($user->verifiedBy) by {{ $user->verifiedBy->name }} @endif
            @else
                No
            @endif
        </p>
        <p><strong>Username last changed:</strong> {{ $user->username_changed_at?->format('M j, Y g:i A') ?? 'Never' }}</p>
    </div>
    <div class="border border-archive-border p-4 space-y-2">
        <p><strong>Joined:</strong> {{ $user->created_at->format('M j, Y g:i A') }}</p>
        <p><strong>Updated:</strong> {{ $user->updated_at->format('M j, Y g:i A') }}</p>
    </div>
</div>

@if($user->id !== auth()->id())
    <div id="delete" class="border border-red-200 bg-red-50 p-6">
        <p class="section-label mb-2 text-red-800">Delete user</p>
        <p class="mb-4 text-sm text-red-800">Deleting this user cannot be undone.</p>

        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete {{ $user->name }}? This cannot be undone.')">
            @csrf
            @method('DELETE')

            @if($user->campaigns_count > 0)
                <div class="mb-4">
                    <label class="section-label mb-2 block text-red-800">Reassign {{ $user->campaigns_count }} campaign(s) to</label>
                    <select name="reassign_to" required class="input-field max-w-md">
                        <option value="">Select user...</option>
                        @foreach($reassignCandidates as $candidate)
                            <option value="{{ $candidate->id }}">{{ $candidate->name }} ({{ '@'.$candidate->username }})</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="submit" class="btn-outline border-red-700 text-red-700 hover:bg-red-700 hover:text-white text-xs">Delete user permanently</button>
        </form>
    </div>
@endif
@endsection
