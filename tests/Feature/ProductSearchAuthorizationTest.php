<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ProductSearchAuthorizationTest extends TestCase
{
    public function test_product_search_requires_authentication(): void
    {
        $response = $this->get('/catalog/product-search?q=pa');

        $response->assertRedirect(route('login'));
    }

    public function test_product_search_requires_permission(): void
    {
        $user = User::factory()->create([
            'tenant_id' => null,
            'is_platform_super_admin' => false,
        ]);

        $response = $this->actingAs($user)->getJson('/catalog/product-search?q=pa');

        $response->assertForbidden();
    }
}
