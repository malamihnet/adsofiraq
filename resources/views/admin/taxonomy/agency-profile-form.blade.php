@props(['agency', 'companyRoles'])

<form
    method="POST"
    action="{{ route('admin.agencies.update', $agency->id) }}"
    enctype="multipart/form-data"
    class="max-w-2xl space-y-8"
>
    @csrf
    @method('PUT')

    <div>
        <label for="name" class="section-label mb-2 block">Name</label>
        <input type="text" name="name" id="name" value="{{ old('name', $agency->name) }}" class="input-field" required>
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label class="section-label mb-2 block">Logo</label>
            @if($agency->logo_url)
                <img src="{{ $agency->logo_url }}" alt="" class="mb-3 h-20 w-20 rounded-full border border-archive-border object-contain p-1">
            @endif
            <input type="file" name="logo" accept="image/*" class="input-field text-sm">
            @if($agency->logo_path)
                <label class="mt-2 flex items-center gap-2 text-xs text-archive-gray">
                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-archive-border">
                    Remove current logo
                </label>
            @endif
            @error('logo')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="section-label mb-2 block">Cover image <span class="normal-case text-archive-gray">(optional)</span></label>
            @if($agency->cover_url)
                <img src="{{ $agency->cover_url }}" alt="" class="mb-3 h-24 w-full max-w-xs rounded-lg border border-archive-border object-cover">
            @endif
            <input type="file" name="cover" accept="image/*" class="input-field text-sm">
            @if($agency->cover_path)
                <label class="mt-2 flex items-center gap-2 text-xs text-archive-gray">
                    <input type="checkbox" name="remove_cover" value="1" class="rounded border-archive-border">
                    Remove current cover
                </label>
            @endif
            @error('cover')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div>
        <label for="bio" class="section-label mb-2 block">Bio / About</label>
        <textarea name="bio" id="bio" rows="5" class="input-field min-h-[120px]">{{ old('bio', $agency->bio) }}</textarea>
        @error('bio')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>

    <fieldset class="space-y-3">
        <legend class="section-label">Links</legend>
        <div>
            <label for="website_url" class="mb-1 block text-xs text-archive-gray">Website</label>
            <input type="url" name="website_url" id="website_url" value="{{ old('website_url', $agency->website_url) }}" class="input-field" placeholder="https://">
        </div>
        <div>
            <label for="instagram_url" class="mb-1 block text-xs text-archive-gray">Instagram</label>
            <input type="url" name="instagram_url" id="instagram_url" value="{{ old('instagram_url', $agency->instagram_url) }}" class="input-field" placeholder="https://">
        </div>
        <div>
            <label for="linkedin_url" class="mb-1 block text-xs text-archive-gray">LinkedIn</label>
            <input type="url" name="linkedin_url" id="linkedin_url" value="{{ old('linkedin_url', $agency->linkedin_url) }}" class="input-field" placeholder="https://">
        </div>
        <div>
            <label for="twitter_url" class="mb-1 block text-xs text-archive-gray">X (Twitter)</label>
            <input type="url" name="twitter_url" id="twitter_url" value="{{ old('twitter_url', $agency->twitter_url) }}" class="input-field" placeholder="https://">
        </div>
        <div>
            <label for="facebook_url" class="mb-1 block text-xs text-archive-gray">Facebook</label>
            <input type="url" name="facebook_url" id="facebook_url" value="{{ old('facebook_url', $agency->facebook_url) }}" class="input-field" placeholder="https://">
        </div>
    </fieldset>

    <fieldset class="space-y-3">
        <legend class="section-label">Company roles</legend>
        @php
            $selectedRoles = old('company_roles', $agency->roles->pluck('role')->all());
        @endphp
        @foreach($companyRoles as $role)
            <label class="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    name="company_roles[]"
                    value="{{ $role->value }}"
                    @checked(in_array($role->value, $selectedRoles, true))
                    class="rounded border-archive-border"
                >
                {{ $role->label() }}
            </label>
        @endforeach
    </fieldset>

    <div class="border border-archive-border p-5">
        <p class="section-label mb-3">Platform verification</p>
        <label class="flex cursor-pointer items-center gap-3">
            <input
                type="checkbox"
                name="is_verified"
                value="1"
                class="rounded border-archive-border"
                @checked(old('is_verified', $agency->is_verified))
            >
            <span class="text-sm">Verified by Ads of Iraq</span>
        </label>
        @if($agency->is_verified && $agency->verified_at)
            <p class="mt-2 text-xs text-archive-gray">
                Verified {{ $agency->verified_at->format('M j, Y') }}
                @if($agency->verifiedBy)
                    by {{ $agency->verifiedBy->name }}
                @endif
            </p>
        @endif
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="btn-primary text-xs">Save profile</button>
        <a href="{{ route('agency.show', $agency) }}" class="btn-outline text-xs" target="_blank" rel="noopener">View public page</a>
    </div>
</form>
