<template>
    <TenantShellLayout page-title="Point of sale">
        <Head title="POS" />
        <h1 class="h4 mb-3 d-lg-none">Point of sale</h1>
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card card-body">
                    <label class="form-label">Search product</label>
                    <input v-model="q" type="search" class="form-control" placeholder="Name, SKU, or barcode" @input="debouncedSearch" />
                    <ul class="list-group mt-2 small">
                        <li
                            v-for="item in results"
                            :key="item.id"
                            class="list-group-item list-group-item-action d-flex justify-content-between"
                            @click="addLine(item)"
                        >
                            <div>
                                <span>{{ item.name }}</span>
                                <small v-if="shelfHint(item)" class="text-muted d-block">{{ shelfHint(item) }}</small>
                                <small v-if="searchBatchHint(item)" class="text-muted d-block">{{ searchBatchHint(item) }}</small>
                            </div>
                            <span class="text-muted">{{ item.sku }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card card-body">
                    <h2 class="h6">Cart</h2>
                    <p v-if="!cart.length" class="text-muted small">Add products from search.</p>
                    <table v-else class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th style="width: 6rem">Unit</th>
                                <th style="width: 6rem">Qty</th>
                                <th style="width: 8rem">Price ({{ currencyCode() }})</th>
                                <th class="text-end">Line</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(line, idx) in cart" :key="idx">
                                <td>
                                    <div>{{ line.name }}</div>
                                    <div v-if="line.batches?.length > 1" class="mt-1">
                                        <select
                                            v-model.number="line.product_batch_id"
                                            class="form-select form-select-sm"
                                            @change="onBatchChange(line)"
                                        >
                                            <option v-for="b in line.batches" :key="b.id" :value="b.id">
                                                {{ formatBatchLabel(b) }}
                                            </option>
                                        </select>
                                    </div>
                                    <div v-else class="small text-muted">{{ lineBatchLabel(line) }}</div>
                                    <div class="small text-muted">{{ lineStockHint(line) }}</div>
                                    <div v-if="linePriceSourceHint(line)" class="small" :class="line.uses_markup_pricing ? 'text-primary' : 'text-muted'">
                                        {{ linePriceSourceHint(line) }}
                                    </div>
                                    <div v-if="linePricingHint(line)" class="small text-muted">{{ linePricingHint(line) }}</div>
                                    <div v-if="lineMaySplit(line)" class="small text-warning">{{ t('catalog.pos_may_split_batches') }}</div>
                                </td>
                                <td>
                                    <select v-model="line.sell_unit" class="form-select form-select-sm" @change="onUnitChange(line)">
                                        <option v-for="u in line.unit_options" :key="u.sell_unit" :value="u.sell_unit">
                                            {{ unitLabel(u.sell_unit) }}
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <input
                                        v-model.number="line.quantity"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="form-control form-control-sm"
                                        @change="normalizeLineQuantity(line)"
                                    />
                                </td>
                                <td><input v-model.number="line.unit_price" type="number" min="0" step="0.0001" class="form-control form-control-sm" /></td>
                                <td class="text-end">{{ formatMoney(Number(line.quantity || 0) * Number(line.unit_price || 0)) }}</td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" @click="cart.splice(idx, 1)">×</button></td>
                            </tr>
                        </tbody>
                        <tfoot v-if="cart.length" class="fw-semibold">
                            <tr>
                                <td colspan="4" class="text-end">Total</td>
                                <td class="text-end">{{ formatMoney(cartTotal) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <form class="mt-3" @submit.prevent="submitSale">
                        <div class="mb-2">
                            <label class="form-label">Payment method</label>
                            <select v-model="paymentMethod" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile">Mobile</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Coupon code (optional)</label>
                            <input v-model="couponCode" type="text" class="form-control form-control-sm text-uppercase" placeholder="SAVE10" autocomplete="off" />
                        </div>
                        <button type="submit" class="btn btn-success" :disabled="!cart.length || submitting">Complete sale</button>
                    </form>
                </div>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import {
    lineMarginPercent,
    resolveMarkupPercent,
    suggestedUnitPrice,
    unitCostInSellUnit,
} from '@/composables/useBatchPricing';
import { batchesWithStock, formatBatchLabel, onBatchChange as syncBatchFields, totalBaseStock } from '@/composables/usePosBatches';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { defaultSellUnit, stockInSellUnit, unitLabel, unitSalePrice } from '@/composables/useProductUnits';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { t } = useLocale();
const { formatMoney, currencyCode } = useMoney();

const q = ref('');
const results = ref([]);
const cart = ref([]);
const paymentMethod = ref('cash');
const couponCode = ref('');
const submitting = ref(false);
let timer;

const cartTotal = computed(() =>
    cart.value.reduce((s, l) => s + Number(l.quantity || 0) * Number(l.unit_price || 0), 0),
);

function debouncedSearch() {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 250);
}

async function runSearch() {
    if (q.value.length < 2) {
        results.value = [];
        return;
    }
    const { data } = await window.axios.get('/catalog/product-search', { params: { q: q.value } });
    results.value = data.data;
}

function shelfHint(item) {
    const batch = batchesWithStock(item)[0];
    const loc = batch?.effective_storage_location ?? item.effective_storage_location ?? item.storage_location;

    if (!loc) {
        return '';
    }

    return loc.code ? `${loc.name} (${loc.code})` : loc.name;
}

function searchBatchHint(item) {
    const batches = batchesWithStock(item);
    if (!batches.length) {
        return t('catalog.pos_no_stock');
    }
    const first = formatBatchLabel(batches[0]);
    if (batches.length === 1) {
        return first;
    }

    return t('catalog.pos_fefo_batch_hint', { batch: first, count: batches.length });
}

function formatQty(value) {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) {
        return '0';
    }
    return n % 1 === 0 ? String(n) : n.toFixed(2);
}

function normalizeLineQuantity(line) {
    const n = Math.round(Number(line.quantity) || 1);
    line.quantity = Math.max(1, n);
}

function selectedBatch(line) {
    return line.batches?.find((b) => b.id === line.product_batch_id);
}

function refreshLinePricing(line) {
    const batch = selectedBatch(line);
    if (!batch) {
        return;
    }
    const product = { default_markup_percent: line.default_markup_percent };
    line.unit_cost = unitCostInSellUnit(batch, line.sell_unit, line.unit_options);
    const suggested = suggestedUnitPrice(batch, product, line.sell_unit, line.unit_options);
    line.uses_markup_pricing = suggested !== null;
    if (suggested !== null) {
        line.unit_price = suggested;
        return;
    }
    line.unit_price = unitSalePrice(
        { units: line.unit_options, sale_price: line.fallback_sale_price },
        line.sell_unit,
    );
}

function linePriceSourceHint(line) {
    const batch = selectedBatch(line);
    if (!batch) {
        return '';
    }
    if (line.uses_markup_pricing) {
        const markup = resolveMarkupPercent(batch, { default_markup_percent: line.default_markup_percent });
        const cost = line.unit_cost ?? unitCostInSellUnit(batch, line.sell_unit, line.unit_options);

        return t('catalog.pos_price_from_markup', {
            cost: formatMoney(cost),
            markup: markup ?? 0,
            price: formatMoney(line.unit_price),
        });
    }

    return t('catalog.pos_price_from_catalog');
}

function onBatchChange(line) {
    syncBatchFields(line);
    refreshLinePricing(line);
}

function onUnitChange(line) {
    refreshLinePricing(line);
}

function linePricingHint(line) {
    const batch = selectedBatch(line);
    if (!batch) {
        return '';
    }
    const cost = line.unit_cost ?? unitCostInSellUnit(batch, line.sell_unit, line.unit_options);
    const margin = lineMarginPercent(line.unit_price, cost);
    let hint = t('catalog.pos_line_cost', { cost: formatMoney(cost) });
    if (margin !== null) {
        hint += ` · ${t('catalog.pos_line_margin', { margin: margin.toFixed(1) })}`;
    }
    return hint;
}

function lineBatchLabel(line) {
    if (line.batch_no) {
        return formatBatchLabel({ batch_no: line.batch_no, expiry_date: line.expiry_date });
    }

    return formatBatchLabel(line.batches?.find((b) => b.id === line.product_batch_id));
}

function lineStockHint(line) {
    const batchAvail = stockInSellUnit({
        baseStock: line.batch_stock,
        baseUnit: line.base_unit,
        sellUnit: line.sell_unit,
        units: line.unit_options,
        piecesPerStrip: line.pieces_per_strip,
    });
    const totalAvail = stockInSellUnit({
        baseStock: line.product_stock_base,
        baseUnit: line.base_unit,
        sellUnit: line.sell_unit,
        units: line.unit_options,
        piecesPerStrip: line.pieces_per_strip,
    });
    const unit = unitLabel(line.sell_unit);
    let hint = `${t('catalog.pos_batch_stock', { qty: formatQty(batchAvail), unit })}`;
    if (line.batches?.length > 1 && totalAvail > batchAvail) {
        hint += ` · ${t('catalog.pos_total_stock', { qty: formatQty(totalAvail), unit })}`;
    }
    if (line.sell_unit !== 'piece' && line.pieces_per_strip && line.base_unit === 'strip') {
        const pieces = Number(line.batch_stock) * Number(line.pieces_per_strip);
        hint += ` (${formatQty(pieces)} pieces)`;
    }
    return hint;
}

function lineMaySplit(line) {
    const qtyBase = Number(line.quantity || 0) * conversionToBase(line);
    const batchBase = Number(line.batch_stock ?? 0);

    return (line.batches?.length ?? 0) > 1 && qtyBase > batchBase + 0.0001;
}

function conversionToBase(line) {
    const u = line.unit_options?.find((x) => x.sell_unit === line.sell_unit);
    const factor = Number(u?.conversion_factor ?? 1);

    return factor > 0 ? factor : 1;
}

function addLine(item) {
    const batches = batchesWithStock(item);
    const batch = batches[0];
    if (!batch) {
        alert(t('catalog.pos_no_stock'));
        return;
    }
    const sellUnit = defaultSellUnit(item);
    const line = {
        product_id: item.id,
        product_batch_id: batch.id,
        batch_no: batch.batch_no,
        expiry_date: batch.expiry_date,
        batches,
        default_markup_percent: item.default_markup_percent,
        name: item.name,
        base_unit: item.base_unit ?? 'strip',
        pieces_per_strip: item.pieces_per_strip ? Number(item.pieces_per_strip) : null,
        batch_stock: Number(batch.quantity_on_hand ?? 0),
        product_stock_base: totalBaseStock(batches),
        sell_unit: sellUnit,
        unit_options: item.units?.length ? item.units : [{ sell_unit: sellUnit, sale_price: item.sale_price }],
        fallback_sale_price: item.sale_price,
        quantity: 1,
        unit_price: 0,
    };
    refreshLinePricing(line);
    cart.value.push(line);
}

function submitSale() {
    cart.value.forEach(normalizeLineQuantity);
    submitting.value = true;
    router.post(
        '/pos/sales',
        {
            lines: cart.value.map((l) => ({
                product_batch_id: l.product_batch_id,
                quantity: Math.max(1, Math.round(Number(l.quantity) || 1)),
                sell_unit: l.sell_unit,
                unit_price: l.unit_price,
            })),
            payments: [{ method: paymentMethod.value, amount: cartTotal.value }],
            discount: 0,
            tax: 0,
            coupon_code: couponCode.value?.trim() || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                submitting.value = false;
                cart.value = [];
            },
        },
    );
}
</script>
