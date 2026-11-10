<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Intentionally empty. The shared ~21k BD master catalog is imported separately:
 *
 *   php artisan catalog:import-master storage/app/bd-medicines.csv --from=master
 *
 * Keeping a tiny hard-coded sample here caused SKU/name duplicates against that CSV.
 */
class MasterCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // no-op
    }
}
