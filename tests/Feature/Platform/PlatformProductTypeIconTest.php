<?php

namespace Tests\Feature\Platform;

use App\Domain\Catalog\Models\CatalogProductType;
use App\Domain\Platform\Models\PlatformProductType;
use App\Models\User;
use App\Support\Catalog\PlatformProductTypeIconStorage;
use App\Support\Catalog\ProductTypeIconResolver;
use App\Support\Catalog\SeedDefaultProductTypes;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformProductTypeIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_upload_platform_product_type_icon(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $type = PlatformProductType::query()->where('slug', 'tablet')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('platform.product-types.update', $type), [
                'name' => $type->name,
                'slug' => $type->slug,
                'sort_order' => $type->sort_order,
                'is_active' => true,
                'icon' => UploadedFile::fake()->image('tablet.png', 64, 64),
            ])
            ->assertRedirect(route('platform.product-types.index'));

        $type->refresh();
        $this->assertNotNull($type->icon_path);
        Storage::disk('public')->assertExists($type->icon_path);
        $this->assertNotNull(PlatformProductTypeIconStorage::url($type->icon_path));
    }

    public function test_new_tenant_receives_copied_icon_from_platform_on_seed(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $platform = PlatformProductType::query()->where('slug', 'tablet')->firstOrFail();
        $path = UploadedFile::fake()->image('tablet.png')->store('platform/product-type-icons', 'public');
        $platform->update(['icon_path' => $path]);

        $owner = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $tenantId = (int) $owner->tenant_id;

        CatalogProductType::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->delete();
        SeedDefaultProductTypes::forTenant($tenantId);

        $tenantType = CatalogProductType::query()->where('slug', 'tablet')->firstOrFail();
        $this->assertNotNull($tenantType->icon_path);
        Storage::disk('public')->assertExists($tenantType->icon_path);
    }

    public function test_tenant_can_override_product_type_icon(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $type = CatalogProductType::query()->where('slug', 'capsule')->firstOrFail();

        $this->actingAs($user)
            ->put("/product-types/{$type->getKey()}", [
                'name' => $type->name,
                'slug' => $type->slug,
                'sort_order' => $type->sort_order,
                'icon' => UploadedFile::fake()->image('capsule-custom.png', 64, 64),
            ])
            ->assertRedirect(route('tenant.product-types.index'));

        $type->refresh();
        $this->assertNotNull($type->icon_path);
        $this->assertTrue($type->uses_custom_icon);
    }

    public function test_tenant_remove_icon_falls_back_to_platform_url(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $platform = PlatformProductType::query()->where('slug', 'syrup')->firstOrFail();
        $platformPath = UploadedFile::fake()->image('syrup.png')->store('platform/product-type-icons', 'public');
        $platform->update(['icon_path' => $platformPath]);

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $type = CatalogProductType::query()->where('slug', 'syrup')->firstOrFail();
        $customPath = UploadedFile::fake()->image('custom.png')->store('product-types/1', 'public');
        $type->update(['icon_path' => $customPath]);

        $this->actingAs($user)
            ->put("/product-types/{$type->getKey()}", [
                'name' => $type->name,
                'slug' => $type->slug,
                'remove_icon' => true,
            ])
            ->assertRedirect(route('tenant.product-types.index'));

        $type->refresh();
        $this->assertNull($type->icon_path);
        $this->assertSame(
            PlatformProductTypeIconStorage::url($platformPath),
            ProductTypeIconResolver::urlForTenantType($type),
        );
    }
}
