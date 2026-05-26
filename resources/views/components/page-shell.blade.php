@props([
    'label' => null,
    'title',
    'intro',
])

<div class="mx-auto max-w-3xl px-4 py-16 md:px-8 md:py-24">
    @if($label)
        <p class="section-label mb-2">{{ $label }}</p>
    @endif
    <h1 class="font-display text-4xl leading-tight md:text-5xl">{{ $title }}</h1>
    <p class="mt-6 max-w-2xl text-base leading-relaxed text-archive-gray">{{ $intro }}</p>
    <div class="mt-12">
        {{ $slot }}
    </div>
</div>
