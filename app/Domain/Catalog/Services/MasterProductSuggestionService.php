<?php

namespace App\Domain\Catalog\Services;

use App\Domain\Catalog\Models\MasterProduct;
use App\Domain\Catalog\Models\MasterProductSuggestion;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Crowdsources missing medicines into a Superadmin review queue.
 * Tenant products stay usable immediately; master grows only after approval.
 */
final class MasterProductSuggestionService
{
    public function suggestFromProduct(Product $product, ?User $user = null): ?MasterProductSuggestion
    {
        $product->loadMissing('manufacturer');

        if ($product->master_product_id !== null) {
            return null;
        }

        $tenantId = (int) $product->tenant_id;
        if ($tenantId <= 0) {
            return null;
        }

        // Already linked to master by barcode/sku — no queue entry needed.
        $existingMaster = $this->findMatchingMaster($product);
        if ($existingMaster) {
            $product->forceFill(['master_product_id' => $existingMaster->getKey()])->save();

            return null;
        }

        $existing = MasterProductSuggestion::query()
            ->where('product_id', $product->getKey())
            ->first();

        if ($existing) {
            if ($existing->isPending()) {
                $existing->fill($this->snapshotFromProduct($product, $user))->save();
            }

            return $existing;
        }

        return MasterProductSuggestion::query()->create(array_merge(
            $this->snapshotFromProduct($product, $user),
            [
                'tenant_id' => $tenantId,
                'product_id' => $product->getKey(),
                'status' => MasterProductSuggestion::STATUS_PENDING,
            ],
        ));
    }

    public function approve(MasterProductSuggestion $suggestion, User $reviewer, ?string $note = null): MasterProduct
    {
        if (! $suggestion->isPending()) {
            throw ValidationException::withMessages([
                'suggestion' => [__('platform.suggestion_not_pending')],
            ]);
        }

        return DB::transaction(function () use ($suggestion, $reviewer, $note) {
            $sku = filled($suggestion->sku)
                ? $this->uniqueMasterSku((string) $suggestion->sku)
                : $this->uniqueMasterSku('MSTR-'.Str::upper(Str::slug(Str::limit($suggestion->name, 40, ''), '')));

            $master = MasterProduct::query()->create([
                'name' => $suggestion->name,
                'generic_name' => $suggestion->generic_name,
                'strength' => $suggestion->strength,
                'manufacturer_name' => $suggestion->manufacturer_name,
                'product_type' => $suggestion->product_type ?: 'other',
                'drug_class' => $suggestion->drug_class,
                'base_unit' => $suggestion->base_unit ?: 'strip',
                'pieces_per_strip' => $suggestion->pieces_per_strip,
                'strips_per_box' => $suggestion->strips_per_box,
                'boxes_per_carton' => $suggestion->boxes_per_carton,
                'sku' => $sku,
                'barcode' => $suggestion->barcode,
                'mrp' => $suggestion->mrp,
                'default_purchase_price' => $suggestion->default_purchase_price,
                'is_active' => true,
            ]);

            $this->linkProduct($suggestion, $master);
            $this->markReviewed($suggestion, $reviewer, MasterProductSuggestion::STATUS_APPROVED, $master, $note);

            return $master;
        });
    }

    public function merge(
        MasterProductSuggestion $suggestion,
        MasterProduct $master,
        User $reviewer,
        ?string $note = null,
    ): MasterProduct {
        if (! $suggestion->isPending()) {
            throw ValidationException::withMessages([
                'suggestion' => [__('platform.suggestion_not_pending')],
            ]);
        }

        return DB::transaction(function () use ($suggestion, $master, $reviewer, $note) {
            $this->linkProduct($suggestion, $master);
            $this->markReviewed($suggestion, $reviewer, MasterProductSuggestion::STATUS_MERGED, $master, $note);

            return $master;
        });
    }

    public function reject(MasterProductSuggestion $suggestion, User $reviewer, ?string $note = null): void
    {
        if (! $suggestion->isPending()) {
            throw ValidationException::withMessages([
                'suggestion' => [__('platform.suggestion_not_pending')],
            ]);
        }

        $this->markReviewed($suggestion, $reviewer, MasterProductSuggestion::STATUS_REJECTED, null, $note);
    }

    /**
     * @return list<array{id:int,name:string,sku:?string,barcode:?string,generic_name:?string,strength:?string}>
     */
    public function matchCandidates(MasterProductSuggestion $suggestion, int $limit = 8): array
    {
        $q = MasterProduct::query()->where('is_active', true);

        $q->where(function ($w) use ($suggestion) {
            if (filled($suggestion->barcode)) {
                $w->orWhere('barcode', $suggestion->barcode);
            }
            if (filled($suggestion->sku)) {
                $w->orWhere('sku', $suggestion->sku);
            }
            $w->orWhere('name', 'like', '%'.$suggestion->name.'%');
            if (filled($suggestion->generic_name)) {
                $w->orWhere(function ($inner) use ($suggestion) {
                    $inner->where('generic_name', 'like', '%'.$suggestion->generic_name.'%');
                    if (filled($suggestion->strength)) {
                        $inner->where('strength', 'like', '%'.$suggestion->strength.'%');
                    }
                });
            }
        });

        return $q->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'barcode', 'generic_name', 'strength'])
            ->map(fn (MasterProduct $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'sku' => $m->sku,
                'barcode' => $m->barcode,
                'generic_name' => $m->generic_name,
                'strength' => $m->strength,
            ])
            ->all();
    }

    private function findMatchingMaster(Product $product): ?MasterProduct
    {
        if (filled($product->barcode) && ! str_starts_with((string) $product->barcode, 'BC-')) {
            $byBarcode = MasterProduct::query()
                ->where('is_active', true)
                ->where('barcode', $product->barcode)
                ->first();
            if ($byBarcode) {
                return $byBarcode;
            }
        }

        if (filled($product->sku)) {
            return MasterProduct::query()
                ->where('is_active', true)
                ->where('sku', $product->sku)
                ->first();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFromProduct(Product $product, ?User $user): array
    {
        return [
            'suggested_by_user_id' => $user?->getKey(),
            'name' => $product->name,
            'generic_name' => $product->generic_name,
            'strength' => $product->strength,
            'manufacturer_name' => $product->manufacturer?->name,
            'product_type' => $product->product_type ?: 'other',
            'drug_class' => null,
            'base_unit' => $product->base_unit ?: 'strip',
            'pieces_per_strip' => $product->pieces_per_strip,
            'strips_per_box' => $product->strips_per_box,
            'boxes_per_carton' => $product->boxes_per_carton,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'mrp' => $product->sale_price ?? 0,
            'default_purchase_price' => $product->purchase_price ?? 0,
        ];
    }

    private function linkProduct(MasterProductSuggestion $suggestion, MasterProduct $master): void
    {
        if ($suggestion->product_id === null) {
            return;
        }

        Product::query()
            ->withoutGlobalScopes()
            ->whereKey($suggestion->product_id)
            ->whereNull('master_product_id')
            ->update(['master_product_id' => $master->getKey()]);
    }

    private function markReviewed(
        MasterProductSuggestion $suggestion,
        User $reviewer,
        string $status,
        ?MasterProduct $master,
        ?string $note,
    ): void {
        $suggestion->forceFill([
            'status' => $status,
            'master_product_id' => $master?->getKey(),
            'reviewed_by_user_id' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
        ])->save();
    }

    private function uniqueMasterSku(string $baseSku): string
    {
        $base = filled($baseSku) ? Str::upper($baseSku) : 'MSTR-'.Str::upper(Str::random(8));
        $candidate = $base;
        $suffix = 1;

        while (MasterProduct::query()->where('sku', $candidate)->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
