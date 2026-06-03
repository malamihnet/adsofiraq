@extends('layouts.admin')

@section('title', 'Positions — Admin')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="section-title">Positions</h1>
    <a href="{{ route('admin.positions.create') }}" class="btn-primary text-xs">Add position</a>
</div>

<form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
    <div>
        <label class="section-label mb-1 block text-xs" for="search">Search</label>
        <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Name or slug…" class="input-field max-w-xs text-sm">
    </div>
    <div>
        <label class="section-label mb-1 block text-xs" for="category">Category</label>
        <select name="category" id="category" class="input-field max-w-xs text-sm">
            <option value="">All categories</option>
            @foreach($categories as $value => $label)
                <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn-primary text-xs">Filter</button>
    @if(request()->hasAny(['search', 'category']))
        <a href="{{ route('admin.positions.index') }}" class="text-sm underline">Clear</a>
    @endif
</form>

<p class="mb-4 text-sm text-archive-gray">{{ $positions->count() }} position(s). Run <code class="text-archive-black">php artisan db:seed --class=PositionSeeder</code> to refresh defaults.</p>

<div class="overflow-x-auto border border-archive-border">
    <table class="w-full text-sm">
        <thead class="border-b border-archive-border bg-archive-light">
            <tr>
                <th class="px-4 py-3 text-left">Name</th>
                <th class="px-4 py-3 text-left">Category</th>
                <th class="px-4 py-3 text-left">Slug</th>
                <th class="px-4 py-3 text-right">Sort</th>
                <th class="px-4 py-3 text-right">People</th>
                <th class="px-4 py-3 text-right">Credits</th>
                <th class="px-4 py-3 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($positions as $position)
                <tr class="border-b border-archive-border">
                    <td class="px-4 py-3 font-medium">{{ $position->name }}</td>
                    <td class="px-4 py-3 text-archive-gray">{{ $position->categoryLabel() }}</td>
                    <td class="px-4 py-3 text-archive-gray">{{ $position->slug }}</td>
                    <td class="px-4 py-3 text-right text-archive-gray">{{ $position->sort_order }}</td>
                    <td class="px-4 py-3 text-right">{{ $position->people_count }}</td>
                    <td class="px-4 py-3 text-right">{{ $position->campaign_credits_count }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <a href="{{ route('admin.positions.edit', $position) }}" class="underline">Edit</a>
                        @if($position->people_count === 0 && $position->campaign_credits_count === 0)
                            <span class="mx-1 text-archive-gray">·</span>
                            <form method="POST" action="{{ route('admin.positions.destroy', $position) }}" class="inline" onsubmit="return confirm('Delete this position?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="underline text-red-700">Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-archive-gray">No positions found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
