@extends('layouts.admin')

@section('title', 'Admin Dashboard — Ads of Iraq')

@section('content')
<h1 class="section-title mb-8">Dashboard</h1>
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
    <div class="border border-archive-border p-6">
        <p class="section-label">Total Users</p>
        <p class="mt-2 font-display text-3xl">{{ $totalUsers }}</p>
    </div>
    <div class="border border-archive-border p-6">
        <p class="section-label">Total Campaigns</p>
        <p class="mt-2 font-display text-3xl">{{ $totalCampaigns }}</p>
    </div>
    <div class="border border-archive-border p-6">
        <p class="section-label">Pending</p>
        <p class="mt-2 font-display text-3xl">{{ $pendingCampaigns }}</p>
    </div>
    <div class="border border-archive-border p-6">
        <p class="section-label">Approved</p>
        <p class="mt-2 font-display text-3xl">{{ $approvedCampaigns }}</p>
    </div>
</div>

@if($recentPending->count())
<section class="mt-12">
    <h2 class="text-lg font-medium mb-6">Recent Pending Submissions</h2>
    <div class="overflow-x-auto border border-archive-border">
        <table class="w-full text-sm">
            <thead class="border-b border-archive-border bg-archive-light">
                <tr>
                    <th class="px-4 py-3 text-left">Title</th>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Submitted</th>
                    <th class="px-4 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentPending as $campaign)
                    <tr class="border-b border-archive-border">
                        <td class="px-4 py-3">{{ $campaign->title }}</td>
                        <td class="px-4 py-3">{{ $campaign->user?->username ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $campaign->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.campaigns.show', $campaign) }}" class="underline">Review</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
