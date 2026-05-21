<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\StorageLocation;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantContext;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StorageLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_storage_locations(): void
    {
        $this->seed(DatabaseSeeder::class);

        Permission::query()->firstOrCreate(['name' => 'storage_locations.view', 'guard_name' => 'web']);
        Permission::query()->firstOrCreate(['name' => 'storage_locations.manage', 'guard_name' => 'web']);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $owner->givePermissionTo(['storage_locations.view', 'storage_locations.manage']);

        $this->actingAs($owner)
            ->get(route('tenant.storage-locations.index'))
            ->assertOk();

        $this->actingAs($owner)
            ->post(route('tenant.storage-locations.store'), [
                'name' => 'Rack A — Shelf 3',
                'code' => 'A3',
                'is_active' => true,
            ])
            ->assertRedirect(route('tenant.storage-locations.index'));

        $this->assertDatabaseHas('storage_locations', [
            'name' => 'Rack A — Shelf 3',
            'code' => 'A3',
        ]);
    }

    public function test_cannot_delete_location_in_use(): void
    {
        $this->seed(DatabaseSeeder::class);

        Permission::query()->firstOrCreate(['name' => 'storage_locations.manage', 'guard_name' => 'web']);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $owner->givePermissionTo('storage_locations.manage');

        app(TenantContext::class)->set(Tenant::query()->findOrFail($owner->tenant_id));
        $this->actingAs($owner);

        $location = StorageLocation::query()->create([
            'name' => 'Rack B',
            'code' => 'B1',
            'is_active' => true,
        ]);

        $product = Product::query()->firstOrFail();
        $product->update(['storage_location_id' => $location->getKey()]);

        $this->delete(route('tenant.storage-locations.destroy', $location))
            ->assertSessionHasErrors('storage_location');

        $this->assertDatabaseHas('storage_locations', ['id' => $location->getKey()]);
    }
}
