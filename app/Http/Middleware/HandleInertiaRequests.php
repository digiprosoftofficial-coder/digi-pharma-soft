<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Locale\TranslationLoader;
use App\Support\Tenant\TenantContext;
use App\Support\Tenant\TenantImpersonation;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $tenantContext = app(TenantContext::class);
        $impersonation = app(TenantImpersonation::class);
        $tenant = $tenantContext->tenant();
        $locale = app()->getLocale();
        $authUser = $request->user();
        $permissionUser = $authUser instanceof User && $impersonation->isActive()
            ? $impersonation->actingUser() ?? $authUser
            : $authUser;

        return [
            ...parent::share($request),
            'locale' => $locale,
            'locales' => TranslationLoader::availableLocales(),
            'translations' => TranslationLoader::forLocale($locale),
            'tenant' => $tenant ? [
                'id' => $tenant->getKey(),
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ] : null,
            'impersonation' => $impersonation->isActive() && $tenant ? [
                'active' => true,
                'tenant_name' => $tenant->name,
                'acting_as' => $impersonation->actingUser()?->name,
                'stop_url' => route('platform.impersonation.destroy'),
            ] : null,
            'auth' => [
                'user' => $authUser instanceof User ? [
                    'id' => $authUser->getKey(),
                    'name' => $authUser->name,
                    'email' => $authUser->email,
                    'email_verified_at' => $authUser->email_verified_at,
                    'is_platform_super_admin' => (bool) $authUser->is_platform_super_admin,
                    'uses_platform_dashboard' => $authUser->shouldUsePlatformDashboard(),
                    'roles' => $permissionUser instanceof User
                        ? $permissionUser->getRoleNames()->values()->all()
                        : [],
                    'permissions' => $permissionUser instanceof User
                        ? $permissionUser->getAllPermissions()->pluck('name')->values()->all()
                        : [],
                ] : null,
            ],
        ];
    }
}
