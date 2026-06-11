<?php

namespace App\Domain\Tenant\Services;

use App\Domain\Tenant\Models\Branch;
use App\Support\Tenant\TenantFeatures;
use App\Support\Tenant\TenantLimits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class BranchService
{
    /**
     * @param  array{name:string, code?:string, address?:string|null, phone?:string|null, is_active?:bool}  $data
     */
    public function create(array $data): Branch
    {
        $tenant = tenant();
        if (! TenantFeatures::multiBranchEnabled($tenant)) {
            throw new RuntimeException(__('branches.feature_disabled'));
        }

        $this->assertUnderLimit();

        $code = isset($data['code']) && $data['code'] !== ''
            ? Str::upper(Str::slug($data['code'], ''))
            : $this->generateCode($data['name']);

        return Branch::query()->create([
            'name' => $data['name'],
            'code' => $code,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_default' => false,
        ]);
    }

    /**
     * @param  array{name?:string, code?:string, address?:string|null, phone?:string|null, is_active?:bool}  $data
     */
    public function update(Branch $branch, array $data): Branch
    {
        if (! TenantFeatures::multiBranchEnabled(tenant())) {
            throw new RuntimeException(__('branches.feature_disabled'));
        }

        if (isset($data['name'])) {
            $branch->name = $data['name'];
        }
        if (array_key_exists('code', $data) && $data['code'] !== '') {
            $branch->code = Str::upper(Str::slug($data['code'], ''));
        }
        if (array_key_exists('address', $data)) {
            $branch->address = $data['address'];
        }
        if (array_key_exists('phone', $data)) {
            $branch->phone = $data['phone'];
        }
        if (array_key_exists('is_active', $data)) {
            if ($branch->is_default && ! $data['is_active']) {
                throw new RuntimeException(__('branches.cannot_deactivate_default'));
            }
            $branch->is_active = (bool) $data['is_active'];
        }

        $branch->save();

        return $branch;
    }

    public function delete(Branch $branch): void
    {
        if (! TenantFeatures::multiBranchEnabled(tenant())) {
            throw new RuntimeException(__('branches.feature_disabled'));
        }

        if ($branch->is_default) {
            throw new RuntimeException(__('branches.cannot_delete_default'));
        }

        $count = Branch::query()->count();
        if ($count <= 1) {
            throw new RuntimeException(__('branches.cannot_delete_last'));
        }

        if (DB::table('product_batches')->where('branch_id', $branch->getKey())->where('quantity_on_hand', '>', 0)->exists()) {
            throw new RuntimeException(__('branches.cannot_delete_with_stock'));
        }

        $branch->delete();
    }

    private function assertUnderLimit(): void
    {
        $max = TenantLimits::maxBranches(tenant());
        if ($max === null) {
            return;
        }

        $current = Branch::query()->count();
        if ($current >= $max) {
            throw new RuntimeException(__('branches.limit_reached', ['max' => $max]));
        }
    }

    private function generateCode(string $name): string
    {
        $base = Str::upper(Str::limit(Str::slug($name, ''), 8, ''));
        if ($base === '') {
            $base = 'BR';
        }

        $code = $base;
        $suffix = 1;
        while (Branch::query()->where('code', $code)->exists()) {
            $code = $base.$suffix;
            $suffix++;
        }

        return $code;
    }
}
