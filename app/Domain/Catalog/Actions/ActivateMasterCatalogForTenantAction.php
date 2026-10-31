<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Services\MasterProductActivationService;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Catalog\MasterCatalogSyncStatus;
use App\Support\Tenant\TenantContext;
use App\Support\Tenant\TenantLimits;
use Spatie\Activitylog\Models\Activity;

final class ActivateMasterCatalogForTenantAction
{
    public function __construct(
        private readonly MasterProductActivationService $activation,
        private readonly TenantContext $tenantContext,
    ) {}

    /**
     * @return array{created: int, skipped: int, stopped_at_limit: bool}
     */
    public function execute(Tenant $tenant, User $causer, ?int $maxToCreate = null): array
    {
        $previous = $this->tenantContext->tenant();
        $this->tenantContext->set($tenant);

        try {
            return $this->run($tenant, $causer, $maxToCreate);
        } finally {
            $this->tenantContext->set($previous);
        }
    }

    /**
     * @return array{created: int, skipped: int, stopped_at_limit: bool}
     */
    private function run(Tenant $tenant, User $causer, ?int $maxToCreate = null): array
    {
        $existing = Product::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->whereNotNull('master_product_id')
            ->pluck('master_product_id')
            ->flip();

        $pendingQuery = MasterProduct::query()->where('is_active', true);
        if ($existing->isNotEmpty()) {
            $pendingQuery->whereNotIn('id', $existing->keys()->all());
        }
        $pending = $pendingQuery->count();

        MasterCatalogSyncStatus::markRunning($tenant, $pending);

        $tenant->unsetRelation('activeSubscription');
        $remainingSlots = TenantLimits::remainingProductsForTenant($tenant);
        if ($maxToCreate !== null) {
            $remainingSlots = $remainingSlots === null
                ? max(0, $maxToCreate)
                : min($remainingSlots, max(0, $maxToCreate));
        }
        $created = 0;
        $skipped = 0;
        $processed = 0;
        $stoppedAtLimit = false;

        MasterProduct::query()
            ->where('is_active', true)
            ->with('units')
            ->orderBy('id')
            ->chunkById(50, function ($masters) use ($tenant, &$existing, &$created, &$skipped, &$processed, &$remainingSlots, &$stoppedAtLimit, $pending): bool {
                foreach ($masters as $master) {
                    if ($existing->has($master->getKey())) {
                        $skipped++;
                        $processed++;

                        continue;
                    }

                    if ($remainingSlots === 0) {
                        $stoppedAtLimit = true;

                        return false;
                    }

                    $alreadyPresent = Product::query()
                        ->withoutGlobalScopes()
                        ->where('tenant_id', $tenant->getKey())
                        ->where(function ($query) use ($master): void {
                            $query->where('master_product_id', $master->getKey());
                            if (filled($master->barcode)) {
                                $query->orWhere('barcode', $master->barcode);
                            }
                        })
                        ->exists();

                    $this->activation->activate($master, $tenant->getKey());
                    $existing->put($master->getKey(), true);
                    $processed++;

                    if ($alreadyPresent) {
                        $skipped++;
                    } else {
                        $created++;
                        if ($remainingSlots !== null) {
                            $remainingSlots--;
                        }
                    }
                }

                MasterCatalogSyncStatus::markProgress($tenant, $created, $skipped, $processed, $pending);

                return true;
            });

        MasterCatalogSyncStatus::markFinished($tenant, $created, $skipped, $stoppedAtLimit);

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->withProperties([
                'created' => $created,
                'skipped' => $skipped,
                'stopped_at_limit' => $stoppedAtLimit,
            ])
            ->event('catalog.master_activated')
            ->log('Master catalog medicines added to pharmacy');

        return [
            'created' => $created,
            'skipped' => $skipped,
            'stopped_at_limit' => $stoppedAtLimit,
        ];
    }
}
