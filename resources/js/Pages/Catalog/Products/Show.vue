<template>
    <TenantShellLayout :page-title="product.name">
        <Head :title="product.name" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <Link href="/products" class="small text-decoration-none">← Products</Link>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h1 class="h4 mb-0">{{ product.name }}</h1>
                    <span
                        class="badge"
                        :class="product.is_active ? 'text-bg-success' : 'text-bg-secondary'"
                    >
                        {{ product.is_active ? t('common.active') : t('common.inactive') }}
                    </span>
                </div>
                <p v-if="product.generic_name || product.strength" class="text-muted small mb-1">
                    <span v-if="product.generic_name">{{ product.generic_name }}</span>
                    <span v-if="product.generic_name && product.strength"> · </span>
                    <span v-if="product.strength">{{ product.strength }}</span>
                </p>
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
                    <div class="card-header bg-white fw-semibold">{{ t('catalog.product_details') }}</div>
                    <div class="card-body small">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ t('catalog.product_type') }}</dt>
                            <dd class="col-sm-8"><ProductTypeLabel :type="product.product_type" /></dd>

                            <dt class="col-sm-4">{{ t('catalog.category') }}</dt>
                            <dd class="col-sm-8">{{ product.category?.name ?? '—' }}</dd>

                            <dt class="col-sm-4">{{ t('catalog.manufacturer') }}</dt>
                            <dd class="col-sm-8">{{ product.manufacturer?.name ?? '—' }}</dd>

                            <dt class="col-sm-4">{{ t('catalog.base_unit') }}</dt>
                            <dd class="col-sm-8 text-capitalize">{{ unitLabel(product.base_unit) }}</dd>

                            <dt v-if="product.pieces_per_strip" class="col-sm-4">{{ t('catalog.pieces_per_strip') }}</dt>
                            <dd v-if="product.pieces_per_strip" class="col-sm-8">{{ formatQty(product.pieces_per_strip) }}</dd>

                            <dt v-if="product.strips_per_box" class="col-sm-4">{{ t('catalog.strips_per_box') }}</dt>
                            <dd v-if="product.strips_per_box" class="col-sm-8">{{ formatQty(product.strips_per_box) }}</dd>

                            <dt v-if="product.boxes_per_carton" class="col-sm-4">{{ t('catalog.boxes_per_carton') }}</dt>
                            <dd v-if="product.boxes_per_carton" class="col-sm-8">{{ formatQty(product.boxes_per_carton) }}</dd>

                            <dt class="col-sm-4">{{ t('catalog.default_storage_location') }}</dt>
                            <dd class="col-sm-8">{{ defaultShelfLabel }}</dd>

                            <dt class="col-sm-4">{{ t('catalog.unit_price_markup_percent') }}</dt>
                            <dd class="col-sm-8">
                                <div>{{ productMarkupPercentLabel }}</div>
                                <div v-if="productMarkupPercentDerived" class="text-muted small">
                                    {{ t('catalog.unit_price_markup_hint', { unit: unitLabel(productMarkupSellUnit) }) }}
                                </div>
                            </dd>

                            <dt class="col-sm-4">{{ t('catalog.min_stock_alert') }}</dt>
                            <dd class="col-sm-8">
                                {{ product.min_stock != null && product.min_stock !== '' ? formatQty(product.min_stock) : '—' }}
                            </dd>

                            <dt v-if="wholesaleEnabled && product.wholesale_price" class="col-sm-4">Wholesale</dt>
                            <dd v-if="wholesaleEnabled && product.wholesale_price" class="col-sm-8">
                                {{ formatMoney(product.wholesale_price) }}
                            </dd>

                            <dt v-if="advancedCatalogEnabled && product.vat_percent" class="col-sm-4">{{ t('catalog.vat_percent') }}</dt>
                            <dd v-if="advancedCatalogEnabled && product.vat_percent" class="col-sm-8">
                                {{ product.vat_percent }}%
                            </dd>

                            <dt v-if="advancedCatalogEnabled && product.short_description" class="col-sm-4">{{ t('catalog.short_description') }}</dt>
                            <dd v-if="advancedCatalogEnabled && product.short_description" class="col-sm-8">
                                {{ product.short_description }}
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

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <div class="fw-semibold">{{ t('catalog.sell_units') }}</div>
                <p class="small text-muted mb-0 mt-1">{{ t('catalog.unit_prices_product_default') }}</p>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('catalog.sell_unit') }}</th>
                            <th>{{ t('catalog.pack_relation') }}</th>
                            <th class="text-end">{{ t('catalog.purchase_price') }}</th>
                            <th class="text-end">{{ t('catalog.sale_price') }}</th>
                            <th class="text-end">{{ t('catalog.batch_markup_percent') }}</th>
                            <th>{{ t('catalog.default_unit') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in product.units" :key="u.sell_unit">
                            <td class="text-capitalize">{{ unitLabel(u.sell_unit) }}</td>
                            <td>
                                <div class="fw-medium">{{ unitRelationLabel(u) }}</div>
                            </td>
                            <td class="text-end">
                                <div>{{ formatMoneyAfterCode(u.purchase_price) }}</div>
                                <div class="text-muted small">
                                    {{ t('catalog.batch_per_unit', { unit: unitLabel(u.sell_unit) }) }}
                                </div>
                            </td>
                            <td class="text-end">
                                <div>{{ formatMoneyAfterCode(u.sale_price) }}</div>
                                <div class="text-muted small">
                                    {{ t('catalog.batch_per_unit', { unit: unitLabel(u.sell_unit) }) }}
                                </div>
                            </td>
                            <td class="text-end">
                                <span class="badge text-bg-light border">{{ unitMarkupLabel(u) }}</span>
                            </td>
                            <td>
                                <span v-if="u.is_default" class="badge text-bg-primary">{{ t('catalog.default_unit') }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                        </tr>
                        <tr v-if="!product.units?.length">
                            <td colspan="6" class="text-muted text-center py-3">No sell units configured.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="fw-semibold">{{ t('catalog.batch_pricing_title') }}</div>
                <p class="small text-muted mb-0 mt-1">{{ t('catalog.batch_markup_help') }}</p>
                <p class="small text-muted mb-0">{{ t('catalog.batch_sale_price_hint') }}</p>
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
                            <th class="text-end">{{ t('catalog.batch_sale_price') }}</th>
                            <th class="text-end">{{ t('catalog.batch_markup_percent') }}</th>
                            <th class="text-end">{{ t('catalog.batch_suggested_price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="b in batches" :key="b.id" :class="{ 'table-warning': b.is_expired }">
                            <td>{{ b.batch_no }}</td>
                            <td>
                                <span>{{ b.expiry_date || '—' }}</span>
                                <span v-if="b.is_expired" class="badge text-bg-danger ms-1">{{ t('catalog.batch_expired') }}</span>
                            </td>
                            <td class="small">{{ batchShelfLabel(b) }}</td>
                            <td class="text-end fw-semibold">{{ formatQty(b.quantity_on_hand) }}</td>
                            <td class="text-end">
                                <div class="fw-medium">
                                    {{ formatMoneyAfterCode(b.purchase_unit_cost) }}
                                    <span class="text-muted small fw-normal">
                                        {{ t('catalog.batch_per_unit', { unit: unitLabel(batchStoredPriceUnit(b, product.base_unit)) }) }}
                                    </span>
                                </div>
                                <div
                                    v-if="batchStoredPriceDiffersFromBase(b, product.base_unit)"
                                    class="text-muted small"
                                >
                                    {{
                                        t('catalog.batch_cost_in_base_unit', {
                                            amount: formatMoneyAfterCode(batchBaseUnitCost(b)),
                                            unit: unitLabel(product.base_unit),
                                        })
                                    }}
                                </div>
                            </td>
                            <td class="text-end">
                                <div v-if="batchEffectiveStoredSalePrice(b) !== null">
                                    {{ formatMoneyAfterCode(batchEffectiveStoredSalePrice(b)) }}
                                    <span class="text-muted small d-block">
                                        {{ t('catalog.batch_per_unit', { unit: unitLabel(batchStoredPriceUnit(b, product.base_unit)) }) }}
                                    </span>
                                </div>
                                <span v-else class="text-muted small">{{ t('catalog.uses_markup_or_catalog') }}</span>
                            </td>
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
                                        placeholder="—"
                                    />
                                    <button type="submit" class="btn btn-sm btn-outline-primary py-0">✓</button>
                                </form>
                                <span v-else>{{ displayMarkup(b) }}</span>
                            </td>
                            <td class="text-end text-muted">
                                <div>{{ batchSuggestedLabel(b) }}</div>
                                <div class="small">
                                    {{ t('catalog.batch_per_unit', { unit: unitLabel(defaultSellUnit(product)) }) }}
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!batches.length">
                            <td colspan="8" class="text-muted text-center py-4">No batches in stock yet.</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="batches.length" class="table-light">
                        <tr>
                            <th colspan="3">Total</th>
                            <th class="text-end">{{ formatQty(stockBase) }}</th>
                            <th colspan="4"></th>
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
import {
    suggestedUnitPrice,
    batchSalePriceInSellUnit,
    batchStoredPriceUnit,
    batchBaseUnitCost,
    batchStoredPriceDiffersFromBase,
} from '@/composables/useBatchPricing';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import { defaultSellUnit, unitLabel, unitPurchasePrice, unitSalePrice } from '@/composables/useProductUnits';
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
const advancedCatalogEnabled = computed(() => page.props.features?.advanced_catalog ?? true);

const { t } = useLocale();
const { formatMoney, currencyCode } = useMoney();
const { formatQty } = useQuantity();
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
                batchMarkups[b.id] = batchMarkupInputValue(b);
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

const productMarkupSellUnit = computed(() => defaultSellUnit(props.product));

const productHasConfiguredMarkup = computed(() =>
    props.product.default_markup_percent !== null && props.product.default_markup_percent !== '',
);

const productMarkupPercent = computed(() => {
    if (productHasConfiguredMarkup.value) {
        const configuredMarkup = Number(props.product.default_markup_percent);
        return Number.isNaN(configuredMarkup) ? null : configuredMarkup;
    }

    const purchasePrice = unitPurchasePrice(props.product, productMarkupSellUnit.value);
    const salePrice = unitSalePrice(props.product, productMarkupSellUnit.value);

    if (purchasePrice <= 0 || salePrice <= 0) {
        return null;
    }

    return ((salePrice - purchasePrice) / purchasePrice) * 100;
});

const productMarkupPercentDerived = computed(() =>
    !productHasConfiguredMarkup.value && productMarkupPercent.value !== null,
);

const productMarkupPercentLabel = computed(() => {
    if (productMarkupPercent.value === null) {
        return '—';
    }

    return `${formatQty(productMarkupPercent.value)}%`;
});

function formatMoneyAfterCode(amount) {
    return `${formatQty(amount)} ${currencyCode()}`;
}

function unitRelationLabel(unit) {
    const factor = Number(unit.conversion_factor ?? 0);
    const sellUnit = unitLabel(unit.sell_unit);
    const baseUnit = unitLabel(props.product.base_unit);

    if (unit.sell_unit === props.product.base_unit || factor === 1) {
        return t('catalog.base_unit_relation');
    }

    if (unit.sell_unit === 'piece' && props.product.base_unit === 'strip' && factor > 0 && factor < 1) {
        return t('catalog.piece_unit_relation', {
            pieces: formatQty(1 / factor),
            base: baseUnit,
        });
    }

    return t('catalog.sell_unit_relation', {
        sell_unit: sellUnit,
        qty: formatQty(factor),
        base: baseUnit,
    });
}

function unitMarkupLabel(unit) {
    const purchasePrice = Number(unit.purchase_price ?? 0);
    const salePrice = Number(unit.sale_price ?? 0);

    if (purchasePrice <= 0 || salePrice <= 0) {
        return '—';
    }

    return `${formatPercentInput(((salePrice - purchasePrice) / purchasePrice) * 100)}%`;
}

function batchShelfLabel(batch) {
    return formatLocation(batch.effective_storage_location ?? batch.storage_location);
}

function displayMarkup(batch) {
    const value = batch.markup_percent ?? batchCalculatedMarkupPercent(batch) ?? props.product.default_markup_percent;
    const formatted = formatPercentInput(value);
    return formatted !== '' ? `${formatted}%` : '—';
}

function batchMarkupInputValue(batch) {
    return batch.markup_percent ?? formatPercentInput(batchCalculatedMarkupPercent(batch));
}

function batchCalculatedMarkupPercent(batch) {
    if (!hasBatchSalePrice(batch)) {
        return null;
    }

    const cost = Number(batch.purchase_unit_cost ?? 0);
    const salePrice = Number(batch.sale_price ?? 0);

    if (cost <= 0 || salePrice <= 0) {
        return null;
    }

    return ((salePrice - cost) / cost) * 100;
}

function formatPercentInput(value) {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    const number = Number(value);
    if (Number.isNaN(number)) {
        return '';
    }

    return Number(number.toFixed(2)).toString();
}

function hasBatchSalePrice(batch) {
    return batch.sale_price !== null && batch.sale_price !== undefined && batch.sale_price !== '';
}

function batchSalePriceFromMarkupInput(batch) {
    const markup = batchMarkups[batch.id];
    if (markup === '' || markup === null || markup === undefined) {
        return null;
    }

    const markupNumber = Number(markup);
    const cost = Number(batch.purchase_unit_cost ?? 0);

    if (Number.isNaN(markupNumber) || cost <= 0) {
        return null;
    }

    return Math.round(cost * (1 + markupNumber / 100) * 10000) / 10000;
}

function batchEffectiveStoredSalePrice(batch) {
    return batchSalePriceFromMarkupInput(batch) ?? (hasBatchSalePrice(batch) ? Number(batch.sale_price) : null);
}

function batchSuggestedLabel(batch) {
    const sellUnit = defaultSellUnit(props.product);
    const effectiveBatch = {
        ...batch,
        sale_price: batchEffectiveStoredSalePrice(batch),
    };
    const mrp = batchSalePriceInSellUnit(effectiveBatch, sellUnit, props.product.units);
    if (mrp !== null) {
        return formatMoneyAfterCode(mrp);
    }

    const suggested = suggestedUnitPrice(effectiveBatch, props.product, sellUnit, props.product.units);
    if (suggested !== null) {
        return formatMoneyAfterCode(suggested);
    }

    return formatMoneyAfterCode(unitSalePrice(props.product, sellUnit));
}

function saveBatchMarkup(batch) {
    const value = batchMarkups[batch.id];
    const payload = {
        markup_percent: value === '' || value === null ? null : value,
    };
    const salePrice = batchSalePriceFromMarkupInput(batch);
    if (salePrice !== null) {
        payload.sale_price = salePrice;
    }

    router.patch(
        `/products/${props.product.id}/batches/${batch.id}/markup`,
        payload,
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
