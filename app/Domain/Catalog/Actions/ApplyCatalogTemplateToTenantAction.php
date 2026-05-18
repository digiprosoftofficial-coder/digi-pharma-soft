<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Platform\Models\CatalogTemplate;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ApplyCatalogTemplateToTenantAction
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {}

    public function execute(CatalogTemplate $template, Tenant $tenant, User $causer): int
    {
        if (! $template->is_published) {
            throw new \InvalidArgumentException('Only published catalog templates can be applied.');
        }

        $template->load('items');

        if ($template->items->isEmpty()) {
            throw new \InvalidArgumentException('This catalog template has no products.');
        }

        return DB::transaction(function () use ($template, $tenant, $causer) {
            $this->tenantContext->set($tenant);

            $category = Category::query()->withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $tenant->getKey(), 'slug' => 'catalog-import'],
                ['name' => 'Catalog import'],
            );

            $created = 0;

            foreach ($template->items as $item) {
                $manufacturerId = null;
                if (filled($item->manufacturer_name)) {
                    $manufacturer = Manufacturer::query()->withoutGlobalScopes()->firstOrCreate(
                        ['tenant_id' => $tenant->getKey(), 'name' => $item->manufacturer_name],
                    );
                    $manufacturerId = $manufacturer->getKey();
                }

                $sku = $this->uniqueSku($tenant->getKey(), $item->sku);

                $exists = Product::query()->withoutGlobalScopes()
                    ->where('tenant_id', $tenant->getKey())
                    ->where('sku', $sku)
                    ->exists();

                if ($exists) {
                    continue;
                }

                Product::query()->withoutGlobalScopes()->create([
                    'tenant_id' => $tenant->getKey(),
                    'category_id' => $category->getKey(),
                    'manufacturer_id' => $manufacturerId,
                    'name' => $item->name,
                    'sku' => $sku,
                    'barcode' => $item->barcode,
                    'unit' => $item->unit,
                    'purchase_price' => $item->purchase_price,
                    'sale_price' => $item->sale_price,
                    'min_stock' => 0,
                    'is_active' => true,
                ]);

                $created++;
            }

            activity()
                ->causedBy($causer)
                ->performedOn($tenant)
                ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
                ->withProperties([
                    'template_id' => $template->getKey(),
                    'template_slug' => $template->slug,
                    'products_created' => $created,
                ])
                ->event('catalog.template_applied')
                ->log('Central catalog template applied to pharmacy');

            return $created;
        });
    }

    private function uniqueSku(int $tenantId, string $baseSku): string
    {
        $sku = Str::upper($baseSku);
        $candidate = $sku;
        $suffix = 1;

        while (Product::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('sku', $candidate)
            ->exists()) {
            $candidate = $sku.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
