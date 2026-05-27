@props([
    'stills',
    'title' => 'Campaign',
])

@php
    $placeholder = asset(config('upload.placeholder', 'images/placeholder.webp'));
    if (! file_exists(public_path(config('upload.placeholder', 'images/placeholder.webp')))) {
        $placeholder = asset(config('upload.placeholder_fallback', 'images/placeholder.jpg'));
    }

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
            'url' => $url,
            'hash' => $hash,
            'alt' => $title.' — still '.($payload->count() + 1),
        ]);
    }

    $first = $payload->first();
@endphp

@if($payload->isNotEmpty())
    <div
        class="campaign-gallery space-y-4"
        x-data="campaignGallery(@js($payload->values()->all()), @js($placeholder))"
        x-init="init()"
    >
        <button
            type="button"
            class="group relative block aspect-[16/10] w-full overflow-hidden border border-archive-border bg-archive-light text-left"
            @click.prevent="openLightbox()"
            aria-label="Open image gallery"
        >
            <img
                src="{{ $first['url'] }}"
                alt="{{ $first['alt'] }}"
                class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-[1.01]"
                :src="previewUrl()"
                :alt="previewAlt()"
                x-on:error="onImageError($event)"
            >
            <span class="pointer-events-none absolute bottom-3 right-3 border border-white/30 bg-black/70 px-2 py-1 text-[10px] uppercase tracking-widest text-white opacity-0 transition group-hover:opacity-100">
                View gallery
            </span>
        </button>

        @if($payload->count() > 1)
            <div class="flex gap-3 overflow-x-auto pb-1">
                @foreach($payload as $index => $still)
                    <button
                        type="button"
                        class="h-16 w-24 flex-shrink-0 overflow-hidden border border-archive-border transition-colors hover:border-archive-black"
                        :class="active === {{ $index }} ? '!border-archive-black ring-1 ring-archive-black' : ''"
                        @click.prevent="select({{ $index }})"
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

        <div
            x-show="lightboxOpen"
            x-cloak
            x-transition.opacity
            class="campaign-gallery-lightbox"
            role="dialog"
            aria-modal="true"
            aria-label="Campaign image gallery"
            @click.self="closeLightbox()"
            @keydown.escape.window="closeLightbox()"
            @keydown.arrow-right.window.prevent="lightboxOpen && next()"
            @keydown.arrow-left.window.prevent="lightboxOpen && prev()"
            style="display: none;"
        >
            <button
                type="button"
                class="campaign-gallery-lightbox__close"
                @click.prevent="closeLightbox()"
                aria-label="Close gallery"
            >
                <span aria-hidden="true">&times;</span>
            </button>

            <button
                type="button"
                x-show="stills.length > 1"
                class="campaign-gallery-lightbox__nav campaign-gallery-lightbox__nav--prev"
                @click.prevent="prev()"
                aria-label="Previous image"
            >
                <span aria-hidden="true">&#8592;</span>
            </button>

            <div class="campaign-gallery-lightbox__stage">
                <img
                    :src="previewUrl()"
                    :alt="previewAlt()"
                    class="campaign-gallery-lightbox__image"
                    x-on:error="onImageError($event)"
                >
            </div>

            <button
                type="button"
                x-show="stills.length > 1"
                class="campaign-gallery-lightbox__nav campaign-gallery-lightbox__nav--next"
                @click.prevent="next()"
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
