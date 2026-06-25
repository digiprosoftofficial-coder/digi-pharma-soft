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
        public ?int $supplierId,
        public ?int $customerId,
        public ?int $productId,
        public ?int $categoryId,
        public ?int $manufacturerId,
        public ?int $userId,
        public ?int $accountId,
        public ?string $paymentStatus,
        public ?string $paymentMethod,
        public ?string $batch,
        public ?string $expiryStatus,
        public ?string $dueStatus,
        public ?string $eventType,
        public ?string $direction,
        public ?string $status,
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

        $optional = [
            'supplier_id' => self::nullableInt($request, 'supplier_id'),
            'customer_id' => self::nullableInt($request, 'customer_id'),
            'product_id' => self::nullableInt($request, 'product_id'),
            'category_id' => self::nullableInt($request, 'category_id'),
            'manufacturer_id' => self::nullableInt($request, 'manufacturer_id'),
            'user_id' => self::nullableInt($request, 'user_id'),
            'account_id' => self::nullableInt($request, 'account_id'),
            'payment_status' => self::nullableString($request, 'payment_status'),
            'payment_method' => self::nullableString($request, 'payment_method'),
            'batch' => self::nullableString($request, 'batch'),
            'expiry_status' => self::nullableString($request, 'expiry_status'),
            'due_status' => self::nullableString($request, 'due_status'),
            'event_type' => self::nullableString($request, 'event_type'),
            'direction' => self::nullableString($request, 'direction'),
            'status' => self::nullableString($request, 'status'),
        ];

        return new self(
            dateFrom: $from,
            dateTo: $to,
            branchId: $branchId,
            tenantWide: $tenantWide,
            canViewAllBranches: $canViewAllBranches,
            branchLabel: $branchLabel,
            supplierId: $optional['supplier_id'],
            customerId: $optional['customer_id'],
            productId: $optional['product_id'],
            categoryId: $optional['category_id'],
            manufacturerId: $optional['manufacturer_id'],
            userId: $optional['user_id'],
            accountId: $optional['account_id'],
            paymentStatus: $optional['payment_status'],
            paymentMethod: $optional['payment_method'],
            batch: $optional['batch'],
            expiryStatus: $optional['expiry_status'],
            dueStatus: $optional['due_status'],
            eventType: $optional['event_type'],
            direction: $optional['direction'],
            status: $optional['status'],
            raw: [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'branch_id' => $tenantWide ? 'all' : ($branchId ? (string) $branchId : ''),
                ...array_filter($optional, fn ($value) => $value !== null && $value !== ''),
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

    private static function nullableInt(Request $request, string $key): ?int
    {
        $value = $request->input($key);

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private static function nullableString(Request $request, string $key): ?string
    {
        $value = trim((string) $request->input($key, ''));

        return $value !== '' ? $value : null;
    }
}
