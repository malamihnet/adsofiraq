@props(['user'])

@php
    $links = array_filter([
        'Website' => $user->website,
        'Instagram' => $user->instagram_url,
        'TikTok' => $user->tiktok_url,
        'Facebook' => $user->facebook_url,
        'LinkedIn' => $user->linkedin_url,
    ]);
@endphp

@if($links)
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-x-4 gap-y-2 text-sm']) }}>
        @foreach($links as $label => $url)
            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="underline hover:text-archive-black">
                {{ $label }}
            </a>
        @endforeach
    </div>
@endif
