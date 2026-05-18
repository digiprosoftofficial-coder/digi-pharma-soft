<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\Manufacturer;
use Illuminate\Validation\ValidationException;

final class ManufacturerService
{
    /**
     * @param  array{name:string}  $data
     */
    public function create(array $data): Manufacturer
    {
        return Manufacturer::query()->create(['name' => $data['name']]);
    }

    /**
     * @param  array{name?:string}  $data
     */
    public function update(Manufacturer $manufacturer, array $data): Manufacturer
    {
        $manufacturer->update(['name' => $data['name'] ?? $manufacturer->name]);

        return $manufacturer->fresh();
    }

    public function delete(Manufacturer $manufacturer): void
    {
        if ($manufacturer->products()->exists()) {
            throw ValidationException::withMessages([
                'manufacturer' => [__('catalog.manufacturer_has_products')],
            ]);
        }

        $manufacturer->delete();
    }
}
