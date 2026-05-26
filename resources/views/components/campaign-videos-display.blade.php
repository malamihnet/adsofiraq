@props(['campaign'])

@php
    $videos = $campaign->resolvedVideos()
        ->filter(fn ($video) => $video->isPlayable())
        ->values();
@endphp

@if($videos->isNotEmpty())
    <div class="flex flex-col gap-8 md:gap-10">
        @foreach($videos as $video)
            <x-campaign-video-player :video="$video" :campaign="$campaign" :campaign-title="$campaign->title" />
        @endforeach
    </div>
@endif
