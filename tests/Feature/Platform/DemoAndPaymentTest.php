<?php

namespace Tests\Feature\Platform;

use App\Domain\Billing\Models\PlatformPayment;
use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Crm\Models\DemoRequest;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Notifications\PlatformEventNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DemoAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_submit_demo_request(): void
    {
        $this->seed(DatabaseSeeder::class);
        Notification::fake();

        $this->from(route('marketing.home'))
            ->post('/demo', [
                'pharmacy_name' => 'Demo Seekers',
                'owner_name' => 'Rahim',
                'mobile' => '01933333333',
                'email' => 'rahim@example.com',
                'address' => 'Dhaka',
                'message' => 'Please call me',
            ])
            ->assertRedirect(route('marketing.home'));

        $this->assertDatabaseHas('demo_requests', [
            'pharmacy_name' => 'Demo Seekers',
            'mobile' => '01933333333',
            'status' => DemoRequest::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('leads', [
            'pharmacy_name' => 'Demo Seekers',
            'phone' => '01933333333',
            'email' => 'rahim@example.com',
            'address' => 'Dhaka',
        ]);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        Notification::assertSentTo($admin, PlatformEventNotification::class, function (PlatformEventNotification $notification) {
            return $notification->event === 'demo.requested';
        });
    }

    public function test_demo_request_rejects_invalid_mobile_and_email(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->from(route('marketing.home'))
            ->post('/demo', [
                'pharmacy_name' => 'Bad Contact',
                'owner_name' => 'Karim',
                'mobile' => '01234567890',
                'email' => 'not-an-email',
                'address' => 'Dhaka',
            ])
            ->assertRedirect(route('marketing.home'))
            ->assertSessionHasErrors(['mobile', 'email']);

        $this->assertDatabaseMissing('demo_requests', [
            'pharmacy_name' => 'Bad Contact',
        ]);
    }

    public function test_super_admin_can_delete_demo_request(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $demo = DemoRequest::query()->create([
            'pharmacy_name' => 'Delete Demo Pharmacy',
            'owner_name' => 'Owner',
            'mobile' => '01900000088',
            'branch_count' => 1,
            'status' => DemoRequest::STATUS_PENDING,
            'message' => 'Remove me',
        ]);

        $this->actingAs($admin)
            ->delete("/platform/demos/{$demo->getKey()}")
            ->assertRedirect(route('platform.demos.index'));

        $this->assertDatabaseMissing('demo_requests', ['id' => $demo->getKey()]);
    }

    public function test_payment_verify_activates_subscription_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->where('slug', 'demo-pharmacy')->firstOrFail();
        $plan = SubscriptionPlan::query()->where('slug', 'professional')->firstOrFail();

        $tenant->forceFill([
            'trial_ends_at' => now()->subDay(),
            'subscription_ends_at' => now()->subDay(),
            'billing_status' => 'past_due',
        ])->save();

        $service = app(\App\Domain\Billing\Services\PlatformPaymentService::class);
        $payment = $service->submit($tenant, [
            'method' => PlatformPayment::METHOD_BKASH,
            'transaction_reference' => 'TXN-UNIQUE-001',
            'billing_interval' => 'monthly',
            'subscription_plan_id' => $plan->getKey(),
            'idempotency_key' => 'idem-1',
        ], $admin);

        $again = $service->submit($tenant, [
            'method' => PlatformPayment::METHOD_BKASH,
            'transaction_reference' => 'TXN-OTHER',
            'billing_interval' => 'monthly',
            'subscription_plan_id' => $plan->getKey(),
            'idempotency_key' => 'idem-1',
        ], $admin);

        $this->assertTrue($payment->is($again));

        $this->actingAs($admin)
            ->post("/platform/payments/{$payment->getKey()}/verify")
            ->assertRedirect();

        $payment->refresh();
        $tenant->refresh();
        $this->assertSame(PlatformPayment::STATUS_VERIFIED, $payment->status);
        $this->assertSame('active', $tenant->billing_status);
        $this->assertTrue($tenant->subscription_ends_at?->isFuture());
    }

    public function test_sales_can_verify_payment(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $tenant = Tenant::query()->where('slug', 'demo-pharmacy')->firstOrFail();
        $plan = SubscriptionPlan::query()->where('slug', 'professional')->firstOrFail();

        $this->actingAs($admin)->post('/platform/admins', [
            'name' => 'Sales Pay',
            'email' => 'sales-pay@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'sales',
        ])->assertRedirect();

        $sales = User::query()->where('email', 'sales-pay@example.com')->firstOrFail();

        $payment = app(\App\Domain\Billing\Services\PlatformPaymentService::class)->submit($tenant, [
            'method' => PlatformPayment::METHOD_BKASH,
            'transaction_reference' => 'TXN-SALES-001',
            'billing_interval' => 'monthly',
            'subscription_plan_id' => $plan->getKey(),
            'idempotency_key' => 'idem-sales-1',
        ], $admin);

        $this->actingAs($sales)
            ->post("/platform/payments/{$payment->getKey()}/verify")
            ->assertRedirect();

        $this->assertSame(PlatformPayment::STATUS_VERIFIED, $payment->fresh()->status);
    }
}
