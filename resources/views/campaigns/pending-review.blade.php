@extends('layouts.app')

@section('title', 'Pending Review — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-16 md:px-8">
    <p class="section-label mb-2 text-center">Submission received</p>
    <h1 class="section-title mb-6 text-center">Pending review</h1>

    <div class="border border-archive-border bg-white p-8 text-center">
        <p class="text-lg leading-relaxed text-archive-black">
            @if(session('pending_review_notice') === 'not_live_yet')
                This campaign is not public yet. It is still pending review — please wait until our team approves it.
            @elseif(session('pending_review_notice') === 'updated')
                Your campaign has been updated and is pending review. Please wait for our team to review your changes.
            @elseif(session('pending_review_notice') === 'submitted')
                Your campaign has been submitted successfully and is pending review.
            @elseif($campaign->status === 'approved' && $campaign->pendingRevision)
                Your campaign update has been submitted for review. The currently published version remains live until approval.
            @else
                Your campaign has been submitted successfully and is pending review.
            @endif
        </p>

        <p class="mt-4 font-display text-xl text-archive-black">{{ $campaign->title }}</p>

        <p class="mt-2 text-sm uppercase tracking-widest text-archive-gray">
            Status: {{ ucfirst($campaign->status) }}
        </p>

        <p class="mt-6 text-sm leading-relaxed text-archive-gray">
            Our team will review your submission before it appears in the public archive.
            You will be notified when it is approved.
        </p>

        <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:justify-center">
            <a href="{{ route('home') }}" class="btn-primary">Back to Home</a>
            <a href="{{ route('profile.campaigns') }}" class="btn-outline">My Campaigns</a>
            <a href="{{ route('campaigns.edit', $campaign) }}" class="btn-outline">Edit Campaign</a>
            <a href="{{ route('campaigns.create') }}" class="btn-outline">Submit Another Campaign</a>
            @if($campaign->status === 'approved')
                <a href="{{ route('campaigns.show', $campaign) }}" class="btn-outline">View Public Campaign</a>
            @endif
        </div>
    </div>
</div>
@endsection
