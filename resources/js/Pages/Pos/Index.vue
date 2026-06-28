<template>
    <TenantShellLayout :page-title="t('sales.pos_title')">
        <Head title="POS" />
        <h1 class="h4 mb-3 d-lg-none">{{ t('sales.pos_title') }}</h1>
        <div v-if="checkoutError" class="alert alert-danger">{{ checkoutError }}</div>
        <div v-if="showSaleSuccessAlert" class="alert alert-success pos-sale-alert">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>{{ saleSuccessMessage }}</span>
                <div class="d-flex align-items-center gap-2">
                    <a :href="`/sales/${lastSaleId}/print`" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">
                        {{ t('sales.pos_print_last') }}
                    </a>
                    <button type="button" class="btn-close" aria-label="Close" @click="closeSaleSuccessAlert"></button>
                </div>
            </div>
            <div :key="saleSuccessAlertKey" class="pos-sale-alert__timer" aria-hidden="true"></div>
        </div>
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card card-body">
                    <label class="form-label">{{ t('sales.pos_search_product') }}</label>
                    <input
                        ref="searchInput"
                        v-model="q"
                        type="search"
                        class="form-control"
                        :placeholder="t('catalog.products_search_placeholder')"
                        autocomplete="off"
                        @input="debouncedSearch"
                        @keydown.enter.prevent="onSearchEnter"
                    />
                    <p class="form-text small mb-0">{{ t('sales.pos_scan_hint') }}</p>
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
                    <div v-if="showQuickProducts" class="mt-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <div class="small fw-semibold text-muted">{{ t('sales.pos_quick_products') }}</div>
                            <div class="btn-group btn-group-sm" role="group" :aria-label="t('sales.pos_quick_products')">
                                <button
                                    v-for="tab in quickProductTabs"
                                    :key="tab.key"
                                    type="button"
                                    class="btn"
                                    :class="activeQuickProductTab === tab.key ? 'btn-primary' : 'btn-outline-secondary'"
                                    @click="activeQuickProductTab = tab.key"
                                >
                                    {{ tab.label }}
                                </button>
                            </div>
                        </div>
                        <div v-if="activeQuickProducts.length" class="pos-product-grid">
                            <button
                                v-for="product in activeQuickProducts"
                                :key="product.id"
                                type="button"
                                class="pos-product-card text-start"
                                :disabled="!hasSellableStock(product)"
                                @click="addLine(product)"
                            >
                                <span class="pos-product-card__image-wrap">
                                    <img
                                        class="pos-product-card__image"
                                        :src="product.image_url || '/images/product-placeholder.png'"
                                        :alt="product.name"
                                        loading="lazy"
                                    />
                                </span>
                                <span class="pos-product-card__name">{{ product.name }}</span>
                                <span class="pos-product-card__meta">
                                    <span v-if="product.strength">{{ product.strength }}</span>
                                    <span v-if="product.sku">{{ product.sku }}</span>
                                </span>
                                <span class="d-flex justify-content-between align-items-end gap-2 mt-auto">
                                    <span class="small text-muted">{{ t('sales.pos_stock') }} {{ product.stock_on_hand ?? '0' }}</span>
                                    <strong class="small">{{ formatMoney(product.sale_price) }}</strong>
                                </span>
                            </button>
                        </div>
                        <p v-else class="text-muted small mb-0">{{ t('sales.pos_no_quick_products') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card card-body">
                    <h2 class="h6">{{ t('sales.cart') }}</h2>
                    <p v-if="!cart.length" class="text-muted small">{{ t('sales.cart_empty_hint') }}</p>
                    <table v-else class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ t('sales.item') }}</th>
                                <th style="width: 6rem">{{ t('catalog.sell_unit') }}</th>
                                <th style="width: 6rem">{{ t('sales.qty') }}</th>
                                <th style="width: 8rem">{{ t('sales.unit_price') }} ({{ currencyCode() }})</th>
                                <th class="text-end">{{ t('sales.line_total') }}</th>
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
                                    <div v-if="selectedBatch(line)?.is_expired" class="small text-danger">
                                        {{ t('catalog.pos_batch_expired') }}
                                    </div>
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
                                <td colspan="4" class="text-end">{{ t('sales.subtotal') }}</td>
                                <td class="text-end">{{ formatMoney(cartSubtotal) }}</td>
                                <td></td>
                            </tr>
                            <tr v-if="cartDiscountAmount > 0">
                                <td colspan="4" class="text-end text-muted">
                                    {{ t('sales.pos_discount') }} ({{ cartDiscountPercent }}%)
                                </td>
                                <td class="text-end text-danger">−{{ formatMoney(cartDiscountAmount) }}</td>
                                <td></td>
                            </tr>
                            <tr v-if="cartDiscountAmount > 0 || roundAdjustment !== 0">
                                <td colspan="4" class="text-end">{{ t('sales.total') }}</td>
                                <td class="text-end">{{ formatMoney(totalAfterDiscount) }}</td>
                                <td></td>
                            </tr>
                            <tr v-if="roundAdjustment !== 0">
                                <td colspan="4" class="text-end text-muted">
                                    {{ t('sales.round_adjustment') }}
                                </td>
                                <td class="text-end" :class="roundAdjustment > 0 ? 'text-success' : 'text-danger'">
                                    {{ roundAdjustment > 0 ? '+' : '' }}{{ formatMoney(roundAdjustment) }}
                                </td>
                                <td></td>
                            </tr>
                            <tr v-if="roundAdjustment !== 0" class="table-primary">
                                <td colspan="4" class="text-end fw-bold">{{ t('sales.payable_amount') }}</td>
                                <td class="text-end fw-bold">{{ formatMoney(payableAmount) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <form class="mt-3" @submit.prevent="submitSale">
                        <div class="mb-2">
                            <label class="form-label">{{ t('sales.pos_customer') }}</label>

                            <!-- Selected customer display -->
                            <div v-if="selectedCustomer" class="d-flex align-items-center gap-2 p-2 bg-light rounded mb-2">
                                <div class="flex-grow-1">
                                    <strong>{{ selectedCustomer.name }}</strong>
                                    <span v-if="selectedCustomer.phone" class="text-muted ms-1">({{ selectedCustomer.phone }})</span>
                                    <span v-if="Number(selectedCustomer.balance_due) > 0" class="text-warning ms-2">
                                        {{ t('sales.pos_customer_due', { amount: formatMoney(selectedCustomer.balance_due) }) }}
                                    </span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearCustomer">×</button>
                            </div>

                            <!-- Customer search (shown when no customer selected and not adding new) -->
                            <div v-if="!selectedCustomer && !showNewCustomerForm">
                                <input
                                    v-model="customerQuery"
                                    type="search"
                                    class="form-control form-control-sm"
                                    :placeholder="t('sales.pos_customer_search_placeholder')"
                                    autocomplete="off"
                                    @input="debouncedCustomerSearch"
                                />
                                <ul v-if="customerResults.length" class="list-group list-group-flush mt-1 small" style="max-height: 150px; overflow-y: auto">
                                    <li
                                        v-for="c in customerResults"
                                        :key="c.id"
                                        class="list-group-item list-group-item-action py-1 px-2"
                                        @click="selectCustomer(c)"
                                    >
                                        {{ c.name }}
                                        <span v-if="c.phone" class="text-muted">({{ c.phone }})</span>
                                        <span v-if="Number(c.balance_due) > 0" class="text-warning float-end">
                                            {{ t('sales.due') }}: {{ formatMoney(c.balance_due) }}
                                        </span>
                                    </li>
                                </ul>
                                <button type="button" class="btn btn-sm btn-link p-0 mt-1" @click="toggleNewCustomerForm">
                                    + {{ t('sales.pos_add_new_customer') }}
                                </button>
                            </div>

                            <!-- New customer form -->
                            <div v-if="showNewCustomerForm && !selectedCustomer" class="border rounded p-2 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small fw-semibold">{{ t('sales.pos_new_customer') }}</span>
                                    <button type="button" class="btn btn-sm btn-link p-0" @click="toggleNewCustomerForm">{{ t('common.cancel') }}</button>
                                </div>
                                <div class="row g-2">
                                    <div class="col-7">
                                        <input
                                            v-model="newCustomerName"
                                            type="text"
                                            class="form-control form-control-sm"
                                            :placeholder="t('sales.customer_name')"
                                            required
                                        />
                                    </div>
                                    <div class="col-5">
                                        <input
                                            v-model="newCustomerPhone"
                                            type="text"
                                            class="form-control form-control-sm"
                                            :placeholder="t('sales.customer_phone')"
                                        />
                                    </div>
                                </div>
                            </div>

                            <p v-if="!selectedCustomer && !showNewCustomerForm" class="form-text small mb-0">
                                {{ t('sales.pos_customer_optional_hint') }}
                            </p>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label">{{ t('sales.pos_discount') }}</label>
                                <div class="input-group input-group-sm">
                                    <input
                                        v-model.number="cartDiscountPercent"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="form-control"
                                    />
                                    <span class="input-group-text">%</span>
                                </div>
                                <p v-if="cartDiscountAmount > 0" class="form-text small mb-0">
                                    {{ t('sales.pos_discount_amount', { amount: formatMoney(cartDiscountAmount) }) }}
                                </p>
                            </div>
                            <div class="col-6">
                                <label class="form-label">{{ t('sales.pos_amount_tendered') }}</label>
                                <input
                                    v-model.number="amountPaid"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="form-control form-control-sm"
                                    @input="onAmountPaidInput"
                                />
                            </div>
                        </div>
                        <div class="mb-2 d-flex flex-wrap gap-2 align-items-center small">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="setPayFull">
                                {{ t('sales.pos_pay_full') }}
                            </button>
                            <span v-if="changePreview > 0.001" class="text-success fw-semibold">
                                {{ t('sales.pos_change_preview', { amount: formatMoney(changePreview) }) }}
                            </span>
                            <span v-else-if="duePreview > 0.001" class="text-warning">
                                {{ t('sales.pos_due_preview', { amount: formatMoney(duePreview) }) }}
                            </span>
                        </div>
                        <div v-if="needsCustomerForDue" class="alert alert-warning py-2 small mb-2">
                            {{ t('sales.due_requires_customer') }}
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ t('sales.payment_method') }}</label>
                            <select v-model="paymentMethod" class="form-select">
                                <option value="cash">{{ t('purchases.payment_cash') }}</option>
                                <option value="card">{{ t('purchases.payment_card') }}</option>
                                <option value="mobile">{{ t('purchases.payment_mobile') }}</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">{{ t('sales.coupon_code') }} <span class="text-muted fw-normal">({{ t('common.optional') }})</span></label>
                            <input v-model="couponCode" type="text" class="form-control form-control-sm text-uppercase" placeholder="SAVE10" autocomplete="off" />
                        </div>
                        <button type="submit" class="btn btn-success" :disabled="!cart.length || submitting || needsCustomerForDue">{{ t('sales.complete_sale') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import {
    batchSalePriceInSellUnit,
    lineMarginPercent,
    resolveMarkupPercent,
    suggestedUnitPrice,
    unitCostInSellUnit,
} from '@/composables/useBatchPricing';
import { batchesWithStock, formatBatchLabel, onBatchChange as syncBatchFields, totalBaseStock } from '@/composables/usePosBatches';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import { defaultSellUnit, stockInSellUnit, unitLabel, unitSalePrice } from '@/composables/useProductUnits';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    lastSaleId: { type: Number, default: null },
    roundingMode: { type: String, default: 'none' },
    quickProducts: { type: Object, default: () => ({ popular: [], latest: [], lastSold: [] }) },
});

const { t } = useLocale();
const { formatMoney, currencyCode } = useMoney();
const { formatQty } = useQuantity();
const page = usePage();
const markupPricingEnabled = computed(() => page.props.features?.markup_pricing ?? false);
const checkoutError = computed(() => page.props.errors?.checkout ?? null);
const saleSuccessMessage = computed(() => page.props.flash?.success || 'Sale completed.');
const showSaleSuccessAlert = ref(Boolean(props.lastSaleId));
const saleSuccessAlertKey = ref(0);
let saleSuccessTimer = null;

const q = ref('');
const results = ref([]);
const cart = ref([]);
const activeQuickProductTab = ref('popular');

// Customer selection
const customerQuery = ref('');
const customerResults = ref([]);
const selectedCustomer = ref(null);
const showNewCustomerForm = ref(false);
const newCustomerName = ref('');
const newCustomerPhone = ref('');
let customerTimer;

const cartDiscountPercent = ref(0);
const amountPaid = ref(0);
const payFullAmount = ref(true);
const paymentMethod = ref('cash');
const couponCode = ref('');
const submitting = ref(false);
const searchInput = ref(null);
let timer;

const cartSubtotal = computed(() =>
    cart.value.reduce((s, l) => s + Number(l.quantity || 0) * Number(l.unit_price || 0), 0),
);

const cartDiscountAmount = computed(() => {
    const pct = Math.min(100, Math.max(0, Number(cartDiscountPercent.value) || 0));

    return Math.round(((cartSubtotal.value * pct) / 100) * 100) / 100;
});

const totalAfterDiscount = computed(() => Math.max(0, cartSubtotal.value - cartDiscountAmount.value));

const roundAdjustment = computed(() => {
    if (props.roundingMode === 'nearest_1') {
        const rounded = Math.round(totalAfterDiscount.value);
        return Math.round((rounded - totalAfterDiscount.value) * 100) / 100;
    }
    return 0;
});

const payableAmount = computed(() => {
    if (props.roundingMode === 'nearest_1') {
        return Math.round(totalAfterDiscount.value);
    }
    return totalAfterDiscount.value;
});

const duePreview = computed(() => Math.max(0, payableAmount.value - Number(amountPaid.value || 0)));

const changePreview = computed(() => Math.max(0, Number(amountPaid.value || 0) - payableAmount.value));

const hasCustomer = computed(() => selectedCustomer.value || (showNewCustomerForm.value && newCustomerName.value.trim()));

const needsCustomerForDue = computed(() => duePreview.value > 0.001 && !hasCustomer.value);

const quickProductTabs = computed(() => [
    { key: 'popular', label: t('sales.pos_popular_products') },
    { key: 'latest', label: t('sales.pos_latest_products') },
    { key: 'lastSold', label: t('sales.pos_last_sold_products') },
]);

const activeQuickProducts = computed(() => props.quickProducts?.[activeQuickProductTab.value] ?? []);
const showQuickProducts = computed(() => q.value.trim().length === 0 && !results.value.length);

function startSaleSuccessAlert() {
    clearTimeout(saleSuccessTimer);
    saleSuccessAlertKey.value += 1;
    showSaleSuccessAlert.value = true;
    saleSuccessTimer = setTimeout(() => {
        showSaleSuccessAlert.value = false;
    }, 15000);
}

function closeSaleSuccessAlert() {
    showSaleSuccessAlert.value = false;
    clearTimeout(saleSuccessTimer);
}

onBeforeUnmount(() => {
    clearTimeout(saleSuccessTimer);
});

watch(
    () => props.lastSaleId,
    (lastSaleId) => {
        if (lastSaleId) {
            startSaleSuccessAlert();
        }
    },
    { immediate: true },
);

watch([cartSubtotal, cartDiscountPercent, payableAmount], () => {
    if (payFullAmount.value) {
        amountPaid.value = payableAmount.value;
    }
});

function setPayFull() {
    payFullAmount.value = true;
    amountPaid.value = payableAmount.value;
}

function onAmountPaidInput() {
    payFullAmount.value = false;
}

// Customer search functions
function debouncedCustomerSearch() {
    clearTimeout(customerTimer);
    customerTimer = setTimeout(runCustomerSearch, 250);
}

async function runCustomerSearch() {
    if (customerQuery.value.length < 1) {
        customerResults.value = [];
        return;
    }
    const { data } = await window.axios.get('/sales/customer-search', { params: { q: customerQuery.value } });
    customerResults.value = data.data;
}

function selectCustomer(customer) {
    selectedCustomer.value = customer;
    customerQuery.value = '';
    customerResults.value = [];
    showNewCustomerForm.value = false;
    newCustomerName.value = '';
    newCustomerPhone.value = '';
}

function clearCustomer() {
    selectedCustomer.value = null;
    customerQuery.value = '';
    customerResults.value = [];
}

function toggleNewCustomerForm() {
    showNewCustomerForm.value = !showNewCustomerForm.value;
    if (showNewCustomerForm.value) {
        selectedCustomer.value = null;
        customerQuery.value = '';
        customerResults.value = [];
    }
}

function debouncedSearch() {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 250);
}

async function runSearch() {
    if (q.value.length < 1) {
        results.value = [];
        return;
    }
    const { data } = await window.axios.get('/catalog/product-search', { params: { q: q.value } });
    results.value = data.data;
}

async function onSearchEnter() {
    await runSearch();
    tryAddFromSearch();
}

function tryAddFromSearch() {
    const term = q.value.trim();
    if (!term || !results.value.length) {
        return;
    }
    const exactBarcode = results.value.find((r) => r.barcode === term);
    const pick = exactBarcode ?? (results.value.length === 1 ? results.value[0] : null);
    if (!pick) {
        return;
    }
    addLine(pick);
    q.value = '';
    results.value = [];
    searchInput.value?.focus();
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

function hasSellableStock(item) {
    return batchesWithStock(item).length > 0;
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

    const batchPrice = batchSalePriceInSellUnit(batch, line.sell_unit, line.unit_options);
    if (batchPrice !== null) {
        line.unit_price = batchPrice;
        line.price_from_batch = true;
        line.uses_markup_pricing = false;
        return;
    }

    const suggested = markupPricingEnabled.value
        ? suggestedUnitPrice(batch, product, line.sell_unit, line.unit_options)
        : null;
    line.price_from_batch = false;
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
    if (line.price_from_batch) {
        return t('catalog.pos_price_from_batch', { price: formatMoney(line.unit_price) });
    }
    if (markupPricingEnabled.value && line.uses_markup_pricing) {
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
        alert(t('catalog.pos_no_sellable_batch'));
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

    const payload = {
        lines: cart.value.map((l) => ({
            product_batch_id: l.product_batch_id,
            quantity: Math.max(1, Math.round(Number(l.quantity) || 1)),
            sell_unit: l.sell_unit,
            unit_price: l.unit_price,
        })),
        customer_id: selectedCustomer.value?.id || null,
        payments: [{ method: paymentMethod.value, amount: Number(amountPaid.value || 0) }],
        discount_percent: Number(cartDiscountPercent.value || 0),
        tax: 0,
        coupon_code: couponCode.value?.trim() || null,
    };

    // Add new customer if creating on-the-fly
    if (showNewCustomerForm.value && newCustomerName.value.trim() && !selectedCustomer.value) {
        payload.new_customer = {
            name: newCustomerName.value.trim(),
            phone: newCustomerPhone.value.trim() || null,
        };
    }

    router.post(
        '/pos/sales',
        payload,
        {
            preserveScroll: true,
            onFinish: () => {
                submitting.value = false;
                cart.value = [];
                q.value = '';
                results.value = [];
                amountPaid.value = 0;
                payFullAmount.value = true;
                cartDiscountPercent.value = 0;
                paymentMethod.value = 'cash';
                couponCode.value = '';
                selectedCustomer.value = null;
                showNewCustomerForm.value = false;
                newCustomerName.value = '';
                newCustomerPhone.value = '';
            },
        },
    );
}
</script>

<style scoped>
.pos-sale-alert {
    position: relative;
    overflow: hidden;
    padding-bottom: 1rem;
}

.pos-sale-alert__timer {
    position: absolute;
    right: 0;
    bottom: 0;
    left: 0;
    height: 0.2rem;
    background: rgba(25, 135, 84, 0.2);
}

.pos-sale-alert__timer::after {
    display: block;
    width: 100%;
    height: 100%;
    content: '';
    background: var(--bs-success);
    animation: pos-sale-alert-timer 15s linear forwards;
    transform-origin: left center;
}

@keyframes pos-sale-alert-timer {
    from {
        transform: scaleX(1);
    }

    to {
        transform: scaleX(0);
    }
}

.pos-product-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.75rem;
}

.pos-product-card {
    display: flex;
    flex-direction: column;
    min-height: 10.25rem;
    padding: 0.65rem;
    color: var(--bs-body-color);
    background: #ffffff;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.35rem;
    transition: border-color 0.12s ease, box-shadow 0.12s ease, transform 0.12s ease;
}

.pos-product-card:hover:not(:disabled) {
    border-color: #adb5bd;
    box-shadow: 0 0.5rem 1rem rgba(15, 23, 42, 0.08);
    transform: translateY(-1px);
}

.pos-product-card:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}

.pos-product-card__image-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 4.75rem;
    margin-bottom: 0.55rem;
    overflow: hidden;
    background: var(--bs-tertiary-bg);
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-radius: 0.3rem;
}

.pos-product-card__image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pos-product-card__name {
    display: -webkit-box;
    overflow: hidden;
    font-weight: 600;
    line-height: 1.25;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.pos-product-card__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.25rem;
    color: var(--bs-secondary-color);
    font-size: 0.76rem;
}

@media (max-width: 575.98px) {
    .pos-product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
