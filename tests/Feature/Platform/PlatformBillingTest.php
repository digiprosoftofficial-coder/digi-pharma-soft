<?php

namespace Tests\Feature\Platform;

use App\Domain\Billing\Models\PlatformInvoice;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PlatformBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_billing_dashboard_with_mrr(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('platform.billing.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Platform/Billing/Index')
                ->has('metrics.mrr_cents')
                ->has('invoices'));
    }

    public function test_super_admin_can_create_mark_paid_and_fail_invoice(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.billing.invoices.store'), [
                'tenant_id' => $tenant->getKey(),
            ])
            ->assertRedirect(route('platform.billing.index'));

        $invoice = PlatformInvoice::query()->where('tenant_id', $tenant->getKey())->first();
        $this->assertNotNull($invoice);
        $this->assertSame(PlatformInvoice::STATUS_OPEN, $invoice->status);
        $this->assertSame(4900, $invoice->amount_cents);

        $this->actingAs($admin)
            ->post(route('platform.billing.invoices.store'), [
                'tenant_id' => $tenant->getKey(),
                'amount_cents' => 12550,
            ])
            ->assertRedirect(route('platform.billing.index'));

        $custom = PlatformInvoice::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('amount_cents', 12550)
            ->first();
        $this->assertNotNull($custom);

        $this->actingAs($admin)
            ->post(route('platform.billing.invoices.failed', $invoice), [
                'reason' => 'Card declined',
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame('past_due', $tenant->billing_status);
        $this->assertNotNull($tenant->grace_period_ends_at);

        $invoice->refresh();
        $this->assertSame(PlatformInvoice::STATUS_UNCOLLECTIBLE, $invoice->status);

        $invoice2 = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->getKey(),
            'invoice_no' => 'INV-TEST-0002',
            'amount_cents' => 4900,
            'currency' => 'BDT',
            'status' => PlatformInvoice::STATUS_OPEN,
            'provider' => 'manual',
        ]);

        $this->actingAs($admin)
            ->post(route('platform.billing.invoices.paid', $invoice2))
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame('active', $tenant->billing_status);
        $this->assertNull($tenant->payment_failed_at);
    }

    public function test_delinquent_command_suspends_after_grace(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $tenant->billing_status = 'past_due';
        $tenant->payment_failed_at = now()->subDays(10);
        $tenant->grace_period_ends_at = now()->subDay();
        $tenant->save();

        Artisan::call('platform:suspend-payment-delinquent');

        $tenant->refresh();
        $this->assertNotNull($tenant->suspended_at);
    }

    public function test_tenant_owner_cannot_access_billing(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('platform.billing.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_download_edit_and_void_open_invoice(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.billing.invoices.store'), [
                'tenant_id' => $tenant->getKey(),
                'amount_cents' => 5000,
            ])
            ->assertRedirect(route('platform.billing.index'));

        $invoice = PlatformInvoice::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('amount_cents', 5000)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('platform.billing.invoices.preview', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_no, false);

        $this->actingAs($admin)
            ->get(route('platform.billing.invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($admin)
            ->put(route('platform.billing.invoices.update', $invoice), [
                'amount_cents' => 7500,
                'due_at' => now()->addDays(14)->toDateString(),
            ])
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(7500, $invoice->amount_cents);
        $this->assertSame(PlatformInvoice::STATUS_OPEN, $invoice->status);

        $this->actingAs($admin)
            ->delete(route('platform.billing.invoices.destroy', $invoice))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertSame(PlatformInvoice::STATUS_VOID, $invoice->status);
    }

    public function test_super_admin_cannot_edit_or_void_paid_invoice(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->getKey(),
            'invoice_no' => 'INV-TEST-PAID-01',
            'amount_cents' => 4900,
            'currency' => 'BDT',
            'status' => PlatformInvoice::STATUS_PAID,
            'provider' => 'manual',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('platform.billing.invoices.update', $invoice), [
                'amount_cents' => 9900,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('platform.billing.invoices.destroy', $invoice))
            ->assertForbidden();

        $invoice->refresh();
        $this->assertSame(4900, $invoice->amount_cents);
        $this->assertSame(PlatformInvoice::STATUS_PAID, $invoice->status);
    }
}
