@extends('layouts.admin')

@section('title', $person->name . ' — Admin')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <a href="{{ route('admin.people.index') }}" class="text-sm underline">&larr; All people</a>
    <div class="flex gap-3">
        <a href="{{ route('admin.people.edit', $person) }}" class="btn-outline text-xs">Edit</a>
        @if($person->status === 'approved')
            <a href="{{ route('people.show', $person) }}" class="btn-outline text-xs" target="_blank" rel="noopener">Public profile</a>
        @endif
    </div>
</div>

@if(session('success'))
    <p class="mb-6 border border-archive-border bg-archive-light px-4 py-3 text-sm">{{ session('success') }}</p>
@endif

<div class="mb-8 flex flex-wrap gap-8 border border-archive-border p-6">
    <img src="{{ $person->photo_url }}" alt="" class="h-32 w-32 rounded-full object-cover">
    <div>
        <h1 class="section-title inline-flex items-center gap-2">{{ $person->name }} <x-verified-badge :verified="$person->is_verified" /></h1>
        <p class="mt-1 text-archive-gray">{{ $person->position }}</p>
        <p class="mt-4 text-sm capitalize"><strong>Status:</strong> {{ $person->status }}</p>
        @if($person->submittedBy)
            <p class="mt-2 text-sm"><strong>Submitted by:</strong> {{ $person->submittedBy->name }} ({{ '@'.$person->submittedBy->username }})</p>
        @endif
        @if($person->approved_at)
            <p class="mt-2 text-sm text-archive-gray">Approved {{ $person->approved_at->format('M j, Y g:i A') }} @if($person->approvedBy) by {{ $person->approvedBy->name }} @endif</p>
        @endif
    </div>
</div>

@if($person->bio)<p class="mb-6 max-w-2xl leading-relaxed">{{ $person->bio }}</p>@endif

@if($person->featured_works)
    <div class="mb-8">
        <p class="section-label mb-3">Featured work</p>
        <ul class="space-y-2 text-sm">
            @foreach($person->featured_works as $work)
                <li>{{ $work }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($person->submission_notes)
    <div class="mb-8 border border-archive-border p-4 text-sm">
        <p class="section-label mb-2">Submission notes</p>
        <p>{{ $person->submission_notes }}</p>
    </div>
@endif

<div class="flex flex-wrap gap-3">
    @if($person->status !== 'approved')
        <form method="POST" action="{{ route('admin.people.approve', $person) }}">@csrf<button type="submit" class="btn-primary text-xs">Approve</button></form>
    @endif
    @if($person->status !== 'rejected')
        <form method="POST" action="{{ route('admin.people.reject', $person) }}">@csrf<button type="submit" class="btn-outline text-xs">Reject</button></form>
    @endif
</div>
@endsection
