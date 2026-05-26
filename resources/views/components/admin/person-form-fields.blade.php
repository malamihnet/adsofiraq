@props(['person' => null, 'requirePhoto' => false])

<div class="grid gap-6 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="section-label mb-2 block">Photo @if($requirePhoto)<span class="text-red-600">*</span>@endif</label>
        @if($person?->photo_path)
            <img src="{{ $person->photo_url }}" alt="" class="mb-3 h-24 w-24 rounded-full object-cover">
            <label class="mb-3 inline-flex items-center gap-2 text-sm text-archive-gray">
                <input type="checkbox" name="remove_photo" value="1" class="rounded border-archive-border">
                Remove photo
            </label>
        @endif
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" @if($requirePhoto) required @endif class="block w-full text-sm">
        @error('photo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="section-label mb-2 block">Name</label>
        <input type="text" name="name" value="{{ old('name', $person?->name) }}" required class="input-field">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="section-label mb-2 block">Position</label>
        <input type="text" name="position" value="{{ old('position', $person?->position) }}" required class="input-field">
        @error('position')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="section-label mb-2 block">Bio</label>
        <textarea name="bio" rows="4" class="input-field">{{ old('bio', $person?->bio) }}</textarea>
        @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="section-label mb-2 block">Website</label>
        <input type="url" name="website_url" value="{{ old('website_url', $person?->website_url) }}" class="input-field">
        @error('website_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="section-label mb-2 block">Official profile URL</label>
        <input type="url" name="official_profile_url" value="{{ old('official_profile_url', $person?->official_profile_url) }}" class="input-field">
        @error('official_profile_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="section-label mb-2 block">Work 1</label>
        <input type="text" name="work_1" value="{{ old('work_1', $person?->work_1) }}" class="input-field">
    </div>
    <div>
        <label class="section-label mb-2 block">Work 2</label>
        <input type="text" name="work_2" value="{{ old('work_2', $person?->work_2) }}" class="input-field">
    </div>
    <div>
        <label class="section-label mb-2 block">Work 3</label>
        <input type="text" name="work_3" value="{{ old('work_3', $person?->work_3) }}" class="input-field">
    </div>

    <div>
        <label class="section-label mb-2 block">Status</label>
        <select name="status" class="input-field">
            @foreach(['pending', 'approved', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(old('status', $person?->status ?? 'approved') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_verified" value="1" class="rounded border-archive-border" @checked(old('is_verified', $person?->is_verified ?? false))>
            <span class="text-sm">Platform verified</span>
        </label>
    </div>

    <div class="sm:col-span-2">
        <label class="section-label mb-2 block">Submission notes</label>
        <textarea name="submission_notes" rows="3" class="input-field">{{ old('submission_notes', $person?->submission_notes) }}</textarea>
    </div>
</div>
