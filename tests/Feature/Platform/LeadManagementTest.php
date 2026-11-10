<?php

namespace Tests\Feature\Platform;

use App\Domain\Billing\Models\SubscriptionPlan;
use App\Domain\Crm\Models\Lead;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Permission\PlatformTeam;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
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

    public function test_super_admin_can_delete_lead(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $lead = Lead::query()->create([
            'pharmacy_name' => 'Delete Me Pharmacy',
            'owner_name' => 'Owner',
            'phone' => '01700000099',
            'branch_count' => 1,
            'source' => 'other',
            'status' => Lead::STATUS_NEW,
            'created_by' => $admin->getKey(),
        ]);

        $this->actingAs($admin)
            ->delete("/platform/leads/{$lead->getKey()}")
            ->assertRedirect(route('platform.leads.index'));

        $this->assertDatabaseMissing('leads', ['id' => $lead->getKey()]);
    }

    public function test_sales_cannot_set_trial_started_or_convert(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $plan = SubscriptionPlan::query()->where('slug', 'starter')->firstOrFail();

        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId(PlatformTeam::ID);
        try {
            $sales = User::query()->create([
                'name' => 'Sales Rep',
                'email' => 'sales-lead@example.com',
                'password' => 'password',
                'tenant_id' => null,
            ]);
            $sales->forceFill(['email_verified_at' => now()])->save();
            $sales->assignRole('sales');
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }

        $lead = Lead::query()->create([
            'pharmacy_name' => 'Pipeline Only Pharmacy',
            'owner_name' => 'Owner',
            'phone' => '01700000111',
            'email' => 'pipeline@example.com',
            'branch_count' => 1,
            'source' => 'website',
            'interested_plan_id' => $plan->getKey(),
            'status' => Lead::STATUS_DEMO_COMPLETED,
            'created_by' => $admin->getKey(),
        ]);

        $this->actingAs($sales)
            ->from(route('platform.leads.show', $lead))
            ->put("/platform/leads/{$lead->getKey()}", [
                'status' => Lead::STATUS_TRIAL_STARTED,
            ])
            ->assertSessionHasErrors('status');

        $lead->refresh();
        $this->assertSame(Lead::STATUS_DEMO_COMPLETED, $lead->status);

        $this->assertFalse($sales->fresh()->isPlatformSuperAdminRole());

        $this->actingAs($sales->fresh())
            ->from(route('platform.leads.show', $lead))
            ->post("/platform/leads/{$lead->getKey()}/convert", [
                'subscription_plan_id' => $plan->getKey(),
                'owner_email' => 'pipeline@example.com',
                'owner_invite' => true,
            ])
            ->assertRedirect();

        $lead->refresh();
        $this->assertSame(Lead::STATUS_DEMO_COMPLETED, $lead->status);
        $this->assertNull($lead->converted_tenant_id);
        $this->assertFalse(Tenant::query()->where('lead_id', $lead->getKey())->exists());
    }
}
