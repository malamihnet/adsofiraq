@props(['heading'])

<section {{ $attributes->merge(['class' => 'border-t border-archive-border py-8 first:border-t-0 first:pt-0']) }}>
    <h2 class="section-label mb-4">{{ $heading }}</h2>
    <div class="space-y-3 text-sm leading-relaxed text-archive-black">
        {{ $slot }}
    </div>
</section>
