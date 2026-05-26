@props(['campaign'])

@if($campaign?->assets?->count())
    <div class="mt-3">
        <p class="text-xs text-archive-gray mb-2">Current stills ({{ $campaign->assets->count() }})</p>
        <div class="flex flex-wrap gap-2">
            @foreach($campaign->assets as $asset)
                <a href="{{ $asset->url }}" target="_blank" rel="noopener"
                   class="block h-16 w-24 overflow-hidden border border-archive-border">
                    <img src="{{ $asset->url }}" alt="" class="h-full w-full object-cover">
                </a>
            @endforeach
        </div>
    </div>
@endif
