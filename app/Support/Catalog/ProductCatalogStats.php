<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\Product;
use Illuminate\Support\Facades\DB;

final class ProductCatalogStats
{
    /**
     * @return array{
     *     total:int,
     *     active:int,
     *     in_stock:int,
     *     out_of_stock:int,
     *     by_branch:list<array{id:int,name:string,code:string,in_stock:int}>
     * }
     */
    public static function forTenant(?int $tenantId = null): array
    {
        $tenantId = $tenantId ?? tenant_id();
        if ($tenantId === null) {
            return [
                'total' => 0,
                'active' => 0,
                'in_stock' => 0,
                'out_of_stock' => 0,
                'by_branch' => [],
            ];
        }

        $total = (int) Product::query()->count();
        $active = (int) Product::query()->where('is_active', true)->count();
        $inStock = (int) DB::table('product_batches')
            ->where('tenant_id', $tenantId)
            ->where('quantity_on_hand', '>', 0)
            ->distinct()
            ->count('product_id');

        $byBranch = DB::table('branches')
            ->leftJoinSub(
                DB::table('product_batches')
                    ->select('branch_id', DB::raw('COUNT(DISTINCT product_id) as in_stock'))
                    ->where('tenant_id', $tenantId)
                    ->where('quantity_on_hand', '>', 0)
                    ->groupBy('branch_id'),
                'stocked',
                'stocked.branch_id',
                '=',
                'branches.id',
            )
            ->where('branches.tenant_id', $tenantId)
            ->where('branches.is_active', true)
            ->orderByDesc('branches.is_default')
            ->orderBy('branches.name')
            ->get([
                'branches.id',
                'branches.name',
                'branches.code',
                DB::raw('COALESCE(stocked.in_stock, 0) as in_stock'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'code' => (string) $row->code,
                'in_stock' => (int) $row->in_stock,
            ])
            ->values()
            ->all();

        return [
            'total' => $total,
            'active' => $active,
            'in_stock' => $inStock,
            'out_of_stock' => max(0, $total - $inStock),
            'by_branch' => $byBranch,
        ];
    }
}
