<?php

namespace Tests\Feature\Marketing;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Platform\Models\OtpVerification;
use App\Domain\Tenant\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfServiceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_and_home_are_public(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')->assertOk();
        $this->get('/pricing')->assertOk();
        $this->get('/get-started')->assertOk();
    }

    public function test_trial_registration_after_otp(): void
    {
        $this->seed(DatabaseSeeder::class);
        $plan = SubscriptionPlan::query()->where('slug', 'starter')->firstOrFail();

        $this->post('/get-started/otp', ['phone' => '01744444444'])->assertRedirect();

        $otp = OtpVerification::query()->where('phone', '01744444444')->latest('id')->firstOrFail();
        $code = null;
        // Recover code from hash is impossible; re-issue via service for test:
        [$row, $plain] = OtpVerification::issue('01744444444');
        $this->post('/get-started/otp/verify', [
            'phone' => '01744444444',
            'code' => $plain,
        ])->assertRedirect();

        $row->refresh();
        $this->assertTrue($row->isVerified());

        $this->post('/get-started', [
            'pharmacy_name' => 'Self Serve Pharmacy',
            'slug' => 'self-serve-pharmacy',
            'owner_name' => 'Nila',
            'owner_email' => 'nila@example.com',
            'owner_password' => 'password123',
            'owner_password_confirmation' => 'password123',
            'phone' => '01744444444',
            'otp_session_token' => $row->session_token,
            'subscription_plan_id' => $plan->getKey(),
            'billing_interval' => 'monthly',
            'start_trial' => true,
        ])->assertRedirect(route('tenant.dashboard'));

        $this->assertTrue(Tenant::query()->where('slug', 'self-serve-pharmacy')->exists());
        $tenant = Tenant::query()->where('slug', 'self-serve-pharmacy')->firstOrFail();
        $this->assertSame('trialing', $tenant->billing_status);
        $this->assertTrue($tenant->isSubscriptionActive());
    }
}
