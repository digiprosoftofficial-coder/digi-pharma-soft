<?php

namespace Tests\Feature\Platform;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Crm\Models\Lead;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_and_list_leads(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->where('slug', 'starter')->firstOrFail();

        $this->actingAs($admin)
            ->post('/platform/leads', [
                'pharmacy_name' => 'Green Care Pharmacy',
                'owner_name' => 'Karim',
                'phone' => '01711111111',
                'branch_count' => 2,
                'source' => 'facebook',
                'interested_plan_id' => $plan->getKey(),
                'status' => 'new',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('leads', [
            'pharmacy_name' => 'Green Care Pharmacy',
            'phone' => '01711111111',
        ]);

        $this->actingAs($admin)
            ->get('/platform/leads')
            ->assertOk();
    }

    public function test_lead_can_be_converted_to_tenant(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->where('slug', 'professional')->firstOrFail();

        $lead = Lead::query()->create([
            'pharmacy_name' => 'Convert Me Pharmacy',
            'owner_name' => 'Owner',
            'phone' => '01822222222',
            'email' => 'convert-owner@example.com',
            'branch_count' => 1,
            'source' => 'referral',
            'interested_plan_id' => $plan->getKey(),
            'status' => Lead::STATUS_DEMO_COMPLETED,
            'created_by' => $admin->getKey(),
        ]);

        $this->actingAs($admin)
            ->post("/platform/leads/{$lead->getKey()}/convert", [
                'slug' => 'convert-me-pharmacy',
                'subscription_plan_id' => $plan->getKey(),
                'owner_email' => 'convert-owner@example.com',
                'owner_invite' => true,
                'billing_interval' => 'monthly',
            ])
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame(Lead::STATUS_CONVERTED, $lead->status);
        $this->assertNotNull($lead->converted_tenant_id);
        $this->assertTrue(Tenant::query()->where('slug', 'convert-me-pharmacy')->exists());
    }
}
