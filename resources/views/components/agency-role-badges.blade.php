@props(['roles' => []])

@if(!empty($roles))
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
        @foreach($roles as $role)
            <span class="inline-flex items-center rounded border border-archive-border bg-archive-light px-2 py-0.5 text-[10px] font-medium uppercase tracking-wider text-archive-gray">
                {{ $role }}
            </span>
        @endforeach
    </div>
@endif
