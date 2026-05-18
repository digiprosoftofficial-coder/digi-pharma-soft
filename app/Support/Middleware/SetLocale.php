<?php

namespace App\Support\Middleware;

use App\Support\Platform\PlatformSettings;
use App\Support\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (! is_string($locale) || ! in_array($locale, ['en', 'bn'], true)) {
            $tenant = app(TenantContext::class)->tenant();
            $tenantLocale = is_array($tenant?->settings) ? ($tenant->settings['locale'] ?? null) : null;

            if (is_string($tenantLocale) && in_array($tenantLocale, ['en', 'bn'], true)) {
                $locale = $tenantLocale;
            } else {
                $locale = PlatformSettings::defaultLocale();
            }
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
