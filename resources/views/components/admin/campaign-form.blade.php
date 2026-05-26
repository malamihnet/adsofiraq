@props([
    'campaign' => null,
    'industries',
    'mediumTypes',
    'countries',
    'brands',
    'agencies',
    'users',
    'defaultUserId' => null,
    'selectedTaxonomies' => [],
])

<form method="POST"
      action="{{ $campaign ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store') }}"
      enctype="multipart/form-data"
      class="space-y-8">
    @csrf
    @if($campaign)
        @method('PUT')
    @endif

    <div class="border border-archive-border p-6">
        <p class="section-label mb-4">Ownership</p>
        <label class="section-label mb-2 block" for="user_id">Submitted by</label>
        <select name="user_id" id="user_id" class="input-field max-w-xl">
            @foreach($users as $user)
                <option value="{{ $user->id }}"
                    @selected(old('user_id', $campaign?->user_id ?? $defaultUserId) == $user->id)>
                    {{ $user->name }} ({{ '@'.$user->username }}) — {{ $user->email }}
                </option>
            @endforeach
        </select>
        @error('user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

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
            <label class="section-label mb-2 block">Published Date</label>
            <input type="date" name="published_at" value="{{ old('published_at', $campaign?->published_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="input-field">
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
            <label class="section-label mb-2 block">Submission Notes</label>
            <textarea name="submission_notes" rows="3" class="input-field">{{ old('submission_notes', $campaign?->submission_notes) }}</textarea>
        </div>

        <div class="flex flex-wrap gap-6 md:col-span-2">
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

    <div class="border border-archive-border p-6">
        <p class="section-label mb-4">Admin settings</p>
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <label class="section-label mb-2 block">Status *</label>
                <select name="status" id="admin-campaign-status" class="input-field" required>
                    <option value="pending" @selected(old('status', $campaign?->status ?? 'approved') === 'pending')>Pending</option>
                    <option value="approved" @selected(old('status', $campaign?->status ?? 'approved') === 'approved')>Approved</option>
                    <option value="rejected" @selected(old('status', $campaign?->status) === 'rejected')>Rejected</option>
                </select>
                @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $campaign?->is_featured)) class="rounded border-archive-border">
                    Featured campaign
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_verified" value="1" @checked(old('is_verified', $campaign?->is_verified)) class="rounded border-archive-border">
                    Verified by Ads of Iraq
                </label>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_hero" value="1" id="admin-campaign-is-hero"
                        @checked(old('is_hero', $campaign?->is_hero)) class="rounded border-archive-border">
                    Homepage hero slider
                </label>
                @error('is_hero')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                <label class="section-label mb-2 mt-4 block" for="hero_order">Hero order</label>
                <input type="number" name="hero_order" id="hero_order" min="1" max="99"
                    value="{{ old('hero_order', $campaign?->hero_order) }}" class="input-field max-w-[120px]" placeholder="1">
                <p class="mt-1 text-xs text-archive-gray">Hero order is optional. By default, the latest campaign approved on Ads of Iraq appears first.</p>
            </div>

            <div class="md:col-span-2">
                <label class="section-label mb-2 block">Admin notes</label>
                <textarea name="admin_notes" rows="3" class="input-field">{{ old('admin_notes', $campaign?->admin_notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="border-t border-archive-border pt-8">
        <button type="submit" class="btn-primary">{{ $campaign ? 'Update Campaign' : 'Create Campaign' }}</button>
        <a href="{{ route('admin.campaigns.index') }}" class="btn-outline ml-4">Cancel</a>
    </div>
</form>

@once
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const status = document.getElementById('admin-campaign-status');
            const hero = document.getElementById('admin-campaign-is-hero');
            if (!status || !hero) return;
            const sync = () => { hero.disabled = status.value !== 'approved'; };
            status.addEventListener('change', sync);
            sync();
        });
    </script>
    @endpush
@endonce
