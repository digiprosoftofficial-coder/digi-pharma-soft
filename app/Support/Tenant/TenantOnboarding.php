<?php

namespace App\Support\Tenant;

use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductBatch;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Models\Sale;
use App\Domain\Tenant\Models\Branch;
use App\Domain\Tenant\Models\Tenant;
use App\Models\User;

final class TenantOnboarding
{
    public const STEPS = [
        'profile',
        'branch',
        'staff',
        'categories',
        'products',
        'suppliers',
        'opening_stock',
        'first_sale',
    ];

    /**
     * @return array{
     *   steps: array<string, bool>,
     *   completed_count: int,
     *   total: int,
     *   percent: int,
     *   completed_at: string|null,
     *   is_complete: bool
     * }
     */
    public static function progress(?Tenant $tenant): array
    {
        if ($tenant === null) {
            return [
                'steps' => array_fill_keys(self::STEPS, false),
                'completed_count' => 0,
                'total' => count(self::STEPS),
                'percent' => 0,
                'completed_at' => null,
                'is_complete' => false,
            ];
        }

        $stored = $tenant->settings['onboarding']['steps'] ?? [];
        $live = self::detect($tenant);
        $steps = [];
        foreach (self::STEPS as $step) {
            $steps[$step] = (bool) (($stored[$step] ?? false) || ($live[$step] ?? false));
        }

        $completed = count(array_filter($steps));
        $total = count(self::STEPS);
        $isComplete = $completed >= $total;
        $completedAt = $tenant->settings['onboarding']['completed_at'] ?? null;

        if ($isComplete && $completedAt === null) {
            $settings = $tenant->settings ?? [];
            $settings['onboarding'] = array_merge($settings['onboarding'] ?? [], [
                'steps' => $steps,
                'completed_at' => now()->toIso8601String(),
            ]);
            $tenant->forceFill(['settings' => $settings])->saveQuietly();
            $completedAt = $settings['onboarding']['completed_at'];
        }

        return [
            'steps' => $steps,
            'completed_count' => $completed,
            'total' => $total,
            'percent' => (int) round(($completed / max(1, $total)) * 100),
            'completed_at' => $completedAt,
            'is_complete' => $isComplete,
        ];
    }

    /**
     * @return array<string, bool>
     */
    public static function detect(Tenant $tenant): array
    {
        $tenantId = (int) $tenant->getKey();

        return [
            'profile' => filled($tenant->name) && filled($tenant->slug),
            'branch' => Branch::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->exists(),
            'staff' => User::query()->where('tenant_id', $tenantId)->count() > 1,
            'categories' => Category::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->exists(),
            'products' => Product::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->exists(),
            'suppliers' => Supplier::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->exists(),
            'opening_stock' => ProductBatch::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->where('quantity_on_hand', '>', 0)->exists(),
            'first_sale' => Sale::query()->withoutGlobalScopes()->where('tenant_id', $tenantId)->exists(),
        ];
    }

    public static function markStep(Tenant $tenant, string $step, bool $done = true): void
    {
        if (! in_array($step, self::STEPS, true)) {
            return;
        }

        $settings = $tenant->settings ?? [];
        $onboarding = $settings['onboarding'] ?? ['steps' => []];
        $onboarding['steps'][$step] = $done;
        $settings['onboarding'] = $onboarding;
        $tenant->forceFill(['settings' => $settings])->save();
    }
}
