<?php

namespace App\Support\Dashboard;

use App\Domain\Tenant\Models\Branch;
use App\Services\Reports\ReportFilter;
use App\Support\Tenant\TenantFeatures;
use Illuminate\Http\Request;

final class DashboardBranchScope
{
    /**
     * @param  list<array{value:string,label:string}>  $options
     */
    public function __construct(
        public readonly string $key,
        public readonly ?int $branchId,
        public readonly bool $all,
        public readonly bool $canViewAll,
        public readonly string $label,
        public readonly array $options,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $canViewAll = ReportFilter::userCanViewAllBranches();
        $options = self::options($canViewAll);
        $currentId = \branch_id();

        if (! $canViewAll) {
            $branch = $currentId ? Branch::query()->whereKey($currentId)->first() : null;

            return new self(
                key: $currentId ? (string) $currentId : 'current',
                branchId: $currentId,
                all: false,
                canViewAll: false,
                label: $branch?->name ?? __('dashboard.current_branch'),
                options: $options,
            );
        }

        // Default to the session active branch; owners opt into all-branches explicitly.
        if (! $request->filled('branch_id')) {
            return self::forCurrentBranch($currentId, $canViewAll, $options);
        }

        $requested = (string) $request->input('branch_id');
        if ($requested === 'all') {
            return new self(
                key: 'all',
                branchId: null,
                all: true,
                canViewAll: true,
                label: __('dashboard.all_branches'),
                options: $options,
            );
        }

        $branch = Branch::query()
            ->whereKey((int) $requested)
            ->where('is_active', true)
            ->first();

        if ($branch === null) {
            return self::forCurrentBranch($currentId, $canViewAll, $options);
        }

        return new self(
            key: (string) $branch->getKey(),
            branchId: (int) $branch->getKey(),
            all: false,
            canViewAll: true,
            label: $branch->name,
            options: $options,
        );
    }

    /**
     * @param  list<array{value:string,label:string}>  $options
     */
    private static function forCurrentBranch(?int $currentId, bool $canViewAll, array $options): self
    {
        $branch = $currentId ? Branch::query()->whereKey($currentId)->first() : null;

        return new self(
            key: $currentId ? (string) $currentId : 'current',
            branchId: $currentId,
            all: false,
            canViewAll: $canViewAll,
            label: $branch?->name ?? __('dashboard.current_branch'),
            options: $options,
        );
    }

    /**
     * @return array{
     *     key:string,
     *     branch_id:?int,
     *     all:bool,
     *     can_view_all:bool,
     *     label:string,
     *     options:list<array{value:string,label:string}>
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'branch_id' => $this->branchId,
            'all' => $this->all,
            'can_view_all' => $this->canViewAll,
            'label' => $this->label,
            'options' => $this->options,
        ];
    }

    /**
     * @param  \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>
     */
    public function constrain(mixed $query, string $column = 'branch_id'): mixed
    {
        if ($this->branchId !== null) {
            $query->where($column, $this->branchId);
        }

        return $query;
    }

    /**
     * @return list<array{value:string,label:string}>
     */
    private static function options(bool $canViewAll): array
    {
        if (! $canViewAll || ! TenantFeatures::multiBranchEnabled(\tenant())) {
            return [];
        }

        $options = [
            ['value' => 'all', 'label' => __('dashboard.all_branches')],
        ];

        foreach (
            Branch::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name']) as $branch
        ) {
            $options[] = [
                'value' => (string) $branch->getKey(),
                'label' => $branch->name,
            ];
        }

        return $options;
    }
}
