@props([
    'stills',
    'title' => 'Campaign',
])

@php
    $placeholder = \App\Support\Placeholder::url('landscape');

    $seenPaths = [];
    $seenUrls = [];
    $seenHashes = [];
    $seenSources = [];
    $payload = collect();

    foreach ($stills as $asset) {
        $url = $asset->resolvedUrl() ?? $asset->url;
        $pathKey = $asset->galleryPathKey() ?? $url;
        $hash = $asset->content_hash ?? $asset->effectiveContentHash();
        $sourceKey = $asset->source_url_key;

        if ($pathKey === null || isset($seenPaths[$pathKey]) || isset($seenUrls[$url])) {
            continue;
        }

        if ($hash !== null && isset($seenHashes[$hash])) {
            continue;
        }

        if ($sourceKey !== null && isset($seenSources[$sourceKey])) {
            continue;
        }

        $seenPaths[$pathKey] = true;
        $seenUrls[$url] = true;

        if ($hash !== null) {
            $seenHashes[$hash] = true;
        }

        if ($sourceKey !== null) {
            $seenSources[$sourceKey] = true;
        }

        $payload->push([
            'id' => $asset->id,
            'url' => $asset->display_url,
            'hash' => $hash,
            'alt' => $asset->effectiveAlt($title, $payload->count() + 1),
        ]);
    }

    $first = $payload->first();
    $galleryStills = $payload->values()->all();
    $galleryPlaceholder = $placeholder;
@endphp

@if($payload->isNotEmpty())
    <div
        class="campaign-gallery space-y-4"
        x-data="campaignGallery({{ Js::from($galleryStills) }}, {{ Js::from($galleryPlaceholder) }})"
        x-init="init()"
    >
        {{-- Main preview: full still at original aspect ratio (thumbnails in strip may crop) --}}
        <button
            type="button"
            class="campaign-gallery__preview group relative text-left"
            x-on:click.prevent="openLightbox()"
            aria-label="Open image gallery"
        >
            <img
                src="{{ $first['url'] }}"
                alt="{{ $first['alt'] }}"
                class="campaign-gallery__preview-image"
                x-bind:src="previewUrl()"
                x-bind:alt="previewAlt()"
                x-on:error="onImageError($event)"
            >
            <span class="pointer-events-none absolute bottom-3 right-3 border border-white/30 bg-black/70 px-2 py-1 text-[10px] uppercase tracking-widest text-white opacity-0 transition group-hover:opacity-100">
                View gallery
            </span>
        </button>

        @if($payload->count() > 1)
            <div class="flex gap-3 overflow-x-auto pb-1">
                @foreach($galleryStills as $index => $still)
                    <button
                        type="button"
                        class="h-16 w-24 flex-shrink-0 overflow-hidden border border-archive-border transition-colors hover:border-archive-black"
                        x-bind:class="active === {{ $index }} ? '!border-archive-black ring-1 ring-archive-black' : ''"
                        x-on:click.prevent="select({{ $index }})"
                        aria-label="Show still {{ $index + 1 }}"
                    >
                        <img
                            src="{{ $still['url'] }}"
                            alt=""
                            class="h-full w-full object-cover"
                            loading="lazy"
                            x-on:error="onImageError($event)"
                        >
                    </button>
                @endforeach
            </div>
            <p class="text-xs text-archive-gray">
                Click a thumbnail to preview, or the main image to open the gallery viewer.
            </p>
        @endif

        {{-- Lightbox (Alpine only; no Blade @ directives in attributes) --}}
        <div
            x-show="lightboxOpen"
            x-cloak
            x-transition.opacity
            class="campaign-gallery-lightbox"
            role="dialog"
            aria-modal="true"
            aria-label="Campaign image gallery"
            x-on:click.self="closeLightbox()"
            x-on:keydown.escape.window="closeLightbox()"
            x-on:keydown.arrow-right.window.prevent="lightboxOpen && next()"
            x-on:keydown.arrow-left.window.prevent="lightboxOpen && prev()"
            style="display: none;"
        >
            <button
                type="button"
                class="campaign-gallery-lightbox__close"
                x-on:click.prevent="closeLightbox()"
                aria-label="Close gallery"
            >
                <span aria-hidden="true">&times;</span>
            </button>

            <button
                type="button"
                x-show="stills.length > 1"
                class="campaign-gallery-lightbox__nav campaign-gallery-lightbox__nav--prev"
                x-on:click.prevent="prev()"
                aria-label="Previous image"
            >
                <span aria-hidden="true">&#8592;</span>
            </button>

            <div class="campaign-gallery-lightbox__stage">
                <img
                    x-bind:src="previewUrl()"
                    x-bind:alt="previewAlt()"
                    class="campaign-gallery-lightbox__image"
                    x-on:error="onImageError($event)"
                >
            </div>

            <button
                type="button"
                x-show="stills.length > 1"
                class="campaign-gallery-lightbox__nav campaign-gallery-lightbox__nav--next"
                x-on:click.prevent="next()"
                aria-label="Next image"
            >
                <span aria-hidden="true">&#8594;</span>
            </button>

            <p
                x-show="stills.length > 1"
                class="campaign-gallery-lightbox__counter"
                x-text="(active + 1) + ' / ' + stills.length"
            ></p>
        </div>
    </div>
@endif
