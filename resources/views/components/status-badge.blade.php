@props(['status'])

@php
    $classes = match($status) {
        'approved' => 'bg-green-100 text-green-800 border-green-200',
        'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'rejected' => 'bg-red-100 text-red-800 border-red-200',
        default => 'bg-neutral-100 text-neutral-800 border-neutral-200',
    };
@endphp

<span class="inline-block border px-2 py-0.5 text-xs uppercase tracking-wider {{ $classes }}">
    {{ $status }}
</span>
