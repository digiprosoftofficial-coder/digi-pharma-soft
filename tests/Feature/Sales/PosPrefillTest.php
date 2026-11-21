<?php

namespace Tests\Feature\Sales;

use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PosPrefillTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_accepts_product_ids_prefill(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $products = Product::query()->where('is_active', true)->orderBy('id')->take(2)->get();
        $this->assertGreaterThanOrEqual(2, $products->count());

        $ids = $products->pluck('id')->implode(',');

        $this->actingAs($user)
            ->get('/pos?product_ids='.$ids)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pos/Index')
                ->has('prefillProducts', 2)
                ->where('prefillProducts.0.id', $products[0]->getKey())
                ->where('prefillProducts.1.id', $products[1]->getKey())
            );
    }

    public function test_pos_prefill_ignores_invalid_ids(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/pos?product_ids=0,abc,-5')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Pos/Index')
                ->has('prefillProducts', 0)
            );
    }
}
