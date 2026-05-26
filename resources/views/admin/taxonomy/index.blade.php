@extends('layouts.admin')

@section('title', $label . ' — Admin')

@section('content')
<h1 class="section-title mb-8">{{ $label }}</h1>

<form method="POST" action="{{ route('admin.' . $type . '.store') }}" class="mb-8 flex gap-4">
    @csrf
    <input type="text" name="name" placeholder="Name" required class="input-field max-w-sm">
    <button type="submit" class="btn-primary">Add</button>
</form>

@if($verifiable ?? false)
    <form method="GET" class="mb-6 flex gap-4">
        <x-admin.verification-filter />
        <button type="submit" class="btn-primary">Filter</button>
    </form>
@endif

<div class="overflow-x-auto border border-archive-border">
    <table class="w-full text-sm">
        <thead class="border-b border-archive-border bg-archive-light">
            <tr>
                <th class="px-4 py-3 text-left">Name</th>
                <th class="px-4 py-3 text-left">Slug</th>
                @if($verifiable ?? false)
                    <th class="px-4 py-3 text-left">Verified</th>
                @endif
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr class="border-b border-archive-border">
                    <td class="px-4 py-3">
                        @if($verifiable ?? false)
                            <a href="{{ route('admin.' . $type . '.show', $item->id) }}" class="inline-flex items-center gap-2 underline">
                                {{ $item->name }}
                                <x-verified-badge :verified="$item->is_verified" />
                            </a>
                        @else
                            <form method="POST" action="{{ route('admin.' . $type . '.update', $item->id) }}" class="flex gap-2">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $item->name }}" class="input-field">
                                <button type="submit" class="btn-outline text-xs">Save</button>
                            </form>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-archive-gray">{{ $item->slug }}</td>
                    @if($verifiable ?? false)
                        <td class="px-4 py-3">
                            @if($item->is_verified)
                                <span class="text-xs uppercase tracking-wider text-[#1d9bf0]">Verified</span>
                            @else
                                —
                            @endif
                        </td>
                    @endif
                    <td class="px-4 py-3">
                        @if($verifiable ?? false)
                            <a href="{{ route('admin.' . $type . '.show', $item->id) }}" class="underline text-xs">Manage</a>
                            <span class="mx-1 text-archive-gray">·</span>
                        @endif
                        <form method="POST" action="{{ route('admin.' . $type . '.destroy', $item->id) }}" class="inline" onsubmit="return confirm('Delete?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 underline text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $items->links() }}</div>
@endsection
