<?php

namespace Tests\Feature\Purchasing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Purchasing\Models\Purchase;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Purchasing\Services\PurchaseReturnService;
use App\Domain\Purchasing\Services\PurchaseService;
use App\Domain\Purchasing\Services\SupplierDueService;
use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\BranchContext;
use App\Support\Tenant\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierDueServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_due_and_total_due_split_across_branches(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $main = Branch::query()->where('is_default', true)->firstOrFail();
        $east = $this->createBranch($tenant, 'East', 'EAST');

        $this->enableSupplierBranchLedger($tenant);
        $this->createPostedPurchase($supplier, $product, $main, 'MAIN-100', 100);
        $this->createPostedPurchase($supplier, $product, $east, 'EAST-50', 50);

        $dues = app(SupplierDueService::class);

        $this->assertSame(100.0, $dues->branchDue($supplier, $main->getKey()));
        $this->assertSame(50.0, $dues->branchDue($supplier, $east->getKey()));
        $this->assertSame(150.0, $dues->totalDue($supplier));

        $breakdown = $dues->breakdownByBranch($supplier);
        $this->assertCount(2, $breakdown);
        $this->assertEqualsCanonicalizing(
            [$main->getKey(), $east->getKey()],
            $breakdown->pluck('branch_id')->all(),
        );
    }

    public function test_return_credit_reduces_branch_due(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $main = Branch::query()->where('is_default', true)->firstOrFail();

        $this->enableSupplierBranchLedger($tenant);
        $this->createPostedPurchase($supplier, $product, $main, 'RET-BASE', 200);

        $batch = ProductBatch::query()
            ->withoutGlobalScope('branch')
            ->where('branch_id', $main->getKey())
            ->where('batch_no', 'RET-BASE')
            ->firstOrFail();

        app(BranchContext::class)->set($main);
        app(PurchaseReturnService::class)->recordReturn($supplier, [[
            'product_batch_id' => $batch->getKey(),
            'quantity' => 5,
            'unit_cost' => 10,
        ]]);

        $dues = app(SupplierDueService::class);
        $this->assertSame(150.0, $dues->branchDue($supplier, $main->getKey()));
        $this->assertSame(150.0, $dues->totalDue($supplier));
    }

    public function test_voided_purchase_excluded_from_due(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = Tenant::query()->firstOrFail();
        $supplier = Supplier::query()->firstOrFail();
        $product = Product::query()->where('sku', 'PAR-500')->firstOrFail();
        $main = Branch::query()->where('is_default', true)->firstOrFail();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->enableSupplierBranchLedger($tenant);
        $purchase = $this->createPostedPurchase($supplier, $product, $main, 'VOID-DUE', 80);
        $dues = app(SupplierDueService::class);

        $this->assertSame(80.0, $dues->totalDue($supplier));

        $this->actingAs($user)->post("/purchases/{$purchase->getKey()}/void")->assertRedirect();

        $this->assertSame(0.0, $dues->totalDue($supplier->fresh()));
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
