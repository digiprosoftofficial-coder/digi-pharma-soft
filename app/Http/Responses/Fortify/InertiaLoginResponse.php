<?php

namespace App\Http\Responses\Fortify;

use App\Models\User;
use App\Support\Auth\PostAuthRedirect;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

final class InertiaLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();
        $default = $user instanceof User
            ? PostAuthRedirect::dashboardPath($user)
            : route('tenant.dashboard', absolute: false);

        if ($user instanceof User) {
            PostAuthRedirect::forgetTenantDashboardIntendedWhenPlatformOperator($request, $user);
        }

        return redirect()->intended($default);
    }
}
