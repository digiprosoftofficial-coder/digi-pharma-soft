<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_list_categories(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->actingAs($user)
            ->post('/categories', ['name' => 'Antibiotics'])
            ->assertRedirect(route('tenant.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Antibiotics', 'slug' => 'antibiotics']);

        $this->actingAs($user)
            ->get('/categories')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Catalog/Categories/Index'));
    }

    public function test_cannot_delete_category_with_products(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $category = Category::query()->firstOrFail();
        Product::query()->whereKey(Product::query()->value('id'))->update(['category_id' => $category->getKey()]);

        $this->actingAs($user)
            ->delete("/categories/{$category->getKey()}")
            ->assertSessionHasErrors();
    }
}
