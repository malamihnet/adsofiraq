@props(['campaigns'])

@if($campaigns->isEmpty())
    <section class="border-b border-archive-border px-4 py-24 md:px-8 md:py-32">
        <div class="mx-auto max-w-4xl text-center">
            <p class="section-label mb-6">Est. 2024</p>
            <h1 class="font-display text-4xl leading-tight tracking-tight md:text-6xl lg:text-7xl">
                The Archive of Iraqi Advertising
            </h1>
            <p class="mx-auto mt-8 max-w-2xl text-lg leading-relaxed text-archive-gray">
                Discover campaigns, films, visuals, and creative work from Iraq's advertising industry.
            </p>
            <div class="mt-10">
                <a href="{{ route('campaigns.index') }}" class="btn-primary">Explore Campaigns</a>
            </div>
            <p class="mx-auto mt-8 max-w-xl text-sm text-archive-gray">
                No hero campaigns selected yet. Check back soon for featured work from the archive.
            </p>
        </div>
    </section>
@else
    @php
        $slides = $campaigns->map(fn ($campaign) => [
            'url' => route('campaigns.show', $campaign),
            'thumbnail' => $campaign->thumbnail_url,
            'brand' => $campaign->brands->first()?->name ?? 'Ads of Iraq',
            'title' => $campaign->title,
            'agency' => $campaign->agencies->first()?->name,
            'medium' => $campaign->mediumTypes->first()?->name,
        ])->values();
    @endphp

    <section
        class="relative z-0 border-b border-archive-border bg-archive-black"
        x-data="homeHeroSlider({ slides: @js($slides) })"
        x-init="init()"
        @mouseenter="pause()"
        @mouseleave="resume()"
        @keydown.window.arrow-right.prevent="next()"
        @keydown.window.arrow-left.prevent="prev()"
        role="region"
        aria-roledescription="carousel"
        aria-label="Featured campaigns"
    >
        <div class="relative h-[420px] overflow-hidden md:h-[520px] lg:h-[560px]">
            <div
                class="flex h-full transition-transform duration-700 ease-out"
                :style="trackStyle()"
                @touchstart.passive="touchStart($event)"
                @touchend.passive="touchEnd($event)"
            >
                <template x-for="(slide, index) in slides" :key="index">
                    <a
                        :href="slide.url"
                        class="group relative flex h-full shrink-0 flex-col justify-end overflow-hidden border-r border-white/10 last:border-r-0"
                        :style="slideStyle()"
                        :aria-label="slide.title"
                    >
                        <img
                            :src="slide.thumbnail"
                            :alt="slide.title"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-105"
                            loading="lazy"
                            decoding="async"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/50 to-black/20"></div>
                        <div class="relative z-10 p-6 md:p-8 lg:p-10">
                            <p class="text-xs font-medium uppercase tracking-widest text-white/70" x-text="slide.brand"></p>
                            <h2 class="mt-2 font-display text-2xl leading-tight text-white md:text-3xl lg:text-4xl" x-text="slide.title"></h2>
                            <p class="mt-2 text-sm text-white/80" x-show="slide.agency" x-text="slide.agency"></p>
                            <p class="mt-1 text-xs uppercase tracking-wider text-white/50" x-show="slide.medium" x-text="slide.medium"></p>
                        </div>
                    </a>
                </template>
            </div>

            <template x-if="canNavigate()">
                <button
                    type="button"
                    @click="prev()"
                    class="absolute left-3 top-1/2 z-20 -translate-y-1/2 border border-white/30 bg-black/40 p-2 text-white backdrop-blur-sm transition hover:bg-black/70 md:left-6"
                    aria-label="Previous slide"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            </template>

            <template x-if="canNavigate()">
                <button
                    type="button"
                    @click="next()"
                    class="absolute right-3 top-1/2 z-20 -translate-y-1/2 border border-white/30 bg-black/40 p-2 text-white backdrop-blur-sm transition hover:bg-black/70 md:right-6"
                    aria-label="Next slide"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </template>
        </div>

        <div class="border-t border-white/10 bg-archive-black px-4 py-4 md:px-8">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2">
                    <template x-for="(slide, index) in slides" :key="'dot-'+index">
                        <button
                            type="button"
                            @click="goTo(index)"
                            class="h-2 w-2 rounded-full transition"
                            :class="dotIndex() === index ? 'bg-white' : 'bg-white/30 hover:bg-white/50'"
                            :aria-label="'Go to slide ' + (index + 1)"
                        ></button>
                    </template>
                </div>
                <div class="h-px flex-1 overflow-hidden bg-white/20 sm:mx-6">
                    <div class="h-full bg-white transition-[width] duration-100 ease-linear" :style="`width: ${progress}%`"></div>
                </div>
                <p class="text-xs uppercase tracking-widest text-white/50">
                    <span x-text="dotIndex() + 1"></span> / <span x-text="slides.length"></span>
                </p>
            </div>
        </div>
    </section>
@endif
