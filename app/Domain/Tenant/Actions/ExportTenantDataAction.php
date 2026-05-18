<?php

namespace App\Domain\Tenant\Actions;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Platform\TenantComplianceExporter;

final class ExportTenantDataAction
{
    public function __construct(
        private readonly TenantComplianceExporter $exporter,
    ) {}

    public function execute(Tenant $tenant, User $causer): string
    {
        $zipPath = $this->exporter->writeZip($tenant);

        activity()
            ->causedBy($causer)
            ->performedOn($tenant)
            ->tap(fn (\Spatie\Activitylog\Models\Activity $activity) => $activity->tenant_id = $tenant->getKey())
            ->event('tenant.data_exported')
            ->log('Pharmacy data exported for compliance');

        return $zipPath;
    }
}
