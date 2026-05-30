@props(['roles' => []])

@if(!empty($roles))
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
        @foreach($roles as $role)
            <span class="inline-flex items-center rounded-full border border-archive-border/70 bg-archive-cream/60 px-2.5 py-0.5 text-[10px] font-medium uppercase tracking-[0.14em] text-archive-gray">
                {{ $role }}
            </span>
        @endforeach
    </div>
@endif
