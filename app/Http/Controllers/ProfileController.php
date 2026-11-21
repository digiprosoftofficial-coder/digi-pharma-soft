<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\User\UserAvatarStorage;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => UserAvatarStorage::url($user->avatar_path),
                'email_verified_at' => $user->email_verified_at,
            ],
            'usesPlatformShell' => $user->shouldUsePlatformDashboard(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ]);

        $avatarPath = $user->avatar_path;

        if ($request->boolean('remove_avatar')) {
            UserAvatarStorage::delete($avatarPath);
            $avatarPath = null;
        }

        if ($request->hasFile('avatar')) {
            UserAvatarStorage::delete($avatarPath);
            $avatarPath = UserAvatarStorage::store($request->file('avatar'), (int) $user->getKey());
        }

        $emailChanged = $validated['email'] !== $user->email;

        $user->forceFill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'avatar_path' => $avatarPath,
        ]);

        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $user->email_verified_at = null;
            $user->save();
            $user->sendEmailVerificationNotification();
        } else {
            $user->save();
        }

        Auth::setUser($user->fresh());

        return redirect()
            ->route('profile.edit')
            ->with('success', __('common.profile_updated'));
    }
}
