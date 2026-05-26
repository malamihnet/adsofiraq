@extends('layouts.app')

@section('title', 'Apply for People Listing — Ads of Iraq')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-12 md:px-8">
    <p class="section-label mb-2">People</p>
    <h1 class="section-title mb-2">Apply for listing</h1>
    <p class="mb-8 text-sm text-archive-gray">Submit your profile for review. Approved listings appear on the public People directory.</p>

    @if(session('success'))
        <p class="mb-8 border border-archive-border bg-archive-light px-4 py-3 text-sm">{{ session('success') }}</p>
    @endif

    <form method="POST" action="{{ route('people.apply.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div>
            <label class="section-label mb-2 block">Photo <span class="text-red-600">*</span></label>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required class="block w-full text-sm file:mr-4 file:border file:border-archive-border file:bg-white file:px-4 file:py-2 file:text-xs file:uppercase file:tracking-widest">
            @error('photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="section-label mb-2 block">Name <span class="text-red-600">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required class="input-field">
            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="section-label mb-2 block">Position / role <span class="text-red-600">*</span></label>
            <input type="text" name="position" value="{{ old('position') }}" required class="input-field" placeholder="Director, Producer, Photographer...">
            @error('position')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="section-label mb-2 block">Bio</label>
            <textarea name="bio" rows="4" class="input-field">{{ old('bio') }}</textarea>
            @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label class="section-label mb-2 block">Website</label>
                <input type="url" name="website_url" value="{{ old('website_url') }}" class="input-field" placeholder="https://">
                @error('website_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="section-label mb-2 block">Official profile link</label>
                <input type="url" name="official_profile_url" value="{{ old('official_profile_url') }}" class="input-field" placeholder="https://">
                @error('official_profile_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="section-label mb-2 block">Featured work <span class="text-red-600">*</span></label>
            <p class="mb-3 text-xs text-archive-gray">Three projects or campaigns you worked on.</p>
            <div class="space-y-3">
                <input type="text" name="work_1" value="{{ old('work_1') }}" required class="input-field" placeholder="Work 1">
                <input type="text" name="work_2" value="{{ old('work_2') }}" required class="input-field" placeholder="Work 2">
                <input type="text" name="work_3" value="{{ old('work_3') }}" required class="input-field" placeholder="Work 3">
            </div>
            @error('work_1')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('work_2')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('work_3')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="section-label mb-2 block">Notes (optional)</label>
            <textarea name="submission_notes" rows="3" class="input-field">{{ old('submission_notes') }}</textarea>
            @error('submission_notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-primary">Submit profile</button>
    </form>
</div>
@endsection
