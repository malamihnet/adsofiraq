@props([
    'campaign' => null,
    'industries',
    'mediumTypes',
    'countries',
    'brands',
    'agencies',
    'productionHouses' => null,
    'users',
    'defaultUserId' => null,
    'selectedTaxonomies' => [],
    'selectedPeopleCredits' => [],
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
                :production-houses="$productionHouses ?? $agencies"
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

        <div class="md:col-span-2">
            <x-people-credits-fields :selected="$selectedPeopleCredits ?? []" />
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
                    <option value="draft" @selected(old('status', $campaign?->status) === 'draft')>Draft</option>
                    <option value="pending" @selected(old('status', $campaign?->status ?? 'approved') === 'pending')>Under review</option>
                    <option value="needs_changes" @selected(old('status', $campaign?->status) === 'needs_changes')>Needs changes</option>
                    <option value="approved" @selected(old('status', $campaign?->status ?? 'approved') === 'approved')>Approved</option>
                    <option value="rejected" @selected(old('status', $campaign?->status) === 'rejected')>Rejected</option>
                </select>
                @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex flex-col gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $campaign?->is_featured)) class="rounded border-archive-border">
                    Editor's Pick (homepage curated section &amp; <a href="{{ route('featured.index') }}" class="underline" target="_blank" rel="noopener">/featured</a>)
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_made_by_iraq" value="1" @checked(old('is_made_by_iraq', $campaign?->is_made_by_iraq)) class="rounded border-archive-border">
                    Made By Iraq
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_draft" value="1" @checked(old('is_draft', $campaign?->is_draft)) class="rounded border-archive-border">
                    Draft (hidden)
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_verified" value="1" @checked(old('is_verified', $campaign?->is_verified)) class="rounded border-archive-border">
                    Verified by Ads of Iraq
                </label>
            </div>

            <div>
                <label class="section-label mb-2 block">Editorial label</label>
                <select name="editorial_label" class="input-field">
                    <option value="">— None —</option>
                    @foreach(config('authority.editorial_labels', []) as $key => $label)
                        <option value="{{ $key }}" @selected(old('editorial_label', $campaign?->editorial_label) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="section-label mb-2 block">AI summary (SEO)</label>
                <textarea name="ai_summary" rows="3" class="input-field">{{ old('ai_summary', $campaign?->ai_summary) }}</textarea>
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

            <div class="md:col-span-2 border-t border-archive-border pt-6">
                <h3 class="section-label mb-4">Archive Delay</h3>
                <input type="hidden" name="archive_placement_enabled" value="0">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="archive_placement_enabled" value="1" id="admin-archive-placement-enabled"
                        @checked(old('archive_placement_enabled', $campaign?->archive_placement_enabled)) class="rounded border-archive-border">
                    Enable archive delay
                </label>
                <p class="mt-1 text-xs text-archive-gray">If disabled, campaign follows automatic latest archive order.</p>
                @error('archive_placement_enabled')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="section-label mb-2 block" for="archive_page">Start page</label>
                        <input type="number" name="archive_page" id="archive_page" min="1"
                            value="{{ old('archive_page', $campaign?->archive_page) }}" class="input-field max-w-[120px]">
                        @error('archive_page')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="section-label mb-2 block" for="archive_position">Start position</label>
                        <input type="number" name="archive_position" id="archive_position" min="1" max="100"
                            value="{{ old('archive_position', $campaign?->archive_position) }}" class="input-field max-w-[120px]">
                        @error('archive_position')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="mt-2 text-xs text-archive-gray">
                    Campaign will not appear before this page/position. Newer campaigns can still push it down naturally.
                    Applies to <code>/campaigns</code> Latest sort only. Manage delays on
                    <a href="{{ route('admin.archive-placements.index') }}" class="underline">Archive Delay</a>.
                </p>
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
            const placement = document.getElementById('admin-archive-placement-enabled');
            if (!status) return;

            const sync = () => {
                const approved = status.value === 'approved';
                if (hero) hero.disabled = !approved;
                if (placement) placement.disabled = !approved;
            };

            status.addEventListener('change', sync);
            sync();
        });
    </script>
    @endpush
@endonce
