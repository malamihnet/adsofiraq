@props(['assets'])

@if($assets->count())
    <div x-data="{ active: 0 }" class="space-y-4">
        <div class="aspect-[16/10] overflow-hidden border border-archive-border bg-archive-light">
            @foreach($assets as $index => $asset)
                <a href="{{ $asset->url }}" target="_blank" rel="noopener"
                   x-show="active === {{ $index }}"
                   class="block h-full w-full"
                   title="Open full size in new tab">
                    <x-campaign-image src="{{ $asset->url }}" alt="Campaign still {{ $index + 1 }}"
                         class="h-full w-full object-contain" {{ $index === 0 ? '' : 'x-cloak' }} />
                </a>
            @endforeach
        </div>
        @if($assets->count() > 1)
            <div class="flex gap-2 overflow-x-auto pb-2">
                @foreach($assets as $index => $asset)
                    <button type="button" @click="active = {{ $index }}"
                            :class="active === {{ $index }} ? 'border-archive-black' : 'border-archive-border'"
                            class="h-16 w-24 flex-shrink-0 overflow-hidden border">
                        <x-campaign-image src="{{ $asset->url }}" alt="" class="h-full w-full object-cover" />
                    </button>
                @endforeach
            </div>
            <p class="text-xs text-archive-gray">Click the main image to open full size in a new tab.</p>
        @else
            <p class="text-xs text-archive-gray">
                <a href="{{ $assets->first()->url }}" target="_blank" rel="noopener" class="underline">Open full size</a>
            </p>
        @endif
    </div>
@endif
