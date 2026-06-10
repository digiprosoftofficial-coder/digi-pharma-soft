<template>
    <TenantShellLayout page-title="New purchase">
        <Head title="New purchase" />
        <h1 class="h4 mb-3">Record purchase</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ t('purchases.supplier') }}</label>
                    <div v-if="selectedSupplier" class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge text-bg-primary">{{ selectedSupplier.name }}</span>
                        <button type="button" class="btn btn-sm btn-link p-0" @click="clearSupplier">{{ t('purchases.change_supplier') }}</button>
                    </div>
                    <div v-else-if="!showNewSupplierForm">
                        <input
                            v-model="supplierQuery"
                            type="search"
                            class="form-control"
                            :placeholder="t('purchases.supplier_search_placeholder')"
                            autocomplete="off"
                            @input="debouncedSupplierSearch"
                        />
                        <ul v-if="supplierResults.length" class="list-group list-group-flush mt-1 border rounded overflow-hidden">
                            <li
                                v-for="s in supplierResults"
                                :key="s.id"
                                class="list-group-item list-group-item-action py-2 small"
                                role="button"
                                @click="selectSupplier(s)"
                            >
                                {{ s.name }}
                                <span v-if="s.phone" class="text-muted">({{ s.phone }})</span>
                            </li>
                        </ul>
                        <button type="button" class="btn btn-sm btn-link p-0 mt-1" @click="toggleNewSupplierForm">
                            + {{ t('purchases.add_new_supplier') }}
                        </button>
                    </div>
                    <div v-else class="border rounded p-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold">{{ t('purchases.new_supplier') }}</span>
                            <button type="button" class="btn btn-sm btn-link p-0" @click="toggleNewSupplierForm">{{ t('common.cancel') }}</button>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <input v-model="newSupplierName" type="text" class="form-control form-control-sm" :placeholder="t('purchases.supplier_name')" required />
                            </div>
                            <div class="col-6">
                                <input v-model="newSupplierPhone" type="text" class="form-control form-control-sm" :placeholder="t('purchases.supplier_phone')" />
                            </div>
                            <div class="col-6">
                                <input v-model="newSupplierEmail" type="email" class="form-control form-control-sm" :placeholder="t('purchases.supplier_email')" />
                            </div>
                        </div>
                    </div>
                    <div v-if="form.errors.supplier_id" class="text-danger small">{{ form.errors.supplier_id }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('purchases.invoice') }}</label>
                    <input v-model="form.invoice_no" class="form-control" required />
                    <div v-if="form.errors.invoice_no" class="text-danger small">{{ form.errors.invoice_no }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('purchases.date') }}</label>
                    <input v-model="form.purchased_at" type="date" class="form-control" required />
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('purchases.notes') }}</label>
                <textarea v-model="form.notes" class="form-control" rows="2" :placeholder="t('purchases.notes_placeholder')" />
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
                <div v-if="Number(form.paid) > 0" class="col-md-4">
                    <label class="form-label">{{ t('purchases.payment_method') }}</label>
                    <select v-model="form.payment_method" class="form-select" required>
                        <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                    <div v-if="form.errors.payment_method" class="text-danger small">{{ form.errors.payment_method }}</div>
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
                        <label class="form-label small">{{ t('purchases.expiry') }}</label>
                        <input v-model="line.expiry_date" type="date" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">{{ t('purchases.manufactured_at') }}</label>
                        <input v-model="line.manufactured_at" type="date" class="form-control form-control-sm" />
                    </div>
                    <div v-if="props.storageLocations.length" class="col-md-3">
                        <label class="form-label small">{{ t('catalog.storage_location_shelf') }}</label>
                        <select v-model="line.storage_location_id" class="form-select form-select-sm">
                            <option :value="null">{{ t('catalog.storage_location_use_default') }}</option>
                            <option v-for="loc in props.storageLocations" :key="loc.id" :value="loc.id">
                                {{ locationLabel(loc) }}
                            </option>
                        </select>
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
                        <label class="form-label small">{{ t('purchases.unit_cost') }} ({{ currencyCode() }})</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">{{ currencySymbol() }}</span>
                            <input v-model.number="line.unit_cost" type="number" min="0" step="0.01" class="form-control" required />
                        </div>
                        <div v-if="priceComparisonLabel(line)" class="form-text" :class="priceComparisonClass(line)">
                            {{ priceComparisonLabel(line) }}
                        </div>
                        <div v-if="Number(line.quantity) > 0 && Number(line.unit_cost) > 0" class="form-text">
                            = {{ formatMoney(Number(line.quantity) * Number(line.unit_cost)) }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">{{ t('purchases.sale_price_mrp') }} ({{ currencyCode() }})</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">{{ currencySymbol() }}</span>
                            <input v-model.number="line.sale_price" type="number" min="0" step="0.01" class="form-control" />
                        </div>
                    </div>
                </div>
                <div v-if="needsPackSize(line)" class="row g-2 mt-1 pt-2 border-top">
                    <div class="col-md-4">
                        <label class="form-label small mb-0">
                            {{ packSizeLabel(line) }}
                        </label>
                        <input
                            v-if="usesStripsPerBox(line)"
                            v-model.number="line.pack_strips_per_box"
                            type="number"
                            min="0.0001"
                            step="any"
                            class="form-control form-control-sm mt-1"
                            required
                            @input="syncLineConversionFromPackInput(line)"
                        />
                        <input
                            v-else-if="usesBoxesPerCarton(line)"
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
                            <p v-if="usesPackSizeFriendlyInput(line) && packSizeBreakdown(line)" class="mb-1">
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
    catalogStripsPerBox,
    defaultSellUnit,
    hasBoxAndCartonUnits,
    unitLabel,
    unitPurchasePrice,
    unitSalePrice,
} from '@/composables/useProductUnits';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    paymentMethods: { type: Array, default: () => [] },
    storageLocations: { type: Array, default: () => [] },
});

function locationLabel(loc) {
    if (!loc) {
        return '';
    }

    return loc.code ? `${loc.name} (${loc.code})` : loc.name;
}

const { t } = useLocale();
const { formatMoney, currencyCode, currencySymbol } = useMoney();

const purchaseBatchTip = computed(() => t('catalog.purchase_batch_pack_tip'));

const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
let searchTimer;

const supplierQuery = ref('');
const supplierResults = ref([]);
const selectedSupplier = ref(null);
const showNewSupplierForm = ref(false);
const newSupplierName = ref('');
const newSupplierPhone = ref('');
const newSupplierEmail = ref('');
let supplierSearchTimer;

const form = useForm({
    supplier_id: '',
    invoice_no: '',
    purchased_at: new Date().toISOString().slice(0, 10),
    notes: '',
    tax: 0,
    discount: 0,
    paid: 0,
    payment_method: props.paymentMethods[0]?.value ?? 'cash',
    lines: [],
});

function debouncedSupplierSearch() {
    clearTimeout(supplierSearchTimer);
    supplierSearchTimer = setTimeout(runSupplierSearch, 250);
}

async function runSupplierSearch() {
    if (supplierQuery.value.length < 1) {
        supplierResults.value = [];
        return;
    }
    const { data } = await window.axios.get('/purchases/supplier-search', { params: { q: supplierQuery.value } });
    supplierResults.value = data.data ?? [];
}

function selectSupplier(supplier) {
    selectedSupplier.value = supplier;
    form.supplier_id = supplier.id;
    supplierQuery.value = '';
    supplierResults.value = [];
    showNewSupplierForm.value = false;
}

function clearSupplier() {
    selectedSupplier.value = null;
    form.supplier_id = '';
}

function toggleNewSupplierForm() {
    showNewSupplierForm.value = !showNewSupplierForm.value;
    if (showNewSupplierForm.value) {
        clearSupplier();
    }
}

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

function usesPackSizeFriendlyInput(line) {
    return usesStripsPerBox(line) || usesBoxesPerCarton(line);
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

function defaultStripsPerBox(line) {
    const product = { units: line.unit_options, strips_per_box: line.catalog_strips_per_box, base_unit: line.base_unit };
    const value = catalogStripsPerBox(product);
    return value && value > 0 ? value : defaultConversion(line);
}

function defaultBoxesPerCarton(line) {
    const product = { units: line.unit_options, boxes_per_carton: line.catalog_boxes_per_carton };
    const value = catalogBoxesPerCarton(product);
    return value && value > 0 ? value : defaultConversion(line) / Math.max(0.0001, boxConversionFactor(line.unit_options));
}

function syncLineConversionFromPackInput(line) {
    if (usesStripsPerBox(line)) {
        const strips = Number(line.pack_strips_per_box);
        if (!Number.isNaN(strips) && strips > 0) {
            line.conversion_factor = formatConversionFactor(strips);
        }
        return;
    }
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
    if (usesStripsPerBox(line)) {
        line.pack_strips_per_box = defaultStripsPerBox(line);
        syncLineConversionFromPackInput(line);
    } else if (usesBoxesPerCarton(line)) {
        line.pack_boxes_per_carton = defaultBoxesPerCarton(line);
        syncLineConversionFromPackInput(line);
    }
}

function packSizeBreakdown(line) {
    if (!usesPackSizeFriendlyInput(line)) {
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
    if (usesStripsPerBox(line)) {
        const def = defaultStripsPerBox(line);
        const current = Number(line.pack_strips_per_box);
        return !Number.isNaN(current) && Math.abs(current - def) > 0.0001;
    }
    if (usesBoxesPerCarton(line)) {
        const def = defaultBoxesPerCarton(line);
        const current = Number(line.pack_boxes_per_carton);
        return !Number.isNaN(current) && Math.abs(current - def) > 0.0001;
    }
    return Number(line.conversion_factor) !== defaultConversion(line);
}

function packSizeDefaultHint(line) {
    if (usesStripsPerBox(line)) {
        return t('catalog.purchase_catalog_default_strips', { qty: formatQty(defaultStripsPerBox(line)) });
    }
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
    if (usesPackSizeFriendlyInput(line)) {
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
        manufactured_at: '',
        sell_unit: sellUnit,
        conversion_factor: 1,
        quantity: 1,
        unit_cost: unitPurchasePrice(product, sellUnit),
        sale_price: unitSalePrice(product, sellUnit),
        last_purchase_unit_cost: product.last_purchase?.unit_cost ?? null,
        last_purchase_sell_unit: product.last_purchase?.sell_unit ?? null,
        last_purchase_date: product.last_purchase?.purchased_at ?? null,
        unit_options: unitOptions,
        existing_batches: existingBatches,
        catalog_strips_per_box: product.strips_per_box ?? null,
        catalog_boxes_per_carton: product.boxes_per_carton ?? null,
        pack_strips_per_box: null,
        pack_boxes_per_carton: null,
        storage_location_id: product.storage_location_id ?? product.storage_location?.id ?? null,
    };
    initLinePackFields(line);
    if (!usesPackSizeFriendlyInput(line)) {
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
        line.storage_location_id = batch.storage_location_id ?? line.storage_location_id;
        if (batch.pack_sell_unit && batch.pack_conversion_factor) {
            line.sell_unit = batch.pack_sell_unit;
            line.conversion_factor = Number(batch.pack_conversion_factor);
            if (usesStripsPerBox(line)) {
                line.pack_strips_per_box = Number(batch.pack_conversion_factor);
            } else if (usesBoxesPerCarton(line)) {
                const boxFactor = boxConversionFactor(line.unit_options);
                line.pack_boxes_per_carton =
                    boxFactor > 0 ? formatConversionFactor(Number(batch.pack_conversion_factor) / boxFactor) : null;
            }
        }
    }
}

async function onUnitChange(line) {
    const product = {
        units: line.unit_options,
        purchase_price: line.unit_options[0]?.purchase_price,
        sale_price: line.unit_options[0]?.sale_price,
    };
    line.unit_cost = unitPurchasePrice(product, line.sell_unit);
    line.sale_price = unitSalePrice(product, line.sell_unit);
    line.pack_strips_per_box = null;
    line.pack_boxes_per_carton = null;
    initLinePackFields(line);
    if (!usesPackSizeFriendlyInput(line)) {
        line.conversion_factor = defaultConversion(line);
    }
    await refreshLastPurchase(line);
}

async function refreshLastPurchase(line) {
    try {
        const { data } = await window.axios.get(`/catalog/products/${line.product_id}/last-purchase`, {
            params: { sell_unit: line.sell_unit },
        });
        line.last_purchase_unit_cost = data.data?.unit_cost ?? null;
        line.last_purchase_sell_unit = data.data?.sell_unit ?? line.sell_unit;
        line.last_purchase_date = data.data?.purchased_at ?? null;
    } catch {
        line.last_purchase_unit_cost = null;
        line.last_purchase_sell_unit = null;
        line.last_purchase_date = null;
    }
}

function priceComparisonLabel(line) {
    if (line.last_purchase_unit_cost == null || line.last_purchase_unit_cost === '') {
        return '';
    }
    return t('purchases.price_comparison', {
        last: formatMoney(line.last_purchase_unit_cost),
        unit: unitLabel(line.last_purchase_sell_unit || line.sell_unit),
        date: line.last_purchase_date || '—',
    });
}

function priceComparisonClass(line) {
    const last = Number(line.last_purchase_unit_cost);
    const current = Number(line.unit_cost);
    if (Number.isNaN(last) || Number.isNaN(current)) {
        return 'text-muted';
    }
    if (current > last + 0.0001) {
        return 'text-danger';
    }
    if (current < last - 0.0001) {
        return 'text-success';
    }
    return 'text-muted';
}

function removeLine(index) {
    form.lines.splice(index, 1);
}

function submit() {
    form.transform((data) => {
        const payload = {
            ...data,
            notes: data.notes || null,
            lines: data.lines.map((line) => {
                if (usesPackSizeFriendlyInput(line)) {
                    syncLineConversionFromPackInput(line);
                }
                const row = {
                    product_id: line.product_id,
                    batch_no: line.batch_no,
                    expiry_date: line.expiry_date || null,
                    manufactured_at: line.manufactured_at || null,
                    sell_unit: line.sell_unit,
                    quantity: line.quantity,
                    unit_cost: line.unit_cost,
                    sale_price: line.sale_price > 0 ? line.sale_price : null,
                    storage_location_id: line.storage_location_id || null,
                };
                if (needsPackSize(line)) {
                    row.conversion_factor = line.conversion_factor;
                }
                return row;
            }),
        };

        if (showNewSupplierForm.value && newSupplierName.value.trim()) {
            payload.new_supplier = {
                name: newSupplierName.value.trim(),
                phone: newSupplierPhone.value.trim() || null,
                email: newSupplierEmail.value.trim() || null,
            };
            delete payload.supplier_id;
        }

        return payload;
    }).post('/purchases');
}
</script>
