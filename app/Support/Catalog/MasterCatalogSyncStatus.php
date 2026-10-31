<?php

namespace App\Support\Catalog;

use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\Product;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use App\Support\Tenant\TenantLimits;
use Illuminate\Support\Carbon;

final class MasterCatalogSyncStatus
{
    public const KEY = 'master_catalog_sync';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_FAILED = 'failed';

    /**
     * @return array<string, mixed>
     */
    public static function present(Tenant $tenant): array
    {
        $state = self::raw($tenant);
        $activeMasterCount = (int) MasterProduct::query()->where('is_active', true)->count();
        $alreadyAdded = (int) Product::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->whereNotNull('master_product_id')
            ->count();
        $pending = self::pendingCount($tenant);
        $remainingSlots = TenantLimits::remainingProductsForTenant($tenant);
        $blockedByLimit = $remainingSlots === 0 && $pending > 0;
        $willAdd = $pending;
        if ($remainingSlots !== null) {
            $willAdd = min($pending, $remainingSlots);
        }

        $status = (string) ($state['status'] ?? '');
        $isBusy = in_array($status, [self::STATUS_QUEUED, self::STATUS_RUNNING], true)
            && ! self::isStale($state);

        return [
            'active_master_count' => $activeMasterCount,
            'already_added' => $alreadyAdded,
            'pending' => $pending,
            'remaining_product_slots' => $remainingSlots,
            'will_add' => $willAdd,
            'can_add' => $pending > 0 && ! $blockedByLimit && ! $isBusy,
            'blocked_by_limit' => $blockedByLimit,
            'is_busy' => $isBusy,
            'status' => $status !== '' ? $status : null,
            'created' => (int) ($state['created'] ?? 0),
            'skipped' => (int) ($state['skipped'] ?? 0),
            'processed' => (int) ($state['processed'] ?? 0),
            'total' => (int) ($state['total'] ?? 0),
            'stopped_at_limit' => (bool) ($state['stopped_at_limit'] ?? false),
            'error' => $state['error'] ?? null,
            'queued_at' => $state['queued_at'] ?? null,
            'started_at' => $state['started_at'] ?? null,
            'finished_at' => $state['finished_at'] ?? null,
        ];
    }

    public static function pendingCount(Tenant $tenant): int
    {
        return (int) MasterProduct::query()
            ->where('is_active', true)
            ->whereNotExists(function ($query) use ($tenant): void {
                $query->selectRaw('1')
                    ->from('products')
                    ->whereColumn('products.master_product_id', 'master_products.id')
                    ->where('products.tenant_id', $tenant->getKey());
            })
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    public static function raw(Tenant $tenant): array
    {
        $value = $tenant->settings[self::KEY] ?? [];

        return is_array($value) ? $value : [];
    }

    public static function markQueued(Tenant $tenant, User $causer, int $total = 0): void
    {
        self::write($tenant, [
            'status' => self::STATUS_QUEUED,
            'queued_at' => now()->toIso8601String(),
            'started_at' => null,
            'finished_at' => null,
            'created' => 0,
            'skipped' => 0,
            'processed' => 0,
            'total' => $total,
            'stopped_at_limit' => false,
            'error' => null,
            'queued_by' => $causer->getKey(),
        ]);
    }

    public static function markRunning(Tenant $tenant, int $total): void
    {
        self::write($tenant, [
            'status' => self::STATUS_RUNNING,
            'started_at' => now()->toIso8601String(),
            'total' => $total,
            'error' => null,
        ]);
    }

    public static function markProgress(Tenant $tenant, int $created, int $skipped, int $processed, int $total): void
    {
        self::write($tenant, [
            'status' => self::STATUS_RUNNING,
            'created' => $created,
            'skipped' => $skipped,
            'processed' => $processed,
            'total' => $total,
        ]);
    }

    public static function markFinished(Tenant $tenant, int $created, int $skipped, bool $stoppedAtLimit): void
    {
        self::write($tenant, [
            'status' => $stoppedAtLimit ? self::STATUS_PARTIAL : self::STATUS_COMPLETED,
            'created' => $created,
            'skipped' => $skipped,
            'processed' => $created + $skipped,
            'stopped_at_limit' => $stoppedAtLimit,
            'finished_at' => now()->toIso8601String(),
            'error' => null,
        ]);
    }

    public static function markFailed(Tenant $tenant, string $message): void
    {
        self::write($tenant, [
            'status' => self::STATUS_FAILED,
            'finished_at' => now()->toIso8601String(),
            'error' => $message,
        ]);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function isStale(array $state): bool
    {
        $stamp = $state['started_at'] ?? $state['queued_at'] ?? null;
        if (! is_string($stamp) || $stamp === '') {
            return true;
        }

        try {
            return Carbon::parse($stamp)->lt(now()->subHours(2));
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * @param  array<string, mixed>  $patch
     */
    private static function write(Tenant $tenant, array $patch): void
    {
        $tenant->refresh();
        $settings = $tenant->settings ?? [];
        $current = is_array($settings[self::KEY] ?? null) ? $settings[self::KEY] : [];
        $settings[self::KEY] = array_merge($current, $patch);
        $tenant->forceFill(['settings' => $settings])->save();
    }
}
