@props([
    'assets' => null,
    'stills' => null,
    'title' => 'Campaign',
])

@php
    $gallery = $stills ?? $assets ?? collect();
@endphp

<x-campaign-gallery :stills="$gallery" :title="$title" />
