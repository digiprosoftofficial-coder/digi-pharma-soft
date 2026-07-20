<?php

namespace App\Support\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePlatformTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('platform.2fa_required')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user instanceof User || ! $user->shouldUsePlatformDashboard()) {
            return $next($request);
        }

        if (app()->environment('testing')) {
            return $next($request);
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return $next($request);
        }

        if ($request->routeIs(
            'platform.two-factor.setup',
            'two-factor.enable',
            'two-factor.confirm',
            'two-factor.qr-code',
            'two-factor.secret-key',
            'two-factor.recovery-codes',
            'two-factor.regenerate-recovery-codes',
            'two-factor.disable',
            'password.confirm',
            'password.confirm.store',
            'password.confirmation',
            'logout',
        )) {
            return $next($request);
        }

        return redirect()->route('platform.two-factor.setup');
    }
}
