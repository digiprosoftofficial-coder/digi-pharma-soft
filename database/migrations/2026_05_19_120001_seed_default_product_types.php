<?php

use App\Domain\Tenant\Models\Tenant;
use App\Support\Catalog\SeedDefaultProductTypes;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Tenant::query()->pluck('id') as $tenantId) {
            SeedDefaultProductTypes::forTenant((int) $tenantId);
        }
    }

    public function down(): void
    {
        // Types may be referenced by products; leave data in place.
    }
};
