<?php

namespace Tests\Feature\Marketing;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Platform\Models\OtpVerification;
use App\Domain\Tenant\Models\Tenant;
use App\Mail\RegistrationOtpMail;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SelfServiceRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_and_home_are_public(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Marketing/Home')
                ->where('showTryLiveDemo', true));
        $this->get('/pricing')->assertOk();
        $this->get('/get-started')->assertOk();
    }

    public function test_trial_registration_after_email_otp(): void
    {
        $this->seed(DatabaseSeeder::class);
        Mail::fake();
        $plan = SubscriptionPlan::query()->where('slug', 'starter')->firstOrFail();

        $this->post('/get-started/otp', ['owner_email' => 'nila@example.com'])->assertRedirect();
        Mail::assertSent(RegistrationOtpMail::class);

        [$row, $plain] = OtpVerification::issue('nila@example.com');
        $this->post('/get-started/otp/verify', [
            'owner_email' => 'nila@example.com',
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

    public function test_registration_rejects_invalid_phone(): void
    {
        $this->seed(DatabaseSeeder::class);
        $plan = SubscriptionPlan::query()->where('slug', 'starter')->firstOrFail();
        [$row, $plain] = OtpVerification::issue('badphone@example.com');
        $this->post('/get-started/otp/verify', [
            'owner_email' => 'badphone@example.com',
            'code' => $plain,
        ])->assertRedirect();

        $this->from('/get-started')
            ->post('/get-started', [
                'pharmacy_name' => 'Bad Phone Pharmacy',
                'owner_name' => 'Owner',
                'owner_email' => 'badphone@example.com',
                'owner_password' => 'password123',
                'owner_password_confirmation' => 'password123',
                'phone' => '12345',
                'otp_session_token' => $row->fresh()->session_token,
                'subscription_plan_id' => $plan->getKey(),
                'billing_interval' => 'monthly',
                'start_trial' => true,
            ])
            ->assertSessionHasErrors('phone');
    }
}
