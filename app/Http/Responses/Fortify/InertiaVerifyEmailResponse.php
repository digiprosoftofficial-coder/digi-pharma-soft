<?php

namespace App\Http\Responses\Fortify;

use App\Models\User;
use App\Support\Auth\PostAuthRedirect;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

final class InertiaVerifyEmailResponse implements VerifyEmailResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();
        $default = $user instanceof User
            ? PostAuthRedirect::dashboardPath($user).'?verified=1'
            : route('tenant.dashboard', absolute: false).'?verified=1';

        if ($user instanceof User) {
            PostAuthRedirect::forgetTenantDashboardIntendedWhenPlatformOperator($request, $user);
        }

        return redirect()->intended($default);
    }
}
