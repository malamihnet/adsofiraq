@php
    $links = [
        ['label' => 'Instagram', 'url' => 'https://www.instagram.com/adsofiraq'],
        ['label' => 'Facebook', 'url' => 'https://www.facebook.com/Adofiraq'],
        ['label' => 'TikTok', 'url' => 'https://www.tiktok.com/@adsofiraq'],
    ];
@endphp

<nav {{ $attributes->merge(['class' => 'flex items-center gap-2 sm:gap-3']) }} aria-label="Social media">
    @foreach($links as $link)
        <a
            href="{{ $link['url'] }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center text-white/70 transition-colors hover:bg-white/10 hover:text-white"
            aria-label="{{ $link['label'] }}"
        >
            @if($link['label'] === 'Instagram')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <rect x="3" y="3" width="18" height="18" rx="5" ry="5"/>
                    <circle cx="12" cy="12" r="3.5"/>
                    <circle cx="17.2" cy="6.8" r="0.75" fill="currentColor" stroke="none"/>
                </svg>
            @elseif($link['label'] === 'Facebook')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/>
                </svg>
            @elseif($link['label'] === 'TikTok')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z"/>
                </svg>
            @endif
        </a>
    @endforeach
</nav>
