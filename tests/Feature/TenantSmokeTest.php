<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_owner_can_open_dashboard_and_purchases_list(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Tenant/Dashboard'));

        $this->actingAs($user)
            ->get('/purchases')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Purchases/Index'));
    }

    public function test_barcode_endpoint_returns_png_for_product(): void
    {
        $this->seed();

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $product = \App\Domain\Catalog\Models\Product::query()->firstOrFail();

        $this->actingAs($user)
            ->get('/barcodes/'.$product->getKey())
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }
}
