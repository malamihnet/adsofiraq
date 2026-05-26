@props(['verified' => false])

@if($verified)
    <span
        {{ $attributes->merge(['class' => 'inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[#1d9bf0]']) }}
        title="Verified by Ads of Iraq"
        aria-label="Verified by Ads of Iraq"
        role="img"
    >
        <svg class="h-2.5 w-2.5 text-white" viewBox="0 0 12 12" fill="none" aria-hidden="true">
            <path d="M2.5 6L5 8.5L9.5 3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </span>
@endif
