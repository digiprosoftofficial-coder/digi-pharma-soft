<?php

namespace App\Console\Commands;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Console\Command;

class SuspendExpiredTenants extends Command
{
    protected $signature = 'tenants:suspend-expired';

    protected $description = 'Suspend tenants whose subscription has ended.';

    public function handle(): int
    {
        $count = Tenant::query()
            ->where('is_active', true)
            ->whereNull('suspended_at')
            ->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '<', now())
            ->update(['suspended_at' => now(), 'is_active' => false]);

        $this->info("Suspended {$count} tenant(s).");

        return self::SUCCESS;
    }
}
