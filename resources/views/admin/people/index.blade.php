@extends('layouts.admin')

@section('title', 'People — Admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="section-title">People</h1>
    <a href="{{ route('admin.people.create') }}" class="btn-primary text-xs">Add person</a>
</div>

@if(session('success'))
    <p class="mb-6 border border-archive-border bg-archive-light px-4 py-3 text-sm">{{ session('success') }}</p>
@endif

<form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label class="section-label mb-2 block">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name or position..." class="input-field max-w-xs">
    </div>
    <div>
        <label class="section-label mb-2 block">Status</label>
        <select name="status" class="input-field text-sm">
            <option value="">All</option>
            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
            <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
        </select>
    </div>
    <button type="submit" class="btn-primary text-xs">Filter</button>
</form>

<div class="overflow-x-auto border border-archive-border">
    <table class="w-full text-sm">
        <thead class="border-b border-archive-border bg-archive-light">
            <tr>
                <th class="px-4 py-3 text-left">Person</th>
                <th class="px-4 py-3 text-left">Position</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Submitted by</th>
                <th class="px-4 py-3 text-left">Created</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($people as $person)
                <tr class="border-b border-archive-border">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="{{ $person->avatar_url }}" alt="" class="h-10 w-10 rounded-full object-cover">
                            <a href="{{ route('admin.people.show', $person) }}" class="font-medium underline">{{ $person->name }}</a>
                        </div>
                    </td>
                    <td class="px-4 py-3">{{ $person->position }}</td>
                    <td class="px-4 py-3 capitalize">{{ $person->status }}</td>
                    <td class="px-4 py-3">{{ $person->submittedBy?->username ?? '—' }}</td>
                    <td class="px-4 py-3 text-archive-gray">{{ $person->created_at->format('M j, Y') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-2 text-xs uppercase tracking-wider">
                            <a href="{{ route('admin.people.show', $person) }}" class="underline">View</a>
                            <a href="{{ route('admin.people.edit', $person) }}" class="underline">Edit</a>
                            @if($person->status !== 'approved')
                                <form method="POST" action="{{ route('admin.people.approve', $person) }}" class="inline">@csrf<button type="submit" class="underline">Approve</button></form>
                            @endif
                            @if($person->status !== 'rejected')
                                <form method="POST" action="{{ route('admin.people.reject', $person) }}" class="inline">@csrf<button type="submit" class="underline">Reject</button></form>
                            @endif
                            <form method="POST" action="{{ route('admin.people.destroy', $person) }}" class="inline" onsubmit="return confirm('Delete this person?')">@csrf @method('DELETE')<button type="submit" class="underline text-red-700">Delete</button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-archive-gray">No people found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $people->links() }}</div>
@endsection
