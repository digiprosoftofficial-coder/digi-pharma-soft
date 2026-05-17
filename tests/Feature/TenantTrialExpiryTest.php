<?php

namespace Tests\Feature;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTrialExpiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_staff_blocked_when_trial_ended_without_paid_subscription(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->findOrFail($user->tenant_id);

        $trialEnd = now()->subDay();
        $tenant->update([
            'trial_ends_at' => $trialEnd,
            'subscription_ends_at' => $trialEnd,
            'is_active' => true,
            'suspended_at' => null,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }
}
