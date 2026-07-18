<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\CatalogProductType;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTypeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_list_product_types(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->post('/product-types', ['name' => 'Ointment', 'sort_order' => 5])
            ->assertRedirect(route('tenant.product-types.index'));

        $this->assertDatabaseHas('product_types', ['name' => 'Ointment', 'slug' => 'ointment']);

        $this->actingAs($user)
            ->get('/product-types')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Catalog/ProductTypes/Index'));
    }

    public function test_cannot_delete_product_type_with_products(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $type = CatalogProductType::query()->where('slug', 'tablet')->firstOrFail();

        $this->actingAs($user)
            ->delete("/product-types/{$type->getKey()}")
            ->assertSessionHasErrors();
    }

    public function test_can_delete_unused_product_type(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $type = CatalogProductType::query()->where('slug', 'injection')->firstOrFail();

        $this->actingAs($user)
            ->delete("/product-types/{$type->getKey()}")
            ->assertRedirect(route('tenant.product-types.index'));

        $this->assertDatabaseMissing('product_types', ['id' => $type->getKey()]);
    }

    public function test_seeded_default_types_exist(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(10, CatalogProductType::query()->count());
        $this->assertDatabaseHas('product_types', ['slug' => 'tablet']);
    }

    public function test_product_form_uses_database_product_types(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get('/products/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('catalogOptions.productTypes')
                ->where('catalogOptions.productTypes', fn ($types) => in_array('tablet', collect($types)->all(), true)));
    }
}
