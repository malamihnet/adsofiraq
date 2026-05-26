@props(['user', 'showUsernameHint' => true, 'showAvatarRemove' => false])

<div class="border border-archive-border p-6">
    <p class="section-label mb-4">Avatar</p>
    <div class="flex flex-wrap items-center gap-6">
        <x-user-avatar :user="$user" size="lg" class="!h-20 !w-20 !text-xl" />
        <div class="space-y-3">
            <div>
                <label class="section-label mb-2 block">Upload new avatar</label>
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-archive-gray file:mr-4 file:border file:border-archive-border file:bg-white file:px-4 file:py-2 file:text-xs file:uppercase file:tracking-widest">
                @error('avatar')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            @if($showAvatarRemove && $user->avatar)
                <label class="inline-flex items-center gap-2 text-sm text-archive-gray">
                    <input type="checkbox" name="remove_avatar" value="1" class="rounded border-archive-border text-archive-black focus:ring-archive-black">
                    Remove current avatar
                </label>
            @endif
        </div>
    </div>
</div>

<div>
    <label class="section-label mb-2 block">Name</label>
    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="input-field">
    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="section-label mb-2 block">Username</label>
    @if($showUsernameHint && ! $user->canChangeUsername())
        <input type="hidden" name="username" value="{{ $user->username }}">
        <p class="input-field bg-archive-light text-archive-gray">{{ $user->username }}</p>
    @else
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm text-archive-gray">@</span>
            <input type="text" name="username" value="{{ old('username', $user->username) }}" required class="input-field pl-8" autocomplete="username">
        </div>
    @endif
    @if($showUsernameHint)
        <p class="mt-2 text-xs text-archive-gray">
            Letters, numbers, underscores, and hyphens only. 3–30 characters.
            @if(! $user->canChangeUsername() && $user->nextUsernameChangeAt())
                You can change your username again on {{ $user->nextUsernameChangeAt()->format('M j, Y') }}.
            @endif
        </p>
    @endif
    @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="section-label mb-2 block">Bio</label>
    <textarea name="bio" rows="4" class="input-field">{{ old('bio', $user->bio) }}</textarea>
    @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div>
    <label class="section-label mb-2 block">Website</label>
    <input type="url" name="website" value="{{ old('website', $user->website) }}" class="input-field" placeholder="https://">
    @error('website')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

<div class="grid gap-6 sm:grid-cols-2">
    <div>
        <label class="section-label mb-2 block">Instagram</label>
        <input type="url" name="instagram_url" value="{{ old('instagram_url', $user->instagram_url) }}" class="input-field" placeholder="https://instagram.com/...">
        @error('instagram_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="section-label mb-2 block">TikTok</label>
        <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $user->tiktok_url) }}" class="input-field" placeholder="https://tiktok.com/...">
        @error('tiktok_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="section-label mb-2 block">Facebook</label>
        <input type="url" name="facebook_url" value="{{ old('facebook_url', $user->facebook_url) }}" class="input-field" placeholder="https://facebook.com/...">
        @error('facebook_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="section-label mb-2 block">LinkedIn <span class="normal-case tracking-normal text-archive-gray">(optional)</span></label>
        <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $user->linkedin_url) }}" class="input-field" placeholder="https://linkedin.com/in/...">
        @error('linkedin_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
