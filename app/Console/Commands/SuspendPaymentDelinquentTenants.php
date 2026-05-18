<?php

namespace App\Console\Commands;

use App\Domain\Tenant\Actions\SuspendTenantAction;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Platform\PlatformSettings;
use Illuminate\Console\Command;

final class SuspendPaymentDelinquentTenants extends Command
{
    protected $signature = 'platform:suspend-payment-delinquent';

    protected $description = 'Suspend pharmacies whose payment grace period has ended';

    public function handle(SuspendTenantAction $suspend): int
    {
        if (! PlatformSettings::autoSuspendOnPaymentFailure()) {
            $this->info('Auto-suspend on payment failure is disabled.');

            return self::SUCCESS;
        }

        $causer = User::query()
            ->where('is_platform_super_admin', true)
            ->orderBy('id')
            ->first();

        if ($causer === null) {
            $this->error('No platform administrator found to attribute suspension.');

            return self::FAILURE;
        }

        $tenants = Tenant::query()
            ->whereNull('suspended_at')
            ->where('billing_status', 'past_due')
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', now())
            ->get();

        foreach ($tenants as $tenant) {
            $suspend->execute(
                $tenant,
                $causer,
                'Automatic suspension: payment failed and grace period expired',
            );
            $this->line("Suspended {$tenant->name} ({$tenant->slug})");
        }

        $this->info("Processed {$tenants->count()} delinquent tenant(s).");

        return self::SUCCESS;
    }
}
