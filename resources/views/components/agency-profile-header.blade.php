@props([
    'agency',
    'stats',
])

@php
    $about = filled($agency->bio)
        ? $agency->bio
        : 'This company has not added a profile description yet.';

    $socialLinks = array_filter([
        'Website' => $agency->website_url,
        'Instagram' => $agency->instagram_url,
        'LinkedIn' => $agency->linkedin_url,
        'X' => $agency->twitter_url,
    ]);
@endphp

<header class="mb-10 border-b border-archive-border/50 pb-10 md:mb-14 md:pb-12">
    <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:gap-8">
        <div class="h-20 w-20 shrink-0 overflow-hidden rounded-full border border-archive-border/80 bg-white shadow-sm sm:h-24 sm:w-24">
            @if($agency->logo_url)
                <img
                    src="{{ $agency->logo_url }}"
                    alt="{{ $agency->name }}"
                    class="h-full w-full object-contain p-2"
                    decoding="async"
                >
            @else
                <div class="flex h-full w-full items-center justify-center bg-archive-light font-display text-2xl text-archive-gray">
                    {{ mb_substr($agency->name, 0, 1) }}
                </div>
            @endif
        </div>

        <div class="min-w-0 flex-1 space-y-5">
            <div>
                <h1 class="font-display text-2xl leading-tight tracking-tight text-archive-black sm:text-3xl">
                    <span class="inline-flex flex-wrap items-center gap-2.5">
                        {{ $agency->name }}
                        <x-verified-badge :verified="$agency->is_verified" />
                    </span>
                </h1>
                @if($agency->roleLabels() !== [])
                    <div class="mt-3">
                        <x-agency-role-badges :roles="$agency->roleLabels()" />
                    </div>
                @endif
            </div>

            <div>
                <h2 class="text-[10px] font-medium uppercase tracking-[0.2em] text-archive-gray">About</h2>
                <p @class([
                    'mt-2 max-w-2xl text-sm leading-relaxed sm:text-[15px]',
                    filled($agency->bio) ? 'text-archive-black/85' : 'text-archive-gray italic',
                ])>{{ $about }}</p>
            </div>

            @if($socialLinks !== [])
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm">
                    @foreach($socialLinks as $label => $url)
                        <a
                            href="{{ $url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-archive-gray underline decoration-archive-border underline-offset-4 transition-colors hover:text-archive-black"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            @endif

            <dl class="flex flex-wrap gap-x-10 gap-y-4 border-t border-archive-border/40 pt-5">
                <div>
                    <dt class="text-[10px] font-medium uppercase tracking-[0.18em] text-archive-gray">Campaigns</dt>
                    <dd class="mt-1 font-display text-xl text-archive-black">{{ number_format($stats['campaigns']) }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-medium uppercase tracking-[0.18em] text-archive-gray">Views</dt>
                    <dd class="mt-1 font-display text-xl text-archive-black">{{ number_format($stats['views']) }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-medium uppercase tracking-[0.18em] text-archive-gray">Saves</dt>
                    <dd class="mt-1 font-display text-xl text-archive-black">{{ number_format($stats['bookmarks']) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</header>
