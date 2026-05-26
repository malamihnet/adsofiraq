@extends('layouts.admin')

@section('title', 'Revision #'.$revision->id.' — Admin — Ads of Iraq')

@section('content')
<div class="mb-8 flex flex-wrap items-center justify-between gap-4">
    <div>
        <p class="section-label mb-1">Revision #{{ $revision->id }}</p>
        <h1 class="section-title">{{ $campaign->title }}</h1>
        <p class="mt-2 text-sm text-archive-gray">
            Submitted by <span class="font-medium text-archive-black">{{ $revision->user?->name ?? '—' }}</span>
            @if($revision->submitted_at)
                &middot; {{ $revision->submitted_at->format('M d, Y H:i') }}
            @endif
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.revisions.index') }}" class="btn-outline text-xs">&larr; All revisions</a>
        <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn-outline text-xs">Open campaign</a>
    </div>
</div>

<div class="mb-8 flex flex-wrap items-center gap-3 border border-archive-border p-4">
    <x-status-badge :status="$revision->status" />
    @if($revision->status === 'pending')
        <form method="POST" action="{{ route('admin.revisions.approve', $revision) }}">@csrf
            <button class="btn-primary text-xs" onclick="return confirm('Approve and publish this update?')">Approve</button>
        </form>
        <form method="POST" action="{{ route('admin.revisions.reject', $revision) }}" class="flex items-center gap-2">@csrf
            <input type="text" name="review_notes" value="" placeholder="Optional review notes" class="input-field !py-2 !text-xs w-64">
            <button class="btn-outline text-xs" onclick="return confirm('Reject this update?')">Reject</button>
        </form>
    @else
        <span class="text-sm text-archive-gray">
            @if($revision->approvedBy)
                Reviewed by {{ $revision->approvedBy->name }}
            @endif
            @if($revision->approved_at)
                &middot; {{ $revision->approved_at->format('M d, Y H:i') }}
            @endif
        </span>
    @endif
</div>

<div class="grid gap-8 lg:grid-cols-2">
    <section class="border border-archive-border p-6">
        <h2 class="text-lg font-medium mb-4">Current (live)</h2>
        <dl class="space-y-3 text-sm">
            @foreach($current as $key => $value)
                <div>
                    <dt class="text-archive-gray">{{ str_replace('_', ' ', ucfirst($key)) }}</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ is_bool($value) ? ($value ? 'Yes' : 'No') : ($value ?? '—') }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <section class="border border-archive-border p-6">
        <h2 class="text-lg font-medium mb-4">Proposed</h2>
        <dl class="space-y-3 text-sm">
            @foreach($proposed as $key => $value)
                <div class="{{ in_array($key, $changedKeys, true) ? 'border-l-4 border-green-300 pl-3' : '' }}">
                    <dt class="text-archive-gray">{{ str_replace('_', ' ', ucfirst($key)) }}</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $value === null ? '—' : (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
</div>

<section class="mt-8 border border-archive-border p-6">
    <h2 class="text-lg font-medium mb-4">Taxonomies (proposed)</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
        @foreach(['brands','agencies','industries','medium_types','countries'] as $key)
            <div>
                <p class="text-archive-gray uppercase tracking-widest text-xs">{{ str_replace('_', ' ', $key) }}</p>
                <p class="mt-1">{{ collect($taxonomies[$key] ?? [])->filter()->join(', ') ?: '—' }}</p>
            </div>
        @endforeach
    </div>
</section>
@endsection

