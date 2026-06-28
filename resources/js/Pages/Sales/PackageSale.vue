<template>
    <TenantShellLayout page-title="Package sale">
        <Head title="Package sale" />

        <div v-if="checkoutError" class="alert alert-danger">{{ checkoutError }}</div>
        <div v-if="showSaleSuccessAlert" class="alert alert-success package-sale-alert">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>{{ saleSuccessMessage }}</span>
                <div class="d-flex align-items-center gap-2">
                    <a :href="`/sales/${lastSaleId}/print`" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success">
                        Print last sale
                    </a>
                    <button type="button" class="btn-close" aria-label="Close" @click="closeSaleSuccessAlert"></button>
                </div>
            </div>
            <div :key="saleSuccessAlertKey" class="package-sale-alert__timer" aria-hidden="true"></div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1 d-lg-none">Package sale</h1>
                <p class="text-muted small mb-0">Select a prepared package or add products manually. Checkout stays on this page.</p>
            </div>
            <Link href="/sales/packages" class="btn btn-sm btn-outline-primary">Manage packages</Link>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card card-body border-0 shadow-sm mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h2 class="h6 mb-0">Ready packages</h2>
                        <span class="small text-muted">{{ packageTemplates.length }} active</span>
                    </div>
                    <div v-if="packageTemplates.length" class="package-template-grid">
                        <button
                            v-for="template in packageTemplates"
                            :key="template.id"
                            type="button"
                            class="package-template-card text-start"
                            @click="addPackage(template)"
                        >
                            <span class="fw-semibold d-block text-truncate">{{ template.name }}</span>
                            <span class="small text-muted d-block">{{ template.items.length }} items</span>
                            <span v-if="template.description" class="small text-muted d-block text-truncate">{{ template.description }}</span>
                            <span v-if="template.fixed_price" class="badge text-bg-primary mt-2">Fixed {{ formatMoney(template.fixed_price) }}</span>
                            <span v-else-if="template.discount_percent" class="badge text-bg-info mt-2">{{ template.discount_percent }}% off</span>
                        </button>
                    </div>
                    <p v-else class="text-muted small mb-0">No active package templates. Create one from package management.</p>
                </div>

                <div class="card card-body border-0 shadow-sm">
                    <label class="form-label">Search product</label>
                    <input v-model="q" type="search" class="form-control" placeholder="Name, SKU, or barcode" @input="debouncedSearch" />
                    <ul class="list-group mt-2 small">
                        <li
                            v-for="item in results"
                            :key="item.id"
                            class="list-group-item list-group-item-action d-flex justify-content-between gap-2"
                            @click="addLine(item)"
                        >
                            <div class="min-w-0">
                                <span class="fw-semibold d-block text-truncate">{{ item.name }}</span>
                                <small v-if="searchBatchHint(item)" class="text-muted d-block">{{ searchBatchHint(item) }}</small>
                            </div>
                            <span class="text-muted">{{ item.sku }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card card-body border-0 shadow-sm package-cart-card">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h2 class="h6 mb-0">Package cart</h2>
                        <span v-if="activePackageName" class="badge text-bg-light border">{{ activePackageName }}</span>
                    </div>
                    <p v-if="!cart.length" class="text-muted small">Choose a ready package or add products from search.</p>
                    <template v-else>
                        <div class="package-cart-cards d-lg-none">
                            <div v-for="(line, idx) in cart" :key="idx" class="package-cart-line-card border rounded bg-white p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">{{ line.name }}</div>
                                        <div class="small text-muted">{{ lineBatchLabel(line) }}</div>
                                        <div v-if="linePriceSourceHint(line)" class="small" :class="line.uses_markup_pricing ? 'text-primary' : 'text-muted'">
                                            {{ linePriceSourceHint(line) }}
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="cart.splice(idx, 1)">×</button>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small mb-1">Batch</label>
                                    <select
                                        v-if="line.batches?.length > 1"
                                        v-model.number="line.product_batch_id"
                                        class="form-select form-select-sm"
                                        @change="onBatchChange(line)"
                                    >
                                        <option v-for="b in line.batches" :key="b.id" :value="b.id">
                                            {{ formatBatchLabel(b) }}
                                        </option>
                                    </select>
                                    <div v-else class="form-control form-control-sm bg-light text-muted">{{ lineBatchLabel(line) }}</div>
                                </div>

                                <div class="package-cart-line-card__fields">
                                    <div>
                                        <label class="form-label small mb-1">Unit</label>
                                        <select v-model="line.sell_unit" class="form-select form-select-sm" @change="onUnitChange(line)">
                                            <option v-for="u in line.unit_options" :key="u.sell_unit" :value="u.sell_unit">
                                                {{ u.sell_unit }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label small mb-1">Qty</label>
                                        <input v-model.number="line.quantity" type="number" min="1" step="1" class="form-control form-control-sm" @change="normalizeLineQuantity(line)" />
                                    </div>
                                    <div>
                                        <label class="form-label small mb-1">Price</label>
                                        <input v-model.number="line.unit_price" type="number" min="0" step="0.0001" class="form-control form-control-sm" />
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center border-top mt-2 pt-2">
                                    <span class="small text-muted">Line total</span>
                                    <strong>{{ formatMoney(Number(line.quantity || 0) * Number(line.unit_price || 0)) }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive d-none d-lg-block">
                            <table class="table table-sm align-middle">
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
                                            <div v-if="linePriceSourceHint(line)" class="small" :class="line.uses_markup_pricing ? 'text-primary' : 'text-muted'">
                                                {{ linePriceSourceHint(line) }}
                                            </div>
                                        </td>
                                        <td>
                                            <select v-model="line.sell_unit" class="form-select form-select-sm" @change="onUnitChange(line)">
                                                <option v-for="u in line.unit_options" :key="u.sell_unit" :value="u.sell_unit">
                                                    {{ u.sell_unit }}
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            <input v-model.number="line.quantity" type="number" min="1" step="1" class="form-control form-control-sm" @change="normalizeLineQuantity(line)" />
                                        </td>
                                        <td><input v-model.number="line.unit_price" type="number" min="0" step="0.0001" class="form-control form-control-sm" /></td>
                                        <td class="text-end">{{ formatMoney(Number(line.quantity || 0) * Number(line.unit_price || 0)) }}</td>
                                        <td><button type="button" class="btn btn-sm btn-outline-danger" @click="cart.splice(idx, 1)">×</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <div class="package-cart-summary border rounded bg-light p-2 mt-2">
                        <div class="d-flex justify-content-between gap-2">
                            <span>Subtotal</span>
                            <strong>{{ formatMoney(cartSubtotal) }}</strong>
                        </div>
                        <div v-if="cartDiscountPercent > 0" class="d-flex justify-content-between gap-2 text-danger">
                            <span>Discount ({{ cartDiscountPercent }}%)</span>
                            <strong>-{{ formatMoney(cartDiscountAmount) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between gap-2 border-top mt-1 pt-1">
                            <span class="fw-semibold">Payable</span>
                            <strong>{{ formatMoney(payableAmount) }}</strong>
                        </div>
                    </div>

                    <div class="mb-2 mt-3">
                        <label class="form-label">Coupon code (optional)</label>
                        <input v-model="couponCode" class="form-control form-control-sm" placeholder="SAVE10" />
                    </div>
                    <form class="mt-3" @submit.prevent="submitSale">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Payment method</label>
                                <select v-model="paymentMethod" class="form-select">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="mobile">Mobile</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount received</label>
                                <input v-model.number="amountTendered" type="number" min="0" step="0.0001" class="form-control" />
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100 mt-3" :disabled="!cart.length || submitting">
                            {{ submitting ? 'Completing...' : 'Complete package sale' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { batchSalePriceInSellUnit, resolveMarkupPercent, suggestedUnitPrice, unitCostInSellUnit } from '@/composables/useBatchPricing';
import { batchesWithStock, formatBatchLabel, onBatchChange as syncBatchFields } from '@/composables/usePosBatches';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { defaultSellUnit, unitSalePrice } from '@/composables/useProductUnits';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    lastSaleId: { type: [Number, String, null], default: null },
    packageTemplates: { type: Array, default: () => [] },
});

const { t } = useLocale();
const { formatMoney, currencyCode } = useMoney();
const page = usePage();
const markupPricingEnabled = computed(() => page.props.features?.markup_pricing ?? false);
const checkoutError = computed(() => page.props.errors?.checkout || '');

const q = ref('');
const results = ref([]);
const cart = ref([]);
const paymentMethod = ref('cash');
const couponCode = ref('');
const submitting = ref(false);
const selectedPackageDiscount = ref(0);
const selectedPackageFixedPrice = ref(null);
const activePackageName = ref('');
const saleSuccessAlertKey = ref(0);
const saleSuccessDismissed = ref(false);
let timer;

const lastSaleId = computed(() => props.lastSaleId);
const showSaleSuccessAlert = computed(() => Boolean(lastSaleId.value) && !saleSuccessDismissed.value);
const saleSuccessMessage = computed(() => page.props.flash?.success || 'Package sale completed.');

const cartSubtotal = computed(() =>
    cart.value.reduce((s, l) => s + Number(l.quantity || 0) * Number(l.unit_price || 0), 0),
);

const cartDiscountPercent = computed(() => {
    if (selectedPackageFixedPrice.value !== null && cartSubtotal.value > 0) {
        const discount = Math.max(0, cartSubtotal.value - Number(selectedPackageFixedPrice.value || 0));
        return Math.min(100, Number(((discount / cartSubtotal.value) * 100).toFixed(4)));
    }

    return Number(selectedPackageDiscount.value || 0);
});

const cartDiscountAmount = computed(() => cartSubtotal.value * (cartDiscountPercent.value / 100));
const payableAmount = computed(() => Math.max(0, cartSubtotal.value - cartDiscountAmount.value));
const amountTendered = ref(0);

function closeSaleSuccessAlert() {
    saleSuccessDismissed.value = true;
    saleSuccessAlertKey.value += 1;
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

function normalizeLineQuantity(line) {
    const n = Math.round(Number(line.quantity) || 1);
    line.quantity = Math.max(1, n);
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

function lineBatchLabel(line) {
    if (line.batch_no) {
        return formatBatchLabel({ batch_no: line.batch_no, expiry_date: line.expiry_date });
    }

    return formatBatchLabel(line.batches?.find((b) => b.id === line.product_batch_id));
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
    line.unit_price =
        suggested ??
        unitSalePrice({ units: line.unit_options, sale_price: line.fallback_sale_price }, line.sell_unit);
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

        return t('catalog.pos_price_from_markup', {
            cost: formatMoney(unitCostInSellUnit(batch, line.sell_unit, line.unit_options)),
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

function addLine(item, options = {}) {
    const batches = batchesWithStock(item);
    const batch = batches[0];
    if (!batch) {
        alert(t('catalog.pos_no_sellable_batch'));
        return false;
    }
    const sellUnit = options.sellUnit || defaultSellUnit(item);
    const line = {
        product_batch_id: batch.id,
        batch_no: batch.batch_no,
        expiry_date: batch.expiry_date,
        batches,
        default_markup_percent: item.default_markup_percent,
        name: item.name,
        sell_unit: sellUnit,
        unit_options: item.units?.length ? item.units : [{ sell_unit: sellUnit, sale_price: item.sale_price }],
        fallback_sale_price: item.sale_price,
        quantity: Math.max(1, Math.round(Number(options.quantity ?? 1) || 1)),
        unit_price: 0,
    };
    refreshLinePricing(line);
    if (options.unitPriceOverride !== null && options.unitPriceOverride !== undefined && options.unitPriceOverride !== '') {
        line.unit_price = Number(options.unitPriceOverride);
        line.price_from_batch = false;
        line.uses_markup_pricing = false;
    }
    cart.value.push(line);
    return true;
}

function addPackage(template) {
    let added = 0;
    template.items.forEach((item) => {
        if (addLine(item.product, {
            quantity: item.quantity,
            sellUnit: item.sell_unit,
            unitPriceOverride: item.unit_price_override,
        })) {
            added += 1;
        }
    });

    if (added > 0) {
        activePackageName.value = template.name;
        selectedPackageDiscount.value = template.discount_percent || 0;
        selectedPackageFixedPrice.value = template.fixed_price ?? null;
    }
}

function submitSale() {
    cart.value.forEach(normalizeLineQuantity);
    submitting.value = true;
    router.post(
        '/sales/package',
        {
            lines: cart.value.map((l) => ({
                product_batch_id: l.product_batch_id,
                quantity: Math.max(1, Math.round(Number(l.quantity) || 1)),
                sell_unit: l.sell_unit,
                unit_price: l.unit_price,
            })),
            payments: [{ method: paymentMethod.value, amount: Number(amountTendered.value || payableAmount.value) }],
            discount_percent: cartDiscountPercent.value,
            tax: 0,
            coupon_code: couponCode.value || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                submitting.value = false;
            },
            onSuccess: () => {
                cart.value = [];
                couponCode.value = '';
                activePackageName.value = '';
                selectedPackageDiscount.value = 0;
                selectedPackageFixedPrice.value = null;
                amountTendered.value = 0;
                saleSuccessDismissed.value = false;
                saleSuccessAlertKey.value += 1;
            },
        },
    );
}
</script>

<style scoped>
.package-template-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

.package-template-card {
    border: 1px solid #dee2e6;
    border-radius: 0.65rem;
    background: #fff;
    padding: 0.75rem;
    min-height: 5.75rem;
}

.package-template-card:hover {
    border-color: var(--bs-primary);
}

.package-cart-line-card__fields {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
}

.package-sale-alert {
    position: relative;
    overflow: hidden;
}

.package-sale-alert__timer {
    position: absolute;
    left: 0;
    bottom: 0;
    height: 3px;
    width: 100%;
    background: currentColor;
    opacity: 0.35;
    animation: package-sale-alert-timer 8s linear forwards;
}

@keyframes package-sale-alert-timer {
    from {
        width: 100%;
    }

    to {
        width: 0%;
    }
}

@media (max-width: 575.98px) {
    .package-template-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .package-template-card {
        padding: 0.6rem;
        min-height: 5.25rem;
    }

    .package-cart-line-card__fields {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
