@extends('layouts.admin')

@section('title', 'Users — Admin')

@section('content')
<h1 class="section-title mb-8">Users</h1>

@if(session('success'))
    <p class="mb-6 border border-archive-border bg-archive-light px-4 py-3 text-sm">{{ session('success') }}</p>
@endif
@if(session('error'))
    <p class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</p>
@endif

<form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label class="section-label mb-2 block">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name, username, email..." class="input-field max-w-xs">
    </div>
    <div>
        <label class="section-label mb-2 block">Role</label>
        <select name="role" class="input-field text-sm">
            <option value="">All roles</option>
            <option value="user" @selected(request('role') === 'user')>User</option>
            <option value="admin" @selected(request('role') === 'admin')>Admin</option>
        </select>
    </div>
    <div>
        <label class="section-label mb-2 block">Email</label>
        <select name="email_verified" class="input-field text-sm">
            <option value="">All</option>
            <option value="verified" @selected(request('email_verified') === 'verified')>Verified</option>
            <option value="unverified" @selected(request('email_verified') === 'unverified')>Not verified</option>
        </select>
    </div>
    <x-admin.verification-filter />
    <button type="submit" class="btn-primary text-xs">Filter</button>
</form>

<div class="overflow-x-auto border border-archive-border">
    <table class="w-full text-sm">
        <thead class="border-b border-archive-border bg-archive-light">
            <tr>
                <th class="px-4 py-3 text-left">User</th>
                <th class="px-4 py-3 text-left">Email</th>
                <th class="px-4 py-3 text-left">Role</th>
                <th class="px-4 py-3 text-left">Email verified</th>
                <th class="px-4 py-3 text-left">Platform</th>
                <th class="px-4 py-3 text-left">Joined</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr class="border-b border-archive-border">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $user->avatar_url }}" alt="" class="h-9 w-9 rounded-full object-cover">
                            <div>
                                <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center gap-1.5 font-medium underline">
                                    {{ $user->name }}
                                    <x-verified-badge :verified="$user->is_verified" />
                                </a>
                                <p class="text-xs text-archive-gray">{{ '@'.$user->username }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3 capitalize">{{ $user->role }}</td>
                    <td class="px-4 py-3">
                        @if($user->email_verified_at)
                            <span class="text-xs text-archive-black">Yes</span>
                        @else
                            <span class="text-xs text-archive-gray">No</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($user->is_verified)
                            <span class="text-xs uppercase tracking-wider text-[#1d9bf0]">Verified</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-archive-gray">{{ $user->created_at->format('M j, Y') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-3 text-xs uppercase tracking-wider">
                            <a href="{{ route('admin.users.show', $user) }}" class="underline">View</a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="underline">Edit</a>
                            @if($user->id !== auth()->id())
                                <a href="{{ route('admin.users.show', $user) }}#delete" class="underline text-red-700">Delete</a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-archive-gray">No users found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $users->links() }}</div>
@endsection
