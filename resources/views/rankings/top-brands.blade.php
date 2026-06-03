@extends('layouts.app')

@section('title', $seo['title'])
@section('meta_description', $seo['description'])

@section('content')
<div class="mx-auto max-w-5xl px-5 py-10 sm:px-8 sm:py-14">
    <nav aria-label="Breadcrumb" class="mb-8 text-xs text-archive-gray">
        <a href="{{ route('rankings.index') }}" class="underline decoration-neutral-300 underline-offset-4 hover:text-archive-black">Rankings</a>
    </nav>

    <header class="mb-10 sm:mb-12">
        <h1 class="font-display text-3xl tracking-tight text-archive-black sm:text-4xl">Top Brands in Iraq</h1>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-archive-gray">
            Iraqi brands ranked by approved campaigns, audience engagement, and platform recognition.
        </p>
    </header>

    <ol class="space-y-4">
        @foreach($brands as $index => $brand)
            <li class="overflow-hidden rounded-2xl border border-neutral-200/80 bg-white p-5">
                <div class="flex items-center gap-4">
                    <span class="w-8 shrink-0 text-center font-display text-2xl tabular-nums text-neutral-300">{{ $index + 1 }}</span>
                    <a href="{{ route('brand.show', $brand) }}" class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border border-neutral-200">
                        <img src="{{ $brand->logo_url }}" alt="" class="h-full w-full object-contain" loading="lazy">
                    </a>
                    <div>
                        <a href="{{ route('brand.show', $brand) }}" class="font-display text-xl hover:underline">{{ $brand->name }}</a>
                        <p class="mt-1 text-xs text-archive-gray">
                            {{ number_format($brand->ranking_campaign_count ?? $brand->campaigns_count ?? 0) }} campaigns |
                            {{ number_format($brand->ranking_total_views ?? 0) }} views
                        </p>
                    </div>
                </div>
            </li>
        @endforeach
    </ol>
</div>
@endsection
