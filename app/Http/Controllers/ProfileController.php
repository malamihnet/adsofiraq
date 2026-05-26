<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AvatarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected AvatarService $avatars,
    ) {}

    public function showRedirect(Request $request): RedirectResponse
    {
        return redirect()->route('users.show', $request->user());
    }

    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only([
            'name',
            'username',
            'bio',
            'website',
            'instagram_url',
            'tiktok_url',
            'facebook_url',
            'linkedin_url',
        ]);

        if ($data['username'] !== $user->username) {
            $data['username_changed_at'] = now();
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->avatars->replace($user->avatar, $request->file('avatar'));
        }

        $user->update($data);

        return redirect()->route('users.show', $user)
            ->with('success', 'Profile updated successfully.');
    }

    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->avatars->delete($user->avatar);
        $user->update(['avatar' => null]);

        return back()->with('success', 'Avatar removed.');
    }
}
