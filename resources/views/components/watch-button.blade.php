@props(['campaign', 'isWatched' => null, 'size' => 'md'])

@php
    $watching = $isWatched ?? ($campaign->is_watched ?? (auth()->check() && $campaign->isWatchedBy(auth()->user())));
    $sizeClasses = $size === 'sm'
        ? 'gap-1 px-2.5 py-1.5 text-[10px]'
        : 'gap-1.5 px-3 py-2 text-[11px]';
    $iconClasses = $size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4';
    $baseClasses = "inline-flex items-center uppercase tracking-[0.12em] font-medium border transition-colors duration-200 {$sizeClasses}";
@endphp

@guest
    <a href="{{ route('login') }}" {{ $attributes->merge(['class' => "{$baseClasses} border-archive-border text-archive-black hover:border-archive-black hover:bg-archive-black hover:text-white"]) }} aria-label="Sign in to watch">
        <svg class="{{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span>Watch</span>
    </a>
@else
    @unless(auth()->user()->hasVerifiedEmail())
        <a href="{{ route('verification.notice') }}" {{ $attributes->merge(['class' => "{$baseClasses} border-archive-border text-archive-gray hover:border-archive-black hover:text-archive-black"]) }} aria-label="Verify email to watch">
            <svg class="{{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>Watch</span>
        </a>
    @else
        @if($watching)
            <form method="POST" action="{{ route('campaigns.watch.destroy', $campaign) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" {{ $attributes->merge(['class' => "{$baseClasses} border-archive-black bg-archive-black text-white hover:bg-neutral-800"]) }} aria-label="Stop watching">
                    <svg class="{{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Watching</span>
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('campaigns.watch.store', $campaign) }}" class="inline">
                @csrf
                <button type="submit" {{ $attributes->merge(['class' => "{$baseClasses} border-archive-border text-archive-black hover:border-archive-black hover:bg-archive-black hover:text-white"]) }} aria-label="Watch campaign">
                    <svg class="{{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span>Watch</span>
                </button>
            </form>
        @endif
    @endunless
@endguest
