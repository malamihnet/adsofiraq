@props([
    'stills',
    'title' => 'Campaign',
])

@php
    $placeholder = asset(config('upload.placeholder', 'images/placeholder.webp'));
    if (! file_exists(public_path(config('upload.placeholder', 'images/placeholder.webp')))) {
        $placeholder = asset(config('upload.placeholder_fallback', 'images/placeholder.jpg'));
    }

    $payload = $stills->values()->map(function ($asset, int $index) use ($title) {
        return [
            'id' => $asset->id,
            'url' => $asset->resolvedUrl() ?? $asset->url,
            'alt' => $title.' — still '.($index + 1),
        ];
    });
@endphp

@if($payload->isNotEmpty())
    <div
        class="campaign-gallery space-y-4"
        x-data="campaignGallery(@js($payload), @js($placeholder))"
        x-init="init()"
    >
        <button
            type="button"
            class="group relative block aspect-[16/10] w-full overflow-hidden border border-archive-border bg-archive-light text-left"
            @click="openLightbox()"
            :aria-label="current ? 'Open image viewer: ' + current.alt : 'Open image viewer'"
        >
            <template x-if="current">
                <img
                    :src="current.url"
                    :alt="current.alt"
                    class="h-full w-full object-contain transition-transform duration-300 group-hover:scale-[1.01]"
                    @error="onImageError($event)"
                >
            </template>
            <div
                x-show="!current"
                class="flex h-full items-center justify-center text-archive-gray"
            >
                <span class="text-xs uppercase tracking-widest">No preview</span>
            </div>
            <span class="pointer-events-none absolute bottom-3 right-3 border border-white/30 bg-black/70 px-2 py-1 text-[10px] uppercase tracking-widest text-white opacity-0 transition group-hover:opacity-100">
                View gallery
            </span>
        </button>

        <template x-if="hasMultiple">
            <div class="flex gap-3 overflow-x-auto pb-1">
                <template x-for="(still, index) in stills" :key="still.id">
                    <button
                        type="button"
                        class="h-16 w-24 flex-shrink-0 overflow-hidden border transition-colors"
                        :class="active === index ? 'border-archive-black ring-1 ring-archive-black' : 'border-archive-border hover:border-archive-black'"
                        @click="select(index)"
                        :aria-label="'Show still ' + (index + 1)"
                        :aria-current="active === index ? 'true' : 'false'"
                    >
                        <img
                            :src="still.url"
                            alt=""
                            class="h-full w-full object-cover"
                            @error="onImageError($event)"
                        >
                    </button>
                </template>
            </div>
        </template>

        <p class="text-xs text-archive-gray" x-show="hasMultiple">
            Click a thumbnail to preview, or the main image to open the gallery viewer.
        </p>

        <template x-teleport="body">
            <div
                x-show="lightboxOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="campaign-gallery-lightbox"
                role="dialog"
                aria-modal="true"
                :aria-label="current ? current.alt : 'Campaign gallery'"
                @click.self="closeLightbox()"
                @keydown.escape.window="closeLightbox()"
                @keydown.arrow-right.window.prevent="next()"
                @keydown.arrow-left.window.prevent="prev()"
            >
                <button
                    type="button"
                    class="campaign-gallery-lightbox__close"
                    @click="closeLightbox()"
                    aria-label="Close gallery"
                >
                    <span aria-hidden="true">&times;</span>
                </button>

                <template x-if="hasMultiple">
                    <button
                        type="button"
                        class="campaign-gallery-lightbox__nav campaign-gallery-lightbox__nav--prev"
                        @click="prev()"
                        aria-label="Previous image"
                    >
                        <span aria-hidden="true">&#8592;</span>
                    </button>
                </template>

                <div class="campaign-gallery-lightbox__stage">
                    <template x-if="current">
                        <img
                            :src="current.url"
                            :alt="current.alt"
                            class="campaign-gallery-lightbox__image"
                            @error="onImageError($event)"
                        >
                    </template>
                </div>

                <template x-if="hasMultiple">
                    <button
                        type="button"
                        class="campaign-gallery-lightbox__nav campaign-gallery-lightbox__nav--next"
                        @click="next()"
                        aria-label="Next image"
                    >
                        <span aria-hidden="true">&#8594;</span>
                    </button>
                </template>

                <p
                    x-show="hasMultiple"
                    class="campaign-gallery-lightbox__counter"
                    x-text="(active + 1) + ' / ' + stills.length"
                ></p>
            </div>
        </template>
    </div>
@endif
