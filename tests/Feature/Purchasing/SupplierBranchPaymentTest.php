<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\PurchasePayment;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\PurchaseService;
use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\BranchContext;
use App\Support\Tenant\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SupplierBranchPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_open_invoices_from_all_branches(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $main = Branch::query()->where('is_default', true)->firstOrFail();
        $east = $this->createBranch($tenant, 'East', 'EAST');

        $this->enableSupplierBranchLedger($tenant);
        $mainPurchase = $this->createPostedPurchase($supplier, $product, $main, 'OWN-MAIN', 100);
        $eastPurchase = $this->createPostedPurchase($supplier, $product, $east, 'OWN-EAST', 60);

        $this->actingAs($owner)
            ->get("/purchases/supplier-bills/{$supplier->getKey()}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchases/SupplierBillShow')
                ->where('viewAllBranches', true)
                ->has('openPurchases', 2)
                ->where('supplier.open_due', 160)
            );

        $this->assertNotNull($mainPurchase->getKey());
        $this->assertNotNull($eastPurchase->getKey());
    }

    public function test_manager_sees_only_active_branch_invoices(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $manager = $this->createManager($tenant);
        $supplier = Supplier::query()->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $main = Branch::query()->where('is_default', true)->firstOrFail();
        $east = $this->createBranch($tenant, 'East', 'EAST');

        $this->enableSupplierBranchLedger($tenant);
        $this->createPostedPurchase($supplier, $product, $main, 'MGR-MAIN', 100);
        $eastPurchase = $this->createPostedPurchase($supplier, $product, $east, 'MGR-EAST', 40);

        $this->actingAs($manager);
        $this->post('/branches/switch', ['branch_id' => $east->getKey()])->assertRedirect();

        $this->get("/purchases/supplier-bills/{$supplier->getKey()}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Purchases/SupplierBillShow')
                ->where('viewAllBranches', false)
                ->has('openPurchases', 1)
                ->where('openPurchases.0.invoice_no', $eastPurchase->invoice_no)
                ->where('supplier.open_due', 40)
            );
    }

    public function test_cross_branch_payment_records_paying_branch(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $main = Branch::query()->where('is_default', true)->firstOrFail();
        $east = $this->createBranch($tenant, 'East', 'EAST');

        $this->enableSupplierBranchLedger($tenant);
        $purchase = $this->createPostedPurchase($supplier, $product, $main, 'XBR-PAY', 100);

        $this->actingAs($owner)
            ->post('/branches/switch', ['branch_id' => $east->getKey()])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post("/purchases/{$purchase->getKey()}/payments", [
                'method' => 'cash',
                'amount' => 25,
            ])
            ->assertRedirect(route('tenant.purchases.show', $purchase));

        $payment = PurchasePayment::query()->where('purchase_id', $purchase->getKey())->latest('id')->firstOrFail();
        $this->assertSame($east->getKey(), (int) $payment->paying_branch_id);
        $this->assertSame($main->getKey(), (int) $purchase->fresh()->branch_id);
    }

    public function test_cross_branch_payment_rejected_when_disabled_in_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenant = Tenant::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $main = Branch::query()->where('is_default', true)->firstOrFail();
        $east = $this->createBranch($tenant, 'East', 'EAST');

        $this->enableSupplierBranchLedger($tenant);
        $settings = $tenant->settings ?? [];
        $settings['supplier_payments'] = ['cross_branch' => false, 'managers_can_pay' => false];
        $tenant->settings = $settings;
        $tenant->save();

        $purchase = $this->createPostedPurchase($supplier, $product, $main, 'XBR-OFF', 80);

        $this->actingAs($owner)
            ->post('/branches/switch', ['branch_id' => $east->getKey()])
            ->assertRedirect();

        $this->actingAs($owner)
            ->from("/purchases/{$purchase->getKey()}")
            ->post("/purchases/{$purchase->getKey()}/payments", [
                'method' => 'cash',
                'amount' => 10,
            ])
            ->assertSessionHasErrors();
    }

    public function test_manager_cannot_pay_when_policy_disabled(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $manager = $this->createManager($tenant);
        $supplier = Supplier::query()->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $main = Branch::query()->where('is_default', true)->firstOrFail();

        $this->enableSupplierBranchLedger($tenant);
        $purchase = $this->createPostedPurchase($supplier, $product, $main, 'MGR-NO-PAY', 50);

        $this->actingAs($manager);
        $this->post("/purchases/{$purchase->getKey()}/payments", [
            'method' => 'cash',
            'amount' => 10,
        ])->assertForbidden();
    }

    public function test_manager_can_pay_when_policy_enabled(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $manager = $this->createManager($tenant);
        $supplier = Supplier::query()->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $main = Branch::query()->where('is_default', true)->firstOrFail();

        $this->enableSupplierBranchLedger($tenant);
        $settings = $tenant->settings ?? [];
        $settings['supplier_payments'] = ['cross_branch' => true, 'managers_can_pay' => true];
        $tenant->settings = $settings;
        $tenant->save();

        $purchase = $this->createPostedPurchase($supplier, $product, $main, 'MGR-PAY', 50);

        $this->actingAs($manager);
        $this->post("/purchases/{$purchase->getKey()}/payments", [
            'method' => 'cash',
            'amount' => 20,
        ])->assertRedirect(route('tenant.purchases.show', $purchase));

        $purchase->refresh();
        $this->assertSame('30.0000', (string) $purchase->due);
    }

    private function enableSupplierBranchLedger(Tenant $tenant): void
    {
        $plan = $tenant->activeSubscription?->plan;
        $this->assertNotNull($plan);

        $plan->features = array_merge($plan->features ?? [], [
            'multi_branch' => true,
            'supplier_branch_ledger' => true,
        ]);
        $plan->limits = array_merge($plan->limits ?? [], ['max_branches' => 5]);
        $plan->save();
        $tenant->refresh();
    }

    private function createBranch(Tenant $tenant, string $name, string $code): Branch
    {
        return Branch::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => $name,
            'code' => $code,
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    private function createManager(Tenant $tenant): User
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenant->getKey());

        $manager = User::query()->create([
            'name' => 'Branch Manager',
            'email' => 'manager-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->getKey(),
            'is_platform_super_admin' => false,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('manager');
        $manager->forceFill(['email_verified_at' => now()])->save();

        return $manager;
    }

    private function createPostedPurchase(
        Supplier $supplier,
        Product $product,
        Branch $branch,
        string $batchNo,
        float $total,
    ): Purchase {
        $tenant = Tenant::query()->findOrFail($supplier->tenant_id);
        app(TenantContext::class)->set($tenant);
        app(BranchContext::class)->set($branch);

        $qty = 10;
        $unitCost = $total / $qty;

        return app(PurchaseService::class)->recordPurchase(
            $supplier,
            'INV-'.$batchNo,
            now()->toDateString(),
            [[
                'product_id' => $product->getKey(),
                'batch_no' => $batchNo,
                'expiry_date' => '2028-06-01',
                'quantity' => $qty,
                'sell_unit' => 'strip',
                'unit_cost' => $unitCost,
            ]],
        );
    }
}
