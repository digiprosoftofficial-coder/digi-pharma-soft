<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Http\Request;

final class PostAuthRedirect
{
    public static function dashboardPath(User $user): string
    {
        if ($user->shouldUsePlatformDashboard()) {
            return route('platform.dashboard', absolute: false);
        }

        return route('tenant.dashboard', absolute: false);
    }

    public static function forgetTenantDashboardIntendedWhenPlatformOperator(Request $request, User $user): void
    {
        if (! $user->shouldUsePlatformDashboard()) {
            return;
        }

        $intended = $request->session()->get('url.intended');
        if (is_string($intended) && self::isTenantDashboardUrl($intended)) {
            $request->session()->forget('url.intended');
        }
    }

    public static function isTenantDashboardUrl(string $url): bool
    {
        $intendedPath = parse_url($url, PHP_URL_PATH);
        $tenantPath = parse_url(route('tenant.dashboard', absolute: true), PHP_URL_PATH);

        if (! is_string($intendedPath) || ! is_string($tenantPath)) {
            return false;
        }

        return rtrim($intendedPath, '/') === rtrim($tenantPath, '/');
    }
}
