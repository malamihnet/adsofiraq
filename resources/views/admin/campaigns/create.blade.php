@extends('layouts.admin')

@section('title', 'Add Campaign — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.campaigns.index') }}" class="text-sm underline">&larr; All campaigns</a>
</div>

<h1 class="section-title mb-8">Add Campaign</h1>

<x-admin.campaign-form
    :industries="$industries"
    :medium-types="$mediumTypes"
    :countries="$countries"
    :brands="$brands"
    :agencies="$agencies"
    :production-houses="$productionHouses ?? $agencies"
    :users="$users"
    :selected-taxonomies="$selectedTaxonomies"
    :selected-people-credits="$selectedPeopleCredits ?? []"
    :default-user-id="auth()->id()"
/>
@endsection
