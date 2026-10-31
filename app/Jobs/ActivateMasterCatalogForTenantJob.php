<?php

namespace App\Jobs;

use App\Domain\Catalog\Actions\ActivateMasterCatalogForTenantAction;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Catalog\MasterCatalogSyncStatus;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class ActivateMasterCatalogForTenantJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public int $uniqueFor = 1800;

    public function __construct(
        public Tenant $tenant,
        public User $causer,
        public ?int $maxToCreate = null,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->tenant->getKey();
    }

    public function handle(ActivateMasterCatalogForTenantAction $action): void
    {
        $this->tenant->refresh();
        $action->execute($this->tenant, $this->causer, $this->maxToCreate);
    }

    public function failed(?Throwable $exception): void
    {
        MasterCatalogSyncStatus::markFailed(
            $this->tenant,
            $exception?->getMessage() ?: 'Master catalog sync failed.',
        );
    }
}
