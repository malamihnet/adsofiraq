@extends('layouts.app')

@section('title', 'Campaigns | Ads Of Iraq')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-12 md:px-8">
    <div class="mb-12">
        <p class="section-label mb-2">Archive</p>
        <h1 class="section-title">Campaigns</h1>
    </div>

    <form method="GET" action="{{ route('campaigns.index') }}" class="mb-8">
        <div class="flex flex-wrap gap-4">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search campaigns..."
                   class="input-field min-w-[200px] flex-1">
            @foreach(request()->except(['search', 'page']) as $key => $value)
                @if(is_string($value) || is_numeric($value))
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <label class="flex items-center gap-2 text-sm">
                <span class="text-archive-gray">Per page</span>
                <select name="per_page" class="input-field w-auto min-w-[5rem]" onchange="this.form.submit()">
                    @foreach([24, 50, 100] as $size)
                        <option value="{{ $size }}" @selected(($perPage ?? 24) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn-primary">Search</button>
        </div>
    </form>

    <div class="grid gap-8 lg:grid-cols-4">
        <aside class="lg:col-span-1">
            <form method="GET" action="{{ route('campaigns.index') }}" class="space-y-6 border border-archive-border p-6">
                @foreach(request()->except(['brand', 'agency', 'industry', 'medium', 'country', 'year', 'sort', 'page']) as $key => $value)
                    @if(is_string($value) || is_numeric($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <div>
                    <label class="section-label mb-2 block">Brand</label>
                    <select name="brand" class="input-field">
                        <option value="">All brands</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->slug }}" @selected(request('brand') === $brand->slug)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="section-label mb-2 block">Agency</label>
                    <select name="agency" class="input-field">
                        <option value="">All agencies</option>
                        @foreach($agencies as $agency)
                            <option value="{{ $agency->slug }}" @selected(request('agency') === $agency->slug)>{{ $agency->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="section-label mb-2 block">Industry</label>
                    <select name="industry" class="input-field">
                        <option value="">All industries</option>
                        @foreach($industries as $industry)
                            <option value="{{ $industry->slug }}" @selected(request('industry') === $industry->slug)>{{ $industry->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="section-label mb-2 block">Medium</label>
                    <select name="medium" class="input-field">
                        <option value="">All mediums</option>
                        @foreach($mediumTypes as $medium)
                            <option value="{{ $medium->slug }}" @selected(request('medium') === $medium->slug)>{{ $medium->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="section-label mb-2 block">Country</label>
                    <select name="country" class="input-field">
                        <option value="">All countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->slug }}" @selected(request('country') === $country->slug)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="section-label mb-2 block">Year</label>
                    <select name="year" class="input-field">
                        <option value="">All years</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected(request('year') == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="section-label mb-2 block">Sort</label>
                    <select name="sort" class="input-field">
                        <option value="latest" @selected(request('sort', 'latest') === 'latest')>Latest</option>
                        <option value="oldest" @selected(request('sort') === 'oldest')>Oldest</option>
                        <option value="views" @selected(request('sort') === 'views')>Most viewed</option>
                        <option value="bookmarks" @selected(request('sort') === 'bookmarks')>Most bookmarked</option>
                    </select>
                </div>

                <div>
                    <label class="section-label mb-2 block">Per page</label>
                    <select name="per_page" class="input-field">
                        @foreach([24, 50, 100] as $size)
                            <option value="{{ $size }}" @selected(($perPage ?? 24) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-primary w-full">Apply filters</button>
                <a href="{{ route('campaigns.index') }}" class="block text-center text-sm underline">Clear all</a>
            </form>
        </aside>

        <div class="lg:col-span-3">
            @if($campaigns->count())
                <x-campaign-grid :campaigns="$campaigns" />
            @else
                <p class="py-16 text-center text-archive-gray">No campaigns found.</p>
            @endif
        </div>
    </div>
</div>
@endsection
