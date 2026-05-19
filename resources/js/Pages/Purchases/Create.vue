<template>
    <TenantShellLayout page-title="New purchase">
        <Head title="New purchase" />
        <h1 class="h4 mb-3">Record purchase</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <select v-model="form.supplier_id" class="form-select" required>
                        <option value="" disabled>Select</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                    <div v-if="form.errors.supplier_id" class="text-danger small">{{ form.errors.supplier_id }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Invoice no</label>
                    <input v-model="form.invoice_no" class="form-control" required />
                    <div v-if="form.errors.invoice_no" class="text-danger small">{{ form.errors.invoice_no }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchased at</label>
                    <input v-model="form.purchased_at" type="date" class="form-control" required />
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Tax ({{ currencyCode() }})</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model.number="form.tax" type="number" min="0" step="0.01" class="form-control" />
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount ({{ currencyCode() }})</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model.number="form.discount" type="number" min="0" step="0.01" class="form-control" />
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Paid ({{ currencyCode() }})</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model.number="form.paid" type="number" min="0" step="0.01" class="form-control" />
                    </div>
                </div>
            </div>

            <div class="card border bg-light mb-3">
                <div class="card-body py-3">
                    <label class="form-label fw-semibold mb-1">Add product</label>
                    <p class="small text-muted mb-2">Search by name, SKU, or barcode — no need to type product ID.</p>
                    <input
                        v-model="searchQuery"
                        type="search"
                        class="form-control"
                        placeholder="Start typing…"
                        autocomplete="off"
                        @input="debouncedSearch"
                    />
                    <ul v-if="searchResults.length" class="list-group list-group-flush mt-2 border rounded overflow-hidden">
                        <li
                            v-for="item in searchResults"
                            :key="item.id"
                            class="list-group-item list-group-item-action py-2"
                            role="button"
                            @click="addProductLine(item)"
                        >
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="fw-medium">{{ item.name }}</span>
                                    <span class="text-muted small ms-2">{{ item.sku }}</span>
                                </div>
                                <span class="badge text-bg-secondary text-capitalize">{{ item.base_unit || item.unit }}</span>
                            </div>
                            <div v-if="item.batches?.length" class="small text-muted mt-1">
                                {{ item.batches.length }} batch(es) in stock
                            </div>
                        </li>
                    </ul>
                    <p v-else-if="searchQuery.length >= 2 && !searching" class="small text-muted mb-0 mt-2">No products found.</p>
                </div>
            </div>

            <div class="alert alert-info small py-2 mb-3" role="note">
                {{ purchaseBatchTip }}
            </div>

            <h2 class="h6">Purchase lines</h2>
            <p v-if="!form.lines.length" class="text-muted small">Search and click a product above to add lines.</p>
            <div v-if="form.errors.lines" class="text-danger small mb-2">{{ form.errors.lines }}</div>

            <div v-for="(line, i) in form.lines" :key="line._key" class="border rounded p-3 mb-2 bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="fw-semibold">{{ line.product_name }}</span>
                        <span class="text-muted small ms-2">{{ line.product_sku }}</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(i)">Remove</button>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Batch</label>
                        <select v-model="line.batch_pick" class="form-select form-select-sm" @change="applyBatchPick(line)">
                            <option value="__new__">+ New batch (from invoice)</option>
                            <option v-for="b in line.existing_batches" :key="b.id" :value="b.batch_no">
                                {{ b.batch_no }} · on hand {{ formatQty(b.quantity_on_hand) }}
                            </option>
                        </select>
                    </div>
                    <div v-if="line.batch_pick === '__new__'" class="col-md-3">
                        <label class="form-label small">New batch no</label>
                        <input v-model="line.batch_no" type="text" class="form-control form-control-sm" required placeholder="e.g. LOT-2026-01" />
                    </div>
                    <div v-else class="col-md-3">
                        <label class="form-label small">Batch no</label>
                        <input :value="line.batch_no" type="text" class="form-control form-control-sm" disabled />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Expiry</label>
                        <input v-model="line.expiry_date" type="date" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Unit</label>
                        <select v-model="line.sell_unit" class="form-select form-select-sm" @change="onUnitChange(line)">
                            <option v-for="u in line.unit_options" :key="u.sell_unit" :value="u.sell_unit">
                                {{ unitLabel(u.sell_unit) }}
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Qty</label>
                        <input v-model.number="line.quantity" type="number" min="0.0001" step="0.0001" class="form-control form-control-sm" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Unit cost ({{ currencyCode() }})</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">{{ currencySymbol() }}</span>
                            <input v-model.number="line.unit_cost" type="number" min="0" step="0.01" class="form-control" required />
                        </div>
                        <div v-if="Number(line.quantity) > 0 && Number(line.unit_cost) > 0" class="form-text">
                            = {{ formatMoney(Number(line.quantity) * Number(line.unit_cost)) }}
                        </div>
                    </div>
                </div>
                <div v-if="needsPackSize(line)" class="row g-2 mt-1 pt-2 border-top">
                    <div class="col-md-4">
                        <label class="form-label small mb-0">
                            {{ packSizeLabel(line) }}
                        </label>
                        <input
                            v-if="usesBoxesPerCarton(line)"
                            v-model.number="line.pack_boxes_per_carton"
                            type="number"
                            min="0.0001"
                            step="any"
                            class="form-control form-control-sm mt-1"
                            required
                            @input="syncLineConversionFromPackInput(line)"
                        />
                        <input
                            v-else
                            v-model.number="line.conversion_factor"
                            type="number"
                            min="0.0001"
                            step="any"
                            class="form-control form-control-sm mt-1"
                            required
                        />
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div v-if="lineQuantityBase(line) > 0" class="small text-muted mb-1">
                            <p v-if="usesBoxesPerCarton(line) && packSizeBreakdown(line)" class="mb-1">
                                {{ packSizeBreakdown(line) }}
                            </p>
                            <p class="mb-0">
                                Adds <strong>{{ formatQty(lineQuantityBase(line)) }}</strong>
                                {{ unitLabel(line.base_unit) }}(s) to stock
                                <span v-if="packSizeDiffersFromDefault(line)" class="text-warning">
                                    ({{ packSizeDefaultHint(line) }})
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary" :disabled="form.processing || !form.lines.length">Save purchase</button>
                <Link href="/purchases" class="btn btn-link">Cancel</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import {
    boxConversionFactor,
    catalogBoxesPerCarton,
    defaultSellUnit,
    hasBoxAndCartonUnits,
    unitLabel,
    unitPurchasePrice,
} from '@/composables/useProductUnits';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({ suppliers: { type: Array, required: true } });

const { t } = useLocale();
const { formatMoney, currencyCode, currencySymbol } = useMoney();

const purchaseBatchTip = computed(() => t('catalog.purchase_batch_pack_tip'));

const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
let searchTimer;

const form = useForm({
    supplier_id: '',
    invoice_no: '',
    purchased_at: new Date().toISOString().slice(0, 10),
    tax: 0,
    discount: 0,
    paid: 0,
    lines: [],
});

function formatQty(value) {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) {
        return '0';
    }
    return n % 1 === 0 ? String(n) : n.toFixed(2);
}

function debouncedSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(runSearch, 250);
}

async function runSearch() {
    if (searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }
    searching.value = true;
    try {
        const { data } = await window.axios.get('/catalog/product-search', { params: { q: searchQuery.value } });
        searchResults.value = data.data ?? [];
    } finally {
        searching.value = false;
    }
}

function buildUnitOptions(product) {
    if (product.units?.length) {
        return product.units;
    }
    const u = product.base_unit ?? product.unit ?? 'strip';
    return [{ sell_unit: u, conversion_factor: 1, purchase_price: product.purchase_price, sale_price: product.sale_price, is_default: true }];
}

function productBaseUnit(product) {
    return product.base_unit ?? product.unit ?? 'strip';
}

function formatConversionFactor(value) {
    const n = Number(value);
    if (Number.isNaN(n) || n <= 0) {
        return 0.0001;
    }
    return Math.round(n * 10000) / 10000;
}

function usesBoxesPerCarton(line) {
    return line.sell_unit === 'carton' && hasBoxAndCartonUnits(line.unit_options);
}

function usesStripsPerBox(line) {
    return line.sell_unit === 'box' && line.base_unit === 'strip';
}

function packSizeLabel(line) {
    if (usesBoxesPerCarton(line)) {
        return t('catalog.purchase_boxes_per_carton');
    }
    if (usesStripsPerBox(line)) {
        return t('catalog.purchase_strips_per_box');
    }
    return `${unitLabel(line.base_unit)} per 1 ${unitLabel(line.sell_unit)} (this receipt)`;
}

function defaultBoxesPerCarton(line) {
    const product = { units: line.unit_options, boxes_per_carton: line.catalog_boxes_per_carton };
    const value = catalogBoxesPerCarton(product);
    return value && value > 0 ? value : defaultConversion(line) / Math.max(0.0001, boxConversionFactor(line.unit_options));
}

function syncLineConversionFromPackInput(line) {
    if (!usesBoxesPerCarton(line)) {
        return;
    }
    const boxes = Number(line.pack_boxes_per_carton);
    const boxFactor = boxConversionFactor(line.unit_options);
    if (Number.isNaN(boxes) || boxes <= 0 || boxFactor <= 0) {
        return;
    }
    line.conversion_factor = formatConversionFactor(boxes * boxFactor);
}

function initLinePackFields(line) {
    if (usesBoxesPerCarton(line)) {
        line.pack_boxes_per_carton = defaultBoxesPerCarton(line);
        syncLineConversionFromPackInput(line);
    }
}

function packSizeBreakdown(line) {
    if (!usesBoxesPerCarton(line)) {
        return '';
    }
    const factor = Number(line.conversion_factor);
    if (Number.isNaN(factor) || factor <= 0) {
        return '';
    }
    return t('catalog.purchase_pack_equals', {
        qty: formatQty(factor),
        unit: unitLabel(line.base_unit),
        sell_unit: unitLabel(line.sell_unit),
    });
}

function packSizeDiffersFromDefault(line) {
    if (usesBoxesPerCarton(line)) {
        const def = defaultBoxesPerCarton(line);
        const current = Number(line.pack_boxes_per_carton);
        return !Number.isNaN(current) && Math.abs(current - def) > 0.0001;
    }
    return Number(line.conversion_factor) !== defaultConversion(line);
}

function packSizeDefaultHint(line) {
    if (usesBoxesPerCarton(line)) {
        return t('catalog.purchase_catalog_default_boxes', { qty: formatQty(defaultBoxesPerCarton(line)) });
    }
    return `catalog default: ${defaultConversion(line)}`;
}

function defaultConversion(line) {
    const option = line.unit_options.find((u) => u.sell_unit === line.sell_unit);
    return Number(option?.conversion_factor ?? 1);
}

function needsPackSize(line) {
    return line.sell_unit !== line.base_unit;
}

function lineQuantityBase(line) {
    const qty = Number(line.quantity);
    if (usesBoxesPerCarton(line)) {
        syncLineConversionFromPackInput(line);
    }
    const factor = needsPackSize(line) ? Number(line.conversion_factor) : 1;
    if (Number.isNaN(qty) || Number.isNaN(factor)) {
        return 0;
    }
    return qty * factor;
}

function addProductLine(product) {
    const sellUnit = defaultSellUnit(product);
    const baseUnit = productBaseUnit(product);
    const existingBatches = product.batches ?? [];
    const unitOptions = buildUnitOptions(product);
    const line = {
        _key: `${product.id}-${Date.now()}`,
        product_id: product.id,
        product_name: product.name,
        product_sku: product.sku,
        base_unit: baseUnit,
        batch_pick: '__new__',
        batch_no: '',
        expiry_date: '',
        sell_unit: sellUnit,
        conversion_factor: 1,
        quantity: 1,
        unit_cost: unitPurchasePrice(product, sellUnit),
        unit_options: unitOptions,
        existing_batches: existingBatches,
        catalog_boxes_per_carton: product.boxes_per_carton ?? null,
        pack_boxes_per_carton: null,
    };
    initLinePackFields(line);
    if (!usesBoxesPerCarton(line)) {
        line.conversion_factor = defaultConversion(line);
    }
    form.lines.push(line);
    searchQuery.value = '';
    searchResults.value = [];
}

function applyBatchPick(line) {
    if (line.batch_pick === '__new__') {
        line.batch_no = '';
        line.expiry_date = '';
        return;
    }
    const batch = line.existing_batches.find((b) => b.batch_no === line.batch_pick);
    if (batch) {
        line.batch_no = batch.batch_no;
        line.expiry_date = batch.expiry_date ?? '';
        if (batch.pack_sell_unit && batch.pack_conversion_factor) {
            line.sell_unit = batch.pack_sell_unit;
            line.conversion_factor = Number(batch.pack_conversion_factor);
            if (usesBoxesPerCarton(line)) {
                const boxFactor = boxConversionFactor(line.unit_options);
                line.pack_boxes_per_carton =
                    boxFactor > 0 ? formatConversionFactor(Number(batch.pack_conversion_factor) / boxFactor) : null;
            }
        }
    }
}

function onUnitChange(line) {
    const product = {
        units: line.unit_options,
        purchase_price: line.unit_options[0]?.purchase_price,
    };
    line.unit_cost = unitPurchasePrice(product, line.sell_unit);
    line.pack_boxes_per_carton = null;
    initLinePackFields(line);
    if (!usesBoxesPerCarton(line)) {
        line.conversion_factor = defaultConversion(line);
    }
}

function removeLine(index) {
    form.lines.splice(index, 1);
}

function submit() {
    form.transform((data) => ({
        ...data,
        lines: data.lines.map((line) => {
            if (usesBoxesPerCarton(line)) {
                syncLineConversionFromPackInput(line);
            }
            const payload = {
                product_id: line.product_id,
                batch_no: line.batch_no,
                expiry_date: line.expiry_date || null,
                sell_unit: line.sell_unit,
                quantity: line.quantity,
                unit_cost: line.unit_cost,
            };
            if (needsPackSize(line)) {
                payload.conversion_factor = line.conversion_factor;
            }
            return payload;
        }),
    })).post('/purchases');
}
</script>
