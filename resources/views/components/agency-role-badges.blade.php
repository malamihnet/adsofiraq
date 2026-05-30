@props(['roles' => []])

@if(!empty($roles))
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
        @foreach($roles as $role)
            <span class="inline-flex items-center rounded-full border border-neutral-200/90 bg-neutral-50 px-3 py-1 text-[10px] font-medium uppercase tracking-[0.14em] text-neutral-500">
                {{ $role }}
            </span>
        @endforeach
    </div>
@endif
