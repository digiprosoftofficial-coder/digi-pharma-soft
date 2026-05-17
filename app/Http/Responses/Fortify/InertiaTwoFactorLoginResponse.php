<?php

namespace App\Http\Responses\Fortify;

use App\Models\User;
use App\Support\Auth\PostAuthRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

final class InertiaTwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
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
