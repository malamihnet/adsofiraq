@props([
    'name',
    'verified' => false,
    'roleBadges' => [],
    'subtitle' => null,
    'bio' => null,
    'logoUrl' => null,
    'coverUrl' => null,
    'websiteUrl' => null,
    'socials' => [],
    'stats' => [],
])

<div class="relative mb-12 overflow-hidden border border-archive-border bg-archive-cream">
    @if($coverUrl)
        <div class="h-40 md:h-52 w-full bg-cover bg-center" style="background-image: url('{{ $coverUrl }}')"></div>
    @else
        <div class="h-32 md:h-40 w-full bg-gradient-to-r from-archive-black to-archive-gray"></div>
    @endif

    <div class="relative px-6 pb-8 pt-0 md:px-10">
        <div class="-mt-12 mb-6 flex flex-col gap-6 md:flex-row md:items-end">
            <div class="h-24 w-24 shrink-0 overflow-hidden border-4 border-white bg-white shadow md:h-28 md:w-28">
                <img src="{{ $logoUrl ?: \App\Support\Placeholder::url('square') }}" alt="{{ $name }}" class="h-full w-full object-contain p-2">
            </div>
            <div class="flex-1">
                <h1 class="font-display text-3xl md:text-4xl inline-flex items-center gap-3 flex-wrap">
                    {{ $name }}
                    <x-verified-badge :verified="$verified" />
                    <x-agency-role-badges :roles="$roleBadges" />
                </h1>
                @if($subtitle)
                    <p class="mt-2 text-sm uppercase tracking-widest text-archive-gray">{{ $subtitle }}</p>
                @endif
            </div>
        </div>

        @if($bio)
            <p class="max-w-3xl leading-relaxed text-archive-black/90">{{ $bio }}</p>
        @endif

        <div class="mt-6 flex flex-wrap items-center gap-4 text-sm">
            @if($websiteUrl)
                <a href="{{ $websiteUrl }}" target="_blank" rel="noopener" class="underline">Website</a>
            @endif
            @foreach($socials as $label => $url)
                @if($url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" class="underline">{{ $label }}</a>
                @endif
            @endforeach
        </div>

        @if(!empty($stats))
            <dl class="mt-8 grid grid-cols-2 gap-4 border-t border-archive-border pt-6 sm:grid-cols-4">
                @foreach($stats as $label => $value)
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-archive-gray">{{ $label }}</dt>
                        <dd class="mt-1 font-display text-2xl">{{ is_numeric($value) ? number_format($value) : $value }}</dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</div>
