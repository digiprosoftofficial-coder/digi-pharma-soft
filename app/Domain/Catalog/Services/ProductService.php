<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ProductService
{
    public function __construct(private readonly ProductRepository $products) {}

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $sku = $data['sku'];
            $barcode = $data['barcode'] ?? null;
            if ($barcode === null || $barcode === '') {
                $barcode = 'BC-'.Str::upper(Str::slug($sku, '')).'-'.Str::upper(Str::random(4));
            }

            return $this->products->store([
                'category_id' => $data['category_id'] ?? null,
                'manufacturer_id' => $data['manufacturer_id'] ?? null,
                'name' => $data['name'],
                'sku' => $sku,
                'barcode' => $barcode,
                'unit' => $data['unit'] ?? 'pcs',
                'purchase_price' => $data['purchase_price'] ?? 0,
                'sale_price' => $data['sale_price'] ?? 0,
                'min_stock' => $data['min_stock'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function updateProduct(Product $product, array $data): Product
    {
        DB::transaction(function () use ($product, $data) {
            $this->products->update($product, [
                'category_id' => $data['category_id'] ?? $product->category_id,
                'manufacturer_id' => $data['manufacturer_id'] ?? $product->manufacturer_id,
                'name' => $data['name'] ?? $product->name,
                'sku' => $data['sku'] ?? $product->sku,
                'barcode' => array_key_exists('barcode', $data) ? $data['barcode'] : $product->barcode,
                'unit' => $data['unit'] ?? $product->unit,
                'purchase_price' => $data['purchase_price'] ?? $product->purchase_price,
                'sale_price' => $data['sale_price'] ?? $product->sale_price,
                'min_stock' => $data['min_stock'] ?? $product->min_stock,
                'is_active' => $data['is_active'] ?? $product->is_active,
            ]);
        });

        return $product->fresh();
    }
}
