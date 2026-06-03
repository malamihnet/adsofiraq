@props(['items' => []])

@if(count($items) > 0)
    <nav aria-label="Breadcrumb" class="mb-6 text-xs uppercase tracking-widest text-archive-gray">
        <ol class="flex flex-wrap items-center gap-2">
            @foreach($items as $index => $item)
                <li class="inline-flex items-center gap-2">
                    @if($index > 0)
                        <span aria-hidden="true" class="text-archive-gray/60">/</span>
                    @endif
                    @if(! empty($item['url']) && $index < count($items) - 1)
                        <a href="{{ $item['url'] }}" class="hover:text-archive-black hover:underline">{{ $item['name'] }}</a>
                    @else
                        <span @class(['text-archive-black' => $index === count($items) - 1])>{{ $item['name'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
