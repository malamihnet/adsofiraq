@extends('layouts.admin')

@section('title', 'Edit Campaign — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="text-sm underline">&larr; Back to campaign</a>
</div>

@if(session('success'))
    <div class="mb-6 border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
@endif

<h1 class="section-title mb-2">Edit Campaign</h1>
<p class="mb-8 text-sm text-archive-gray">{{ $campaign->title }}</p>

<x-admin.campaign-form
    :campaign="$campaign"
    :industries="$industries"
    :medium-types="$mediumTypes"
    :countries="$countries"
    :brands="$brands"
    :agencies="$agencies"
    :production-houses="$productionHouses ?? $agencies"
    :users="$users"
    :selected-taxonomies="$selectedTaxonomies"
    :selected-people-credits="$selectedPeopleCredits ?? []"
    :default-user-id="$campaign->user_id"
/>
@endsection
