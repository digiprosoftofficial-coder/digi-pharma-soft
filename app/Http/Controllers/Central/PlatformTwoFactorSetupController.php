<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformTwoFactorSetupController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($user->shouldUsePlatformDashboard(), 403);

        return Inertia::render('Platform/TwoFactor/Setup', [
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'confirming' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null,
        ]);
    }
}
