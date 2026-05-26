<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserUpdateRequest;
use App\Models\User;
use App\Services\AvatarService;
use App\Services\PlatformVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected PlatformVerificationService $verificationService,
        protected AvatarService $avatars,
    ) {}

    public function index(Request $request): View
    {
        $query = User::withCount(['campaigns', 'bookmarks', 'campaignWatchers'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role') && in_array($request->role, ['user', 'admin'], true)) {
            $query->where('role', $request->role);
        }

        if ($request->input('email_verified') === 'verified') {
            $query->whereNotNull('email_verified_at');
        } elseif ($request->input('email_verified') === 'unverified') {
            $query->whereNull('email_verified_at');
        }

        $query->platformVerificationFilter($request->input('verified'));

        return view('admin.users.index', [
            'users' => $query->paginate(30)->withQueryString(),
        ]);
    }

    public function show(User $user): View
    {
        $user->load(['verifiedBy'])->loadCount([
            'campaigns',
            'approvedCampaigns',
            'bookmarks',
            'campaignWatchers',
        ]);

        $reassignCandidates = User::query()
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get(['id', 'name', 'username']);

        return view('admin.users.show', compact('user', 'reassignCandidates'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(AdminUserUpdateRequest $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id && $request->input('role') !== 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return back()->withInput()->with('error', 'You cannot remove your own admin role as the only admin.');
            }
        }

        if ($user->isAdmin() && $request->input('role') !== 'admin') {
            $remainingAdmins = User::where('role', 'admin')->where('id', '!=', $user->id)->count();
            if ($remainingAdmins < 1) {
                return back()->withInput()->with('error', 'Cannot demote the last admin.');
            }
        }

        $data = $request->safe()->only([
            'name',
            'username',
            'email',
            'bio',
            'website',
            'instagram_url',
            'tiktok_url',
            'facebook_url',
            'linkedin_url',
            'role',
        ]);

        if ($data['username'] !== $user->username) {
            $data['username_changed_at'] = now();
        }

        if ($request->boolean('email_verified')) {
            $data['email_verified_at'] = $user->email_verified_at ?? now();
        } else {
            $data['email_verified_at'] = null;
        }

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $this->avatars->replace($user->avatar, $request->file('avatar'));
        } elseif ($request->boolean('remove_avatar')) {
            $this->avatars->delete($user->avatar);
            $data['avatar'] = null;
        }

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $user->update($data);

        $this->verificationService->update(
            $user->fresh(),
            $request->user(),
            $request->boolean('is_verified')
        );

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Cannot delete the last admin.');
        }

        $campaignCount = $user->campaigns()->count();

        if ($campaignCount > 0) {
            $validated = $request->validate([
                'reassign_to' => ['required', 'exists:users,id', 'not_in:'.$user->id],
            ]);

            $user->campaigns()->update(['user_id' => $validated['reassign_to']]);
        }

        $this->avatars->delete($user->avatar);
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
