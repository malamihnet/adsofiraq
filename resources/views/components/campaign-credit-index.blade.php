@props(['campaign'])

@php
    $hasTaxonomyCredits = $campaign->agencies->isNotEmpty()
        || $campaign->brands->isNotEmpty()
        || $campaign->productionHouses->isNotEmpty();
@endphp

@if($hasTaxonomyCredits)
    <section class="mt-12 border-t border-archive-border pt-10">
        <h2 class="section-label mb-6">Campaign Credits</h2>
        <dl class="space-y-3 text-sm">
            @foreach($campaign->agencies as $agency)
                <div class="flex flex-wrap gap-x-2">
                    <dt class="text-archive-gray">Agency:</dt>
                    <dd><a href="{{ route('agency.show', $agency) }}" class="underline">{{ $agency->name }}</a></dd>
                </div>
            @endforeach

            @foreach($campaign->productionHouses as $house)
                <div class="flex flex-wrap gap-x-2">
                    <dt class="text-archive-gray">Production:</dt>
                    <dd><a href="{{ route('agency.show', $house) }}" class="underline">{{ $house->name }}</a></dd>
                </div>
            @endforeach

            @foreach($campaign->brands as $brand)
                <div class="flex flex-wrap gap-x-2">
                    <dt class="text-archive-gray">Brand:</dt>
                    <dd><a href="{{ route('brand.show', $brand) }}" class="underline">{{ $brand->name }}</a></dd>
                </div>
            @endforeach
        </dl>
    </section>
@endif

@if($campaign->tags->isNotEmpty())
    <section class="mt-8">
        <h3 class="section-label mb-3">Tags</h3>
        <div class="flex flex-wrap gap-2">
            @foreach($campaign->tags as $tag)
                <a href="{{ route('tags.show', $tag) }}" class="border border-archive-border px-3 py-1 text-xs hover:bg-archive-light">
                    {{ $tag->name }}
                </a>
            @endforeach
        </div>
    </section>
@endif
