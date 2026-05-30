@extends('layouts.admin')

@section('title', $item->name . ' — Admin')

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.' . $type . '.index') }}" class="text-sm underline">&larr; All {{ strtolower($label) }}</a>
</div>

<div class="mb-8 flex flex-wrap items-center gap-4">
    <h1 class="section-title inline-flex items-center gap-2 flex-wrap">
        {{ $item->name }}
        <x-verified-badge :verified="$item->is_verified" />
        @if($type === 'agencies' && method_exists($item, 'roleLabels'))
            <x-agency-role-badges :roles="$item->roleLabels()" />
        @endif
    </h1>
</div>

<p class="mb-8 text-sm text-archive-gray">Slug: {{ $item->slug }} · {{ $item->campaigns_count }} campaigns</p>

@if(session('success'))
    <p class="mb-6 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</p>
@endif

@if($type === 'agencies')
    <h2 class="section-label mb-6">Edit profile</h2>
    @include('admin.taxonomy.agency-profile-form', ['agency' => $item, 'companyRoles' => $companyRoles])
@else
    <form method="POST" action="{{ route('admin.' . $type . '.update', $item->id) }}" class="mb-8 max-w-md">
        @csrf
        @method('PUT')
        <div class="flex gap-4">
            <input type="text" name="name" value="{{ $item->name }}" class="input-field" required>
            <button type="submit" class="btn-outline text-xs">Save name</button>
        </div>
    </form>

    <x-admin.verification-form
        :action="route('admin.' . $type . '.verification', $item->id)"
        :model="$item"
    />

    <p class="mt-8">
        <a href="{{ route('brand.show', $item) }}" class="underline text-sm">View public page</a>
    </p>
@endif
@endsection
