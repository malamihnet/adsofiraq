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
        'Facebook' => $agency->facebook_url,
    ]);
@endphp

<header class="agency-profile-header">
    @if($agency->cover_url)
        <div class="mb-8 overflow-hidden rounded-2xl border border-neutral-200/80 bg-neutral-50 shadow-sm">
            <img
                src="{{ $agency->cover_url }}"
                alt=""
                class="h-28 w-full object-cover sm:h-32"
                decoding="async"
            >
        </div>
    @endif

    <div class="flex flex-col gap-8 sm:gap-10 lg:flex-row lg:items-start lg:gap-12">
        {{-- Avatar --}}
        <div class="flex shrink-0 justify-center lg:justify-start">
            <div class="h-[88px] w-[88px] overflow-hidden rounded-full border border-neutral-200/90 bg-white shadow-[0_1px_3px_rgba(0,0,0,0.06)] ring-4 ring-white sm:h-24 sm:w-24">
                @if($agency->logo_url)
                    <img
                        src="{{ $agency->logo_url }}"
                        alt="{{ $agency->name }}"
                        class="h-full w-full object-contain p-2.5"
                        decoding="async"
                    >
                @else
                    <div class="flex h-full w-full items-center justify-center bg-neutral-50 font-display text-2xl text-neutral-400">
                        {{ mb_substr($agency->name, 0, 1) }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Identity + content --}}
        <div class="min-w-0 flex-1 space-y-8 sm:space-y-9">
            <div class="space-y-4 text-center lg:text-left">
                <h1 class="font-display text-[1.75rem] font-normal leading-[1.15] tracking-tight text-archive-black sm:text-4xl">
                    <span class="inline-flex flex-wrap items-center justify-center gap-2.5 lg:justify-start">
                        {{ $agency->name }}
                        <x-verified-badge :verified="$agency->is_verified" />
                    </span>
                </h1>

                @if($agency->roleLabels() !== [])
                    <div class="flex justify-center lg:justify-start">
                        <x-agency-role-badges :roles="$agency->roleLabels()" class="gap-2" />
                    </div>
                @endif
            </div>

            <div class="mx-auto max-w-2xl space-y-3 lg:mx-0">
                <h2 class="text-center text-[10px] font-medium uppercase tracking-[0.24em] text-archive-gray lg:text-left">About</h2>
                <p @class([
                    'text-center text-[15px] leading-7 lg:text-left',
                    filled($agency->bio) ? 'text-neutral-600' : 'italic text-archive-gray',
                ])>{{ $about }}</p>
            </div>

            @if($socialLinks !== [])
                <div class="flex flex-wrap items-center justify-center gap-2 lg:justify-start">
                    @foreach($socialLinks as $label => $url)
                        <a
                            href="{{ $url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center rounded-full border border-neutral-200/90 bg-white px-4 py-2 text-xs font-medium text-neutral-600 shadow-sm transition-colors hover:border-neutral-300 hover:text-archive-black"
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Stats card --}}
            <div class="overflow-hidden rounded-2xl border border-neutral-200/80 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.04)]">
                <dl class="grid grid-cols-1 divide-y divide-neutral-100 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <div class="px-6 py-5 text-center sm:py-6">
                        <dt class="text-[10px] font-medium uppercase tracking-[0.2em] text-archive-gray">Campaigns</dt>
                        <dd class="mt-2 font-display text-2xl tabular-nums text-archive-black sm:text-[1.65rem]">{{ number_format($stats['campaigns']) }}</dd>
                    </div>
                    <div class="px-6 py-5 text-center sm:py-6">
                        <dt class="text-[10px] font-medium uppercase tracking-[0.2em] text-archive-gray">Views</dt>
                        <dd class="mt-2 font-display text-2xl tabular-nums text-archive-black sm:text-[1.65rem]">{{ number_format($stats['views']) }}</dd>
                    </div>
                    <div class="px-6 py-5 text-center sm:py-6">
                        <dt class="text-[10px] font-medium uppercase tracking-[0.2em] text-archive-gray">Saves</dt>
                        <dd class="mt-2 font-display text-2xl tabular-nums text-archive-black sm:text-[1.65rem]">{{ number_format($stats['bookmarks']) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</header>
