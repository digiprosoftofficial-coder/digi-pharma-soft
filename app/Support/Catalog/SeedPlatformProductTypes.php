<?php

namespace App\Support\Catalog;

use App\Domain\Platform\Models\PlatformProductType;
use Illuminate\Support\Facades\DB;

final class SeedPlatformProductTypes
{
    public static function run(): void
    {
        if (PlatformProductType::query()->exists()) {
            return;
        }

        $order = 0;
        foreach (ProductType::cases() as $case) {
            $name = ucfirst(str_replace('_', ' ', $case->value));
            if ($case === ProductType::Other) {
                $name = 'Other';
            }

            DB::table('platform_product_types')->insert([
                'name' => $name,
                'slug' => $case->value,
                'icon_path' => null,
                'sort_order' => $order++,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
