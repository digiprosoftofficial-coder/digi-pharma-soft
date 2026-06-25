<?php

namespace App\Services\Reports;

use App\Domain\Accounting\Models\LedgerAccount;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Models\Product;
use App\Domain\Purchasing\Models\Supplier;
use App\Domain\Sales\Models\Customer;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

final class ReportOptionService
{
    public function common(): array
    {
        return [
            'branches' => ReportFilter::branchOptions(),
            'suppliers' => $this->options(Supplier::query()->orderBy('name')->limit(200)->get(['id', 'name']), 'name'),
            'customers' => $this->options(Customer::query()->orderBy('name')->limit(200)->get(['id', 'name']), 'name'),
            'products' => $this->options(Product::query()->orderBy('name')->limit(300)->get(['id', 'name']), 'name'),
            'categories' => $this->options(Category::query()->orderBy('name')->limit(200)->get(['id', 'name']), 'name'),
            'manufacturers' => $this->options(Manufacturer::query()->orderBy('name')->limit(200)->get(['id', 'name']), 'name'),
            'users' => $this->options(User::query()->where('tenant_id', \tenant_id())->orderBy('name')->limit(200)->get(['id', 'name']), 'name'),
            'accounts' => $this->options(LedgerAccount::query()->orderBy('code')->get(['id', 'code', 'name']), 'account_label'),
            'events' => Activity::query()
                ->where('tenant_id', \tenant_id())
                ->whereNotNull('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event')
                ->map(fn (string $event) => ['value' => $event, 'label' => $event])
                ->values(),
        ];
    }

    private function options($rows, string $labelField): array
    {
        return $rows
            ->map(fn ($row) => [
                'value' => $row->getKey(),
                'label' => $labelField === 'account_label'
                    ? trim(($row->code ? $row->code.' - ' : '').$row->name)
                    : $row->{$labelField},
            ])
            ->values()
            ->all();
    }
}
