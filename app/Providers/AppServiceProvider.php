<?php

namespace App\Providers;

use App\Domain\Accounting\Models\LedgerAccount;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Platform\Models\PlatformSetting;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Catalog\Models\Product;
use App\Domain\Hr\Models\Employee;
use App\Domain\Inventory\Models\StockTransfer;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\DiscountCoupon;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SaleReturn;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\DiscountCouponPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LedgerAccountPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\SalePolicy;
use App\Policies\SaleReturnPolicy;
use App\Policies\StockTransferPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\Platform\PlatformSettingPolicy;
use App\Policies\Platform\SubscriptionPlanPolicy;
use App\Policies\Platform\TenantPolicy;
use App\Policies\TenantUserPolicy;
use App\Support\Tenant\TenantContext;
use App\Support\Tenant\TenantContextResolver;
use App\Support\Tenant\TenantImpersonation;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext);
        $this->app->singleton(TenantImpersonation::class);
        $this->app->singleton(TenantContextResolver::class);
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments = []) {
            $impersonation = app(TenantImpersonation::class);

            if (! $impersonation->isActive() || ! $user->shouldUsePlatformDashboard()) {
                return null;
            }

            if (request()->routeIs('platform.*')) {
                return null;
            }

            $acting = $impersonation->actingUser();
            if ($acting === null) {
                return false;
            }

            return Gate::forUser($acting)->inspect($ability, $arguments)->allowed();
        });

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(SaleReturn::class, SaleReturnPolicy::class);
        Gate::policy(StockTransfer::class, StockTransferPolicy::class);
        Gate::policy(LedgerAccount::class, LedgerAccountPolicy::class);
        Gate::policy(DiscountCoupon::class, DiscountCouponPolicy::class);
        Gate::policy(User::class, TenantUserPolicy::class);
        Gate::policy(Tenant::class, TenantPolicy::class);
        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);
        Gate::policy(PlatformSetting::class, PlatformSettingPolicy::class);

        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;
            if ($user instanceof User) {
                $user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
            if (! method_exists($user, 'tapActivity')) {
                return;
            }
            activity()
                ->performedOn($user)
                ->causedBy($user)
                ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $user->tenant_id)
                ->withProperties(['ip' => request()->ip(), 'user_agent' => request()->userAgent()])
                ->event('login')
                ->log('User logged in');
        });
    }
}
