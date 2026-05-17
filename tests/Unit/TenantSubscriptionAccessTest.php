<?php

namespace Tests\Unit;

use App\Domain\Tenant\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(array $overrides = []): Tenant
    {
        return Tenant::query()->create(array_merge([
            'name' => 'Test Pharmacy',
            'slug' => 'test-'.uniqid(),
            'is_active' => true,
        ], $overrides));
    }

    public function test_blocks_access_when_trial_ended_without_extended_subscription(): void
    {
        $trialEnd = now()->subDay();

        $tenant = $this->makeTenant([
            'trial_ends_at' => $trialEnd,
            'subscription_ends_at' => $trialEnd,
        ]);

        $this->assertFalse($tenant->isSubscriptionActive());
        $this->assertFalse($tenant->hasPaidSubscriptionBeyondTrial());
    }

    public function test_allows_access_during_trial(): void
    {
        $trialEnd = now()->addDays(7);

        $tenant = $this->makeTenant([
            'trial_ends_at' => $trialEnd,
            'subscription_ends_at' => $trialEnd,
        ]);

        $this->assertTrue($tenant->isSubscriptionActive());
    }

    public function test_allows_access_after_trial_when_subscription_extended(): void
    {
        $tenant = $this->makeTenant([
            'trial_ends_at' => now()->subDay(),
            'subscription_ends_at' => now()->addYear(),
        ]);

        $this->assertTrue($tenant->hasPaidSubscriptionBeyondTrial());
        $this->assertTrue($tenant->isSubscriptionActive());
    }
}
