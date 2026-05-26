@extends('layouts.app')

@section('title', $agency->name . ' — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <h1 class="section-title mb-12 inline-flex items-center gap-3">
        {{ $agency->name }}
        <x-verified-badge :verified="$agency->is_verified" />
    </h1>
    <x-campaign-grid :campaigns="$campaigns" />
</div>
@endsection
