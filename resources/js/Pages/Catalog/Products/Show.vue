<template>
    <TenantShellLayout :page-title="product.name">
        <Head :title="product.name" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <Link href="/products" class="small text-decoration-none">← Products</Link>
                <h1 class="h4 mb-1">{{ product.name }}</h1>
                <p v-if="product.generic_name || product.strength" class="text-muted small mb-1">
                    <span v-if="product.generic_name">{{ product.generic_name }}</span>
                    <span v-if="product.generic_name && product.strength"> · </span>
                    <span v-if="product.strength">{{ product.strength }}</span>
                </p>
                <p class="text-muted small mb-0">
                    SKU <strong>{{ product.sku }}</strong>
                    <span v-if="product.barcode" class="ms-2">· Barcode {{ product.barcode }}</span>
                    <span v-if="product.default_markup_percent" class="ms-2">
                        · {{ t('catalog.default_markup_percent') }} {{ product.default_markup_percent }}%
                    </span>
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
                        <p class="text-muted small mb-1">Purchased (base units)</p>
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
                        <p class="text-muted small mb-2">Stock by sell unit</p>
                        <ul class="list-unstyled small mb-0">
                            <li v-for="row in stockByUnit" :key="row.sell_unit" class="d-flex justify-content-between">
                                <span>{{ unitLabel(row.sell_unit) }}</span>
                                <strong>{{ formatQty(row.quantity_on_hand) }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Details</div>
                    <div class="card-body small">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Type</dt>
                            <dd class="col-sm-8"><ProductTypeLabel :type="product.product_type" /></dd>
                            <dt class="col-sm-4">Default shelf</dt>
                            <dd class="col-sm-8">{{ defaultShelfLabel }}</dd>
                            <dt class="col-sm-4">Sell units</dt>
                            <dd class="col-sm-8">
                                <span v-for="u in product.units" :key="u.sell_unit" class="me-2">
                                    {{ unitLabel(u.sell_unit) }}: {{ formatMoney(u.sale_price) }}
                                    <span v-if="u.is_default" class="text-muted">(default)</span>
                                </span>
                            </dd>
                            <dt v-if="wholesaleEnabled && product.wholesale_price" class="col-sm-4">Wholesale</dt>
                            <dd v-if="wholesaleEnabled && product.wholesale_price" class="col-sm-8">
                                {{ formatMoney(product.wholesale_price) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            :alt="product.name"
                            class="img-fluid rounded border mb-2"
                            style="max-height: 160px"
                        />
                        <p v-else class="text-muted small mb-2">No image</p>
                        <img :src="`/barcodes/${product.id}`" alt="Barcode" class="border rounded bg-white p-2" style="max-height: 80px" />
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="fw-semibold">Batches</div>
                <p class="small text-muted mb-0 mt-1">{{ t('catalog.batch_markup_help') }}</p>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Batch no</th>
                            <th>Expiry</th>
                            <th>{{ t('catalog.storage_location_shelf') }}</th>
                            <th class="text-end">On hand ({{ unitLabel(product.base_unit) }})</th>
                            <th class="text-end">Unit cost</th>
                            <th class="text-end">{{ t('catalog.batch_markup_percent') }}</th>
                            <th class="text-end">{{ t('catalog.batch_suggested_price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in batches" :key="b.id">
                            <td>{{ b.batch_no }}</td>
                            <td>{{ b.expiry_date || '—' }}</td>
                            <td class="small">{{ batchShelfLabel(b) }}</td>
                            <td class="text-end fw-semibold">{{ formatQty(b.quantity_on_hand) }}</td>
                            <td class="text-end">{{ formatMoney(b.purchase_unit_cost) }}</td>
                            <td class="text-end">
                                <form
                                    v-if="can('products.manage')"
                                    class="d-inline-flex gap-1 justify-content-end align-items-center"
                                    @submit.prevent="saveBatchMarkup(b)"
                                >
                                    <input
                                        v-model="batchMarkups[b.id]"
                                        type="number"
                                        min="0"
                                        max="1000"
                                        step="0.01"
                                        class="form-control form-control-sm text-end"
                                        style="width: 4.5rem"
                                        :placeholder="product.default_markup_percent ?? '—'"
                                    />
                                    <button type="submit" class="btn btn-sm btn-outline-primary py-0">✓</button>
                                </form>
                                <span v-else>{{ displayMarkup(b) }}</span>
                            </td>
                            <td class="text-end text-muted">{{ batchSuggestedLabel(b) }}</td>
                        </tr>
                        <tr v-if="!batches.length">
                            <td colspan="7" class="text-muted text-center py-4">No batches in stock yet.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="batches.length" class="table-light">
                        <tr>
                            <th colspan="3">Total</th>
                            <th class="text-end">{{ formatQty(stockBase) }}</th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import ProductTypeLabel from '@/Components/Catalog/ProductTypeLabel.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { suggestedUnitPrice } from '@/composables/useBatchPricing';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { defaultSellUnit, unitLabel, unitSalePrice } from '@/composables/useProductUnits';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    product: { type: Object, required: true },
    stockBase: { type: String, default: '0' },
    purchasedQuantity: { type: String, default: '0' },
    stockByUnit: { type: Array, default: () => [] },
    stockPieces: { type: String, default: null },
});

const page = usePage();
const wholesaleEnabled = computed(() => page.props.features?.wholesale_pricing ?? false);

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();

const batchMarkups = reactive({});

const batches = computed(() => {
    const raw = props.product.batches;
    if (!raw) {
        return [];
    }
    return Array.isArray(raw) ? raw : raw.data ?? [];
});

watch(
    batches,
    (list) => {
        for (const b of list) {
            if (batchMarkups[b.id] === undefined) {
                batchMarkups[b.id] = b.markup_percent ?? '';
            }
        }
    },
    { immediate: true },
);

function formatLocation(loc) {
    if (!loc) {
        return '—';
    }

    return loc.code ? `${loc.name} (${loc.code})` : loc.name;
}

const defaultShelfLabel = computed(() =>
    formatLocation(props.product.storage_location ?? props.product.effective_storage_location),
);

function batchShelfLabel(batch) {
    return formatLocation(batch.effective_storage_location ?? batch.storage_location);
}

function formatQty(value) {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) {
        return '0';
    }
    return n % 1 === 0 ? String(n) : n.toFixed(2);
}

function displayMarkup(batch) {
    const value = batch.markup_percent ?? props.product.default_markup_percent;
    return value != null && value !== '' ? `${value}%` : '—';
}

function batchSuggestedLabel(batch) {
    const sellUnit = defaultSellUnit(props.product);
    const suggested = suggestedUnitPrice(batch, props.product, sellUnit, props.product.units);
    if (suggested !== null) {
        return formatMoney(suggested);
    }

    return formatMoney(unitSalePrice(props.product, sellUnit));
}

function saveBatchMarkup(batch) {
    const value = batchMarkups[batch.id];
    router.patch(
        `/products/${props.product.id}/batches/${batch.id}/markup`,
        { markup_percent: value === '' || value === null ? null : value },
        { preserveScroll: true },
    );
}

function confirmDelete() {
    if (!window.confirm(`Delete product "${props.product.name}"? This cannot be undone.`)) {
        return;
    }
    router.delete(`/products/${props.product.id}`);
}
</script>
