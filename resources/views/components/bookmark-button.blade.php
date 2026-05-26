@props(['campaign', 'isBookmarked' => null, 'size' => 'md'])

@php
    $bookmarked = $isBookmarked ?? ($campaign->is_bookmarked ?? (auth()->check() && $campaign->isBookmarkedBy(auth()->user())));
    $sizeClasses = $size === 'sm'
        ? 'gap-1 px-2.5 py-1.5 text-[10px]'
        : 'gap-1.5 px-3 py-2 text-[11px]';
    $iconClasses = $size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4';
    $baseClasses = "inline-flex items-center uppercase tracking-[0.12em] font-medium border transition-colors duration-200 {$sizeClasses}";
@endphp

@guest
    <a href="{{ route('login') }}" {{ $attributes->merge(['class' => "{$baseClasses} border-archive-border text-archive-black hover:border-archive-black hover:bg-archive-black hover:text-white"]) }} aria-label="Sign in to bookmark">
        <svg class="{{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
        </svg>
        <span>Bookmark</span>
    </a>
@else
    @unless(auth()->user()->hasVerifiedEmail())
        <a href="{{ route('verification.notice') }}" {{ $attributes->merge(['class' => "{$baseClasses} border-archive-border text-archive-gray hover:border-archive-black hover:text-archive-black"]) }} aria-label="Verify email to bookmark">
            <svg class="{{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            <span>Bookmark</span>
        </a>
    @else
        @if($bookmarked)
            <form method="POST" action="{{ route('campaigns.bookmark.destroy', $campaign) }}" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" {{ $attributes->merge(['class' => "{$baseClasses} border-archive-black bg-archive-black text-white hover:bg-neutral-800"]) }} aria-label="Remove bookmark">
                    <svg class="{{ $iconClasses }} fill-current" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    <span>Bookmarked</span>
                </button>
            </form>
        @else
            <form method="POST" action="{{ route('campaigns.bookmark.store', $campaign) }}" class="inline">
                @csrf
                <button type="submit" {{ $attributes->merge(['class' => "{$baseClasses} border-archive-border text-archive-black hover:border-archive-black hover:bg-archive-black hover:text-white"]) }} aria-label="Bookmark campaign">
                    <svg class="{{ $iconClasses }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                    </svg>
                    <span>Bookmark</span>
                </button>
            </form>
        @endif
    @endunless
@endguest
