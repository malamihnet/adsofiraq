@extends('layouts.app')

@section('title', 'Submit Campaign — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12 md:px-8">
    <p class="section-label mb-2">Contribute</p>
    <h1 class="section-title mb-12">Submit Campaign</h1>
    <x-submit-campaign-form
        :industries="$industries"
        :medium-types="$mediumTypes"
        :countries="$countries"
        :brands="$brands"
        :agencies="$agencies"
        :production-houses="$productionHouses ?? $agencies"
        :selected-taxonomies="$selectedTaxonomies"
        :selected-people-credits="$selectedPeopleCredits ?? []"
    />
</div>
@endsection
