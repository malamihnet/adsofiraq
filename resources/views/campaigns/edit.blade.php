@extends('layouts.app')

@section('title', 'Edit Campaign — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12 md:px-8">
    <p class="section-label mb-2">Edit</p>
    <h1 class="section-title mb-12">Edit Campaign</h1>
    <x-submit-campaign-form
        :campaign="$campaign"
        :industries="$industries"
        :medium-types="$mediumTypes"
        :countries="$countries"
        :brands="$brands"
        :agencies="$agencies"
        :selected-taxonomies="$selectedTaxonomies"
    />
</div>
@endsection
