<template>
    <TenantShellLayout :page-title="product.name">
        <Head :title="product.name" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <Link href="/products" class="small text-decoration-none">← Products</Link>
                <h1 class="h4 mb-1">{{ product.name }}</h1>
                <p class="text-muted small mb-0">
                    SKU <strong>{{ product.sku }}</strong>
                    <span v-if="product.barcode" class="ms-2">· Barcode {{ product.barcode }}</span>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a :href="`/barcodes/${product.id}`" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">Barcode</a>
                <Link v-if="can('products.manage')" :href="`/products/${product.id}/edit`" class="btn btn-primary btn-sm">Edit</Link>
                <button
                    v-if="can('products.manage')"
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    @click="confirmDelete"
                >
                    Delete
                </button>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body">
                        <p class="text-muted small mb-1">On hand (base unit)</p>
                        <p class="h4 mb-0">
                            {{ formatQty(stockBase) }}
                            <span class="fs-6 text-muted text-capitalize">{{ unitLabel(product.base_unit) }}</span>
                        </p>
                        <p v-if="stockPieces" class="small text-muted mb-0 mt-2">
                            <strong>{{ stockPieces }}</strong> pieces total
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Total purchased</p>
                        <p class="h4 mb-0">
                            {{ formatQty(purchasedQuantity) }}
                            <span class="fs-6 text-muted text-capitalize">{{ unitLabel(product.base_unit) }}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <p class="text-muted small mb-1">Status</p>
                        <span class="badge fs-6" :class="product.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                            {{ product.is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <p class="small text-muted mb-0 mt-2">Min stock alert: {{ product.min_stock ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">Stock by sell unit</div>
            <p class="small text-muted px-3 pt-2 mb-0">
                Current on-hand quantity shown in each configured unit (after sales, purchases, and adjustments).
            </p>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Unit</th>
                            <th class="text-end">Per {{ unitLabel(product.base_unit) }}</th>
                            <th class="text-end">On hand</th>
                            <th class="text-end">Sale price</th>
                            <th class="text-end">Purchase price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in stockByUnit" :key="row.sell_unit">
                            <td class="text-capitalize">
                                {{ unitLabel(row.sell_unit) }}
                                <span v-if="row.is_default" class="badge text-bg-primary ms-1">Default</span>
                            </td>
                            <td class="text-end">{{ formatQty(row.conversion_factor) }}</td>
                            <td class="text-end fw-semibold">{{ formatQty(row.quantity_on_hand) }}</td>
                            <td class="text-end">{{ formatMoney(unitSalePrice(product, row.sell_unit)) }}</td>
                            <td class="text-end">{{ formatMoney(unitPurchasePrice(product, row.sell_unit)) }}</td>
                        </tr>
                        <tr v-if="!stockByUnit.length">
                            <td colspan="5" class="text-muted text-center py-3">No sell units configured.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Product details</div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-sm-4">Type</dt>
                            <dd class="col-sm-8 text-capitalize">{{ product.product_type || '—' }}</dd>
                            <dt class="col-sm-4">Category</dt>
                            <dd class="col-sm-8">{{ product.category?.name || '—' }}</dd>
                            <dt class="col-sm-4">Manufacturer</dt>
                            <dd class="col-sm-8">{{ product.manufacturer?.name || '—' }}</dd>
                            <dt class="col-sm-4">Base unit</dt>
                            <dd class="col-sm-8 text-capitalize">{{ unitLabel(product.base_unit) }}</dd>
                            <template v-if="product.pieces_per_strip">
                                <dt class="col-sm-4">Pieces per strip</dt>
                                <dd class="col-sm-8">{{ formatQty(product.pieces_per_strip) }}</dd>
                            </template>
                            <dt class="col-sm-4">Default sale</dt>
                            <dd class="col-sm-8">{{ formatMoney(product.sale_price) }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100 text-center">
                    <div class="card-header bg-white fw-semibold">Barcode label</div>
                    <div class="card-body">
                        <img :src="`/barcodes/${product.id}`" alt="Barcode" class="border rounded bg-white p-2" style="max-height: 80px" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Batches</div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Batch no</th>
                            <th>Expiry</th>
                            <th class="text-end">On hand ({{ unitLabel(product.base_unit) }})</th>
                            <th class="text-end">Unit cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in batches" :key="b.id">
                            <td>{{ b.batch_no }}</td>
                            <td>{{ b.expiry_date || '—' }}</td>
                            <td class="text-end fw-semibold">{{ formatQty(b.quantity_on_hand) }}</td>
                            <td class="text-end">{{ formatMoney(b.purchase_unit_cost) }}</td>
                        </tr>
                        <tr v-if="!batches.length">
                            <td colspan="4" class="text-muted text-center py-4">No batches in stock yet.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="batches.length" class="table-light">
                        <tr>
                            <th colspan="2">Total</th>
                            <th class="text-end">{{ formatQty(stockBase) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import { unitLabel, unitPurchasePrice, unitSalePrice } from '@/composables/useProductUnits';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
    stockBase: { type: String, default: '0' },
    purchasedQuantity: { type: String, default: '0' },
    stockByUnit: { type: Array, default: () => [] },
    stockPieces: { type: String, default: null },
});

const { formatMoney } = useMoney();
const { can } = usePermissions();

const batches = computed(() => {
    const raw = props.product.batches;
    if (!raw) {
        return [];
    }
    return Array.isArray(raw) ? raw : raw.data ?? [];
});

function formatQty(value) {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) {
        return '0';
    }
    return n % 1 === 0 ? String(n) : n.toFixed(2);
}

function confirmDelete() {
    if (!window.confirm(`Delete product "${props.product.name}"? This cannot be undone.`)) {
        return;
    }
    router.delete(`/products/${props.product.id}`);
}
</script>
