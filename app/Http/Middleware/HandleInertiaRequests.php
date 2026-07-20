<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Locale\TranslationLoader;
use App\Support\Money\MoneyFormatter;
use App\Support\Platform\PlatformAnnouncementService;
use App\Support\Platform\PlatformSettings;
use App\Domain\Tenant\Models\Branch;
use App\Support\Tenant\BranchContext;
use App\Support\Tenant\TenantContext;
use App\Support\Tenant\TenantFeatures;
use App\Support\Tenant\TenantImpersonation;
use App\Support\Theme\ThemeCatalog;
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
            'features' => TenantFeatures::shareForInertia($tenant),
            'theme' => ThemeCatalog::resolveForTenant($tenant),
            'branch' => $this->branchShare($tenant),
            'branches' => $this->branchesShare($tenant),
            'money' => $this->moneyShare($tenant, $locale),
            'impersonation' => $impersonation->isActive() && $tenant ? [
                'active' => true,
                'tenant_name' => $tenant->name,
                'acting_as' => $impersonation->actingUser()?->name,
                'stop_url' => route('platform.impersonation.destroy'),
            ] : null,
            'networkAnnouncement' => $tenant && ! $request->routeIs('platform.*')
                ? PlatformAnnouncementService::activeBanner()
                : null,
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

    /**
     * @return array{currency: string, locale: string, symbol: string}
     */
    /**
     * @return array{id:int, name:string, code:string}|null
     */
    private function branchShare(?\App\Domain\Tenant\Models\Tenant $tenant): ?array
    {
        if ($tenant === null) {
            return null;
        }

        $branch = app(BranchContext::class)->branch();
        if ($branch === null) {
            return null;
        }

        return [
            'id' => $branch->getKey(),
            'name' => $branch->name,
            'code' => $branch->code,
        ];
    }

    /**
     * @return list<array{id:int, name:string, code:string, is_default:bool}>
     */
    private function branchesShare(?\App\Domain\Tenant\Models\Tenant $tenant): array
    {
        if ($tenant === null || ! TenantFeatures::multiBranchEnabled($tenant)) {
            return [];
        }

        return Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_default'])
            ->map(fn (Branch $b) => [
                'id' => $b->getKey(),
                'name' => $b->name,
                'code' => $b->code,
                'is_default' => (bool) $b->is_default,
            ])
            ->values()
            ->all();
    }

    private function moneyShare(?\App\Domain\Tenant\Models\Tenant $tenant, string $appLocale): array
    {
        $currency = $tenant && method_exists($tenant, 'currency')
            ? $tenant->currency()
            : PlatformSettings::defaultCurrency();

        $locale = MoneyFormatter::localeFor($currency, $appLocale);

        return [
            'currency' => $currency,
            'locale' => $locale,
            'symbol' => MoneyFormatter::symbol($currency, $locale),
        ];
    }
}
