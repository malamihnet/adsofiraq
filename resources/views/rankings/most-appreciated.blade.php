@extends('layouts.app')

@section('title', 'Most Appreciated Campaigns — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <p class="text-sm"><a href="{{ route('rankings.index') }}" class="underline">Rankings</a></p>
    <h1 class="section-title mt-4 mb-12">Most Appreciated</h1>
    <x-campaign-grid :campaigns="$campaigns" />
</div>
@endsection
