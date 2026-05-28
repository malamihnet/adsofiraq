@extends('layouts.admin')

@section('title', $item->name . ' — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.' . $type . '.index') }}" class="text-sm underline">&larr; All {{ strtolower($label) }}</a>
</div>

<div class="mb-8 flex flex-wrap items-center gap-4">
    <h1 class="section-title inline-flex items-center gap-2">
        {{ $item->name }}
        <x-verified-badge :verified="$item->is_verified" />
    </h1>
</div>

<p class="mb-8 text-sm text-archive-gray">Slug: {{ $item->slug }} · {{ $item->campaigns_count }} campaigns</p>

<form method="POST" action="{{ route('admin.' . $type . '.update', $item->id) }}" class="mb-8 flex max-w-md gap-4">
    @csrf
    @method('PUT')
    <input type="text" name="name" value="{{ $item->name }}" class="input-field" required>
    <button type="submit" class="btn-outline text-xs">Save name</button>
</form>

<x-admin.verification-form
    :action="route('admin.' . $type . '.verification', $item->id)"
    :model="$item"
/>

<p class="mt-8">
    <a href="{{ route($type === 'agencies' ? 'agency.show' : 'brand.show', $item) }}" class="underline text-sm">View public page</a>
</p>
@endsection
