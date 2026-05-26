@extends('layouts.admin')

@section('title', 'Edit ' . $user->name . ' — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.users.show', $user) }}" class="text-sm underline">&larr; Back to user</a>
</div>

<h1 class="section-title mb-8">Edit User</h1>

@if(session('error'))
    <p class="mb-6 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</p>
@endif

<form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data" class="max-w-2xl space-y-6">
    @csrf
    @method('PUT')

    <x-profile-form-fields :user="$user" :show-username-hint="false" :show-avatar-remove="true" />

    <div class="border border-archive-border p-6 space-y-6">
        <p class="section-label">Account</p>

        <div>
            <label class="section-label mb-2 block">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input-field">
            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="section-label mb-2 block">Role</label>
            <select name="role" class="input-field">
                <option value="user" @selected(old('role', $user->role) === 'user')>User</option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
            </select>
            @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <label class="flex cursor-pointer items-center gap-3">
            <input type="checkbox" name="email_verified" value="1" class="rounded border-archive-border text-archive-black focus:ring-archive-black" @checked(old('email_verified', $user->email_verified_at !== null))>
            <span class="text-sm">Email verified</span>
        </label>

        <label class="flex cursor-pointer items-center gap-3">
            <input type="checkbox" name="is_verified" value="1" class="rounded border-archive-border text-archive-black focus:ring-archive-black" @checked(old('is_verified', $user->is_verified))>
            <span class="text-sm">Platform verified by Ads of Iraq</span>
        </label>

        @if($user->username_changed_at)
            <p class="text-xs text-archive-gray">Username last changed {{ $user->username_changed_at->format('M j, Y g:i A') }}</p>
        @endif
    </div>

    <div class="border border-archive-border p-6 space-y-4">
        <p class="section-label">Password reset (optional)</p>
        <p class="text-xs text-archive-gray">Leave blank to keep the current password.</p>
        <div>
            <label class="section-label mb-2 block">New password</label>
            <input type="password" name="password" class="input-field" autocomplete="new-password">
            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="section-label mb-2 block">Confirm password</label>
            <input type="password" name="password_confirmation" class="input-field" autocomplete="new-password">
        </div>
    </div>

    <div class="flex flex-wrap gap-4">
        <button type="submit" class="btn-primary">Save user</button>
        <a href="{{ route('admin.users.show', $user) }}" class="btn-outline">Cancel</a>
    </div>
</form>
@endsection
