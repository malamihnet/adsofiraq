@props(['campaign' => null, 'industries', 'mediumTypes', 'countries', 'brands', 'agencies', 'selectedTaxonomies' => []])

<form method="POST" action="{{ $campaign ? route('campaigns.update', $campaign) : route('campaigns.store') }}"
      enctype="multipart/form-data" class="space-y-8">
    @csrf
    @if($campaign)
        @method('PUT')
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="section-label mb-2 block">Campaign Title *</label>
            <input type="text" name="title" value="{{ old('title', $campaign?->title) }}" required class="input-field">
            @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <x-campaign-taxonomy-fields
                :agencies="$agencies"
                :brands="$brands"
                :industries="$industries"
                :medium-types="$mediumTypes"
                :countries="$countries"
                :selected="$selectedTaxonomies"
            />
        </div>

        <div>
            <label class="section-label mb-2 block">Publish / Creation Date</label>
            <input type="date" name="published_at" value="{{ old('published_at', $campaign?->published_at?->format('Y-m-d')) }}" class="input-field">
        </div>

        <div class="md:col-span-2">
            <label class="section-label mb-2 block">Description *</label>
            <textarea name="description" rows="6" required class="input-field">{{ old('description', $campaign?->description) }}</textarea>
            @error('description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label class="section-label mb-2 block">Credits</label>
            <textarea name="credits" rows="4" class="input-field" placeholder="Creative team, production credits...">{{ old('credits', $campaign?->credits) }}</textarea>
        </div>

        <x-campaign-videos-fields :campaign="$campaign" />

        <div>
            <label class="section-label mb-2 block">Thumbnail</label>
            <input type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp" class="input-field">
            <p class="mt-1 text-xs text-archive-gray">Optional. If left empty, the first still or video thumbnail will be used automatically.</p>
            @if($campaign?->thumbnail_url)
                <img src="{{ $campaign->thumbnail_url }}" alt="" class="mt-2 h-24 object-cover">
            @endif
            @error('thumbnail')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="section-label mb-2 block">Stills / Assets</label>
            <input type="file" name="assets[]" accept=".jpg,.jpeg,.png,.webp" multiple class="input-field">
            <p class="mt-1 text-xs text-archive-gray">Upload multiple stills or visuals from the campaign. If no thumbnail is uploaded, the first still will be used as the campaign thumbnail.</p>
            <x-campaign-existing-assets :campaign="$campaign" />
            @error('assets')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            @error('assets.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-2">
            <label class="section-label mb-2 block">Submission Notes (admin only)</label>
            <textarea name="submission_notes" rows="3" class="input-field">{{ old('submission_notes', $campaign?->submission_notes) }}</textarea>
        </div>

        <div class="flex gap-6 md:col-span-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_student" value="1" @checked(old('is_student', $campaign?->is_student)) class="rounded border-archive-border">
                Student work
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_nsfw" value="1" @checked(old('is_nsfw', $campaign?->is_nsfw)) class="rounded border-archive-border">
                NSFW
            </label>
        </div>
    </div>

    <div class="border-t border-archive-border pt-8">
        <button type="submit" class="btn-primary">{{ $campaign ? 'Update Campaign' : 'Submit Campaign' }}</button>
        <p class="mt-4 text-sm text-archive-gray">Submitted campaigns require admin approval before appearing publicly.</p>
    </div>
</form>
