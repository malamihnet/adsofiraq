@extends('layouts.admin')

@section('title', 'Campaign Revisions — Admin — Ads of Iraq')

@section('content')
<div class="mb-8 flex items-center justify-between gap-4">
    <h1 class="section-title">Revisions</h1>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.revisions.index', ['status' => 'pending']) }}" class="{{ $status === 'pending' ? 'btn-primary' : 'btn-outline' }} text-xs">Pending</a>
        <a href="{{ route('admin.revisions.index', ['status' => 'approved']) }}" class="{{ $status === 'approved' ? 'btn-primary' : 'btn-outline' }} text-xs">Approved</a>
        <a href="{{ route('admin.revisions.index', ['status' => 'rejected']) }}" class="{{ $status === 'rejected' ? 'btn-primary' : 'btn-outline' }} text-xs">Rejected</a>
    </div>
</div>

<div class="overflow-x-auto border border-archive-border">
    <table class="w-full text-sm">
        <thead class="border-b border-archive-border bg-archive-light">
            <tr>
                <th class="px-4 py-3 text-left">Campaign</th>
                <th class="px-4 py-3 text-left">Submitted by</th>
                <th class="px-4 py-3 text-left">Status</th>
                <th class="px-4 py-3 text-left">Submitted</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($revisions as $revision)
                <tr class="border-b border-archive-border">
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $revision->campaign?->title ?? '—' }}</div>
                        <div class="text-xs text-archive-gray">#{{ $revision->id }}</div>
                    </td>
                    <td class="px-4 py-3">{{ $revision->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3"><x-status-badge :status="$revision->status" /></td>
                    <td class="px-4 py-3 text-archive-gray">
                        {{ $revision->submitted_at?->format('M d, Y H:i') ?? $revision->created_at->format('M d, Y H:i') }}
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.revisions.show', $revision) }}" class="underline">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-10 text-center text-archive-gray" colspan="5">No revisions.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">{{ $revisions->links() }}</div>
@endsection

