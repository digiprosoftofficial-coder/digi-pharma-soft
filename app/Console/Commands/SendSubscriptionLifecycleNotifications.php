<?php

namespace App\Console\Commands;

use App\Domain\Tenant\Models\Tenant;
use App\Notifications\PlatformEventNotification;
use App\Support\Platform\PlatformEventNotifier;
use Illuminate\Console\Command;

class SendSubscriptionLifecycleNotifications extends Command
{
    protected $signature = 'platform:notify-subscription-lifecycle';

    protected $description = 'Queue trial ending / subscription renewal reminder notifications';

    public function handle(PlatformEventNotifier $notifier): int
    {
        $trialEnding = Tenant::query()
            ->whereNull('suspended_at')
            ->where('is_active', true)
            ->where('billing_status', 'trialing')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(3)])
            ->with('users')
            ->get();

        foreach ($trialEnding as $tenant) {
            $owner = $tenant->users()->first();
            if ($owner) {
                $owner->notify(new PlatformEventNotification(
                    'trial.ending_soon',
                    'Trial ending soon',
                    "Your DigiProSoft trial for {$tenant->name} ends on {$tenant->trial_ends_at->toDateString()}.",
                    ['tenant_id' => $tenant->getKey()],
                ));
            }

            $notifier->notifyOperators(
                'trial.ending_soon',
                'Trial ending soon',
                "{$tenant->name} trial ends {$tenant->trial_ends_at?->toDateString()}.",
                ['tenant_id' => $tenant->getKey()],
            );
        }

        $renewals = Tenant::query()
            ->whereNull('suspended_at')
            ->where('is_active', true)
            ->where('billing_status', 'active')
            ->whereBetween('subscription_ends_at', [now(), now()->addDays(7)])
            ->get();

        foreach ($renewals as $tenant) {
            $owner = $tenant->users()->first();
            if ($owner) {
                $owner->notify(new PlatformEventNotification(
                    'subscription.renewal_reminder',
                    'Subscription renewal reminder',
                    "Your DigiProSoft subscription for {$tenant->name} renews/ends on {$tenant->subscription_ends_at->toDateString()}.",
                    ['tenant_id' => $tenant->getKey()],
                ));
            }
        }

        $expiredTrials = Tenant::query()
            ->whereNull('suspended_at')
            ->where('billing_status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->where(function ($q) {
                $q->whereNull('subscription_ends_at')
                    ->orWhereColumn('subscription_ends_at', '<=', 'trial_ends_at');
            })
            ->limit(100)
            ->get();

        foreach ($expiredTrials as $tenant) {
            $notifier->notifyOperators(
                'trial.expired',
                'Trial expired',
                "{$tenant->name} trial has expired.",
                ['tenant_id' => $tenant->getKey()],
            );
        }

        $this->info('Lifecycle notifications queued.');

        return self::SUCCESS;
    }
}
