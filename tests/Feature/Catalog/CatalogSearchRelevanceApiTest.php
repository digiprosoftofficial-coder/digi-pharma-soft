<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\MasterProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchRelevanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_search_puts_brand_match_before_generic_substring(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        MasterProduct::query()->create([
            'sku' => 'MSTR-BEXIDAL-NAPA',
            'name' => 'Bexidal 50 mg',
            'generic_name' => 'Mebhydrolin Napadisylate',
            'strength' => '50 mg',
            'manufacturer_name' => 'Beximco',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'is_active' => true,
        ]);
        MasterProduct::query()->create([
            'sku' => 'MSTR-NAPA-500',
            'name' => 'Napa 500 mg',
            'generic_name' => 'Paracetamol',
            'strength' => '500 mg',
            'manufacturer_name' => 'Beximco',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'is_active' => true,
        ]);
        MasterProduct::query()->create([
            'sku' => 'MSTR-LONAPAM-1',
            'name' => 'Lonapam 1 mg',
            'generic_name' => 'Clonazepam',
            'strength' => '1 mg',
            'manufacturer_name' => 'Other',
            'product_type' => 'tablet',
            'base_unit' => 'strip',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/catalog/master/search?q=napa');
        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertNotEmpty($names);
        $this->assertSame('Napa 500 mg', $names[0]);
        $this->assertContains('Bexidal 50 mg', $names);
        $this->assertTrue(
            array_search('Napa 500 mg', $names, true) < array_search('Bexidal 50 mg', $names, true)
        );
    }
}
