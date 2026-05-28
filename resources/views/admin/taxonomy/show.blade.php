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
        @if($type === 'agencies' && $item->is_production_house)
            <x-production-house-badge />
        @endif
    </h1>
</div>

<p class="mb-8 text-sm text-archive-gray">Slug: {{ $item->slug }} · {{ $item->campaigns_count }} campaigns</p>

<form method="POST" action="{{ route('admin.' . $type . '.update', $item->id) }}" class="mb-8 max-w-md space-y-4">
    @csrf
    @method('PUT')
    <div class="flex gap-4">
        <input type="text" name="name" value="{{ $item->name }}" class="input-field" required>
        <button type="submit" class="btn-outline text-xs">Save</button>
    </div>
    @if($type === 'agencies')
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_production_house" value="1" @checked(old('is_production_house', $item->is_production_house)) class="rounded border-archive-border">
            Is Production House
        </label>
    @endif
</form>

<x-admin.verification-form
    :action="route('admin.' . $type . '.verification', $item->id)"
    :model="$item"
/>

<p class="mt-8">
    <a href="{{ route($type === 'agencies' ? 'agency.show' : 'brand.show', $item) }}" class="underline text-sm">View public page</a>
</p>
@endsection
