@extends('layouts.app')

@section('title', 'Brands — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <h1 class="section-title mb-12">Brands</h1>
    <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
        @foreach($brands as $brand)
            <a href="{{ route('brands.show', $brand) }}" class="border border-archive-border p-6 hover:border-archive-black">
                <p class="font-display text-lg">{{ $brand->name }}</p>
                <p class="mt-2 text-xs text-archive-gray">{{ $brand->campaigns_count }} campaigns</p>
            </a>
        @endforeach
    </div>
    <div class="mt-12">{{ $brands->links() }}</div>
</div>
@endsection
