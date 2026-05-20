<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\Product;
use Illuminate\Support\Str;

final class ProductSkuGenerator
{
    private const PREFIX = 'PRD-';

    public static function generate(?int $tenantId = null): string
    {
        $tenantId ??= tenant_id();

        $latest = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('sku', 'like', self::PREFIX.'%')
            ->orderByDesc('id')
            ->value('sku');

        $next = 1;
        if (is_string($latest) && preg_match('/^'.preg_quote(self::PREFIX, '/').'(\d+)$/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        do {
            $sku = self::PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (
            Product::query()
                ->where('tenant_id', $tenantId)
                ->where('sku', $sku)
                ->exists()
        );

        return $sku;
    }

    /**
     * Suggest SKU from product name when useful for imports (not used for auto-save).
     */
    public static function suggestFromName(string $name): string
    {
        $slug = Str::upper(Str::slug($name, '-'));

        return $slug !== '' ? Str::limit($slug, 32, '') : self::generate();
    }
}
