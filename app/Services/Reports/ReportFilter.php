<?php

namespace App\Services\Reports;

use App\Domain\Tenant\Models\Branch;
use App\Support\Tenant\BranchProvisioner;
use App\Support\Tenant\TenantFeatures;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final readonly class ReportFilter
{
    public function __construct(
        public CarbonImmutable $dateFrom,
        public CarbonImmutable $dateTo,
        public ?int $branchId,
        public bool $tenantWide,
        public bool $canViewAllBranches,
        public string $branchLabel,
        public array $raw,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $from = $request->date('date_from')
            ? CarbonImmutable::parse($request->date('date_from')->toDateString())->startOfDay()
            : now()->toImmutable()->subDays(30)->startOfDay();

        $to = $request->date('date_to')
            ? CarbonImmutable::parse($request->date('date_to')->toDateString())->endOfDay()
            : now()->toImmutable()->endOfDay();

        $canViewAllBranches = self::userCanViewAllBranches();
        $requestedBranch = $request->input('branch_id');
        $branchId = null;
        $tenantWide = false;

        if ($canViewAllBranches) {
            $branchId = is_numeric($requestedBranch) && (int) $requestedBranch > 0
                ? (int) $requestedBranch
                : null;
            $tenantWide = $branchId === null;
        } else {
            $branchId = \branch_id()
                ?? BranchProvisioner::defaultForTenant(\tenant_id())?->getKey();
        }

        $branchLabel = $tenantWide
            ? 'All branches'
            : (Branch::query()->whereKey($branchId)->value('name') ?? 'Current branch');

        return new self(
            dateFrom: $from,
            dateTo: $to,
            branchId: $branchId,
            tenantWide: $tenantWide,
            canViewAllBranches: $canViewAllBranches,
            branchLabel: $branchLabel,
            raw: [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'branch_id' => $tenantWide ? 'all' : ($branchId ? (string) $branchId : ''),
            ],
        );
    }

    public static function userCanViewAllBranches(): bool
    {
        $user = auth()->user();

        return TenantFeatures::multiBranchEnabled(\tenant())
            && (
                ($user?->can('reports.view_all_branches') ?? false)
                || ($user?->can('purchases.view_all_branches') ?? false)
            );
    }

    /**
     * @return Collection<int, array{id:int, name:string, code:?string}>
     */
    public static function branchOptions(): Collection
    {
        if (! self::userCanViewAllBranches()) {
            return collect();
        }

        return Branch::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Branch $branch) => [
                'id' => $branch->getKey(),
                'name' => $branch->name,
                'code' => $branch->code,
            ]);
    }

    public function queryParams(array $extra = []): array
    {
        return array_filter(array_merge($this->raw, $extra), fn ($value) => $value !== null && $value !== '');
    }
}
