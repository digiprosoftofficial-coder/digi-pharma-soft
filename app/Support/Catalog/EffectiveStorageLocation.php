<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Catalog\Models\StorageLocation;

final class EffectiveStorageLocation
{
    /**
     * @return array{id: int, name: string, code: string|null}|null
     */
    public static function forBatch(ProductBatch $batch): ?array
    {
        $location = self::resolve($batch);

        return self::toArray($location);
    }

    public static function resolve(ProductBatch $batch): ?StorageLocation
    {
        if ($batch->relationLoaded('storageLocation') && $batch->storage_location_id !== null) {
            return $batch->storageLocation;
        }

        if ($batch->storage_location_id !== null) {
            $batch->loadMissing('storageLocation');

            return $batch->storageLocation;
        }

        $product = $batch->relationLoaded('product')
            ? $batch->product
            : $batch->product()->with('storageLocation')->first();

        if ($product === null) {
            return null;
        }

        if ($product->relationLoaded('storageLocation')) {
            return $product->storageLocation;
        }

        $product->loadMissing('storageLocation');

        return $product->storageLocation;
    }

    /**
     * @return array{id: int, name: string, code: string|null}|null
     */
    public static function forProduct(Product $product): ?array
    {
        if ($product->relationLoaded('storageLocation')) {
            return self::toArray($product->storageLocation);
        }

        $product->loadMissing('storageLocation');

        return self::toArray($product->storageLocation);
    }

    /**
     * @return array{id: int, name: string, code: string|null}|null
     */
    private static function toArray(?StorageLocation $location): ?array
    {
        if ($location === null) {
            return null;
        }

        return [
            'id' => $location->getKey(),
            'name' => $location->name,
            'code' => $location->code,
        ];
    }
}
