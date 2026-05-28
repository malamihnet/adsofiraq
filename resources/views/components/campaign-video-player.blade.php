@props(['video', 'campaign', 'campaignTitle' => ''])

@php
    $embedId = $video->embed_id;
    $posterUrl = $video->poster_url ?? $campaign->thumbnail_url ?? asset('images/placeholder.jpg');
@endphp

<div class="campaign-plyr w-full">
    @if($video->type === 'file' && $video->file_url)
        <video
            class="js-plyr-player w-full"
            playsinline
            preload="metadata"
            data-poster="{{ $posterUrl }}"
        >
            <source src="{{ $video->file_url }}" @if($video->mime_type) type="{{ $video->mime_type }}" @endif>
        </video>
    @elseif($video->type === 'direct' && $video->url)
        <video
            class="js-plyr-player w-full"
            playsinline
            preload="metadata"
            data-poster="{{ $posterUrl }}"
        >
            <source src="{{ $video->url }}">
        </video>
    @elseif($video->type === 'youtube' && $embedId)
        <div
            class="js-plyr-player plyr__video-embed"
            data-plyr-provider="youtube"
            data-plyr-embed-id="{{ $embedId }}"
        ></div>
    @elseif($video->type === 'vimeo' && $embedId)
        <div
            class="js-plyr-player plyr__video-embed"
            data-plyr-provider="vimeo"
            data-plyr-embed-id="{{ $embedId }}"
        ></div>
    @endif
</div>
