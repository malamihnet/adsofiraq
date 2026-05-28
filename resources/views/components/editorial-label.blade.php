@props(['label'])

@if($label)
    <span {{ $attributes->merge(['class' => 'inline-block border border-archive-black px-2 py-0.5 text-[10px] font-semibold uppercase tracking-widest']) }}>
        {{ $label }}
    </span>
@endif
