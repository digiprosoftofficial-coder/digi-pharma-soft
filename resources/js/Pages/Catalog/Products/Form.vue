<template>
    <TenantShellLayout :page-title="existing ? 'Edit product' : 'New product'">
        <Head :title="existing ? 'Edit product' : 'New product'" />
        <h1 class="h4 mb-4 d-lg-none">{{ existing ? 'Edit product' : 'New product' }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" type="text" class="form-control" required />
                    <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">SKU</label>
                    <input v-model="form.sku" type="text" class="form-control" required />
                    <div v-if="form.errors.sku" class="text-danger small">{{ form.errors.sku }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Barcode</label>
                    <input v-model="form.barcode" type="text" class="form-control" />
                    <div v-if="existing" class="mt-2">
                        <span class="small text-muted d-block mb-1">Label preview</span>
                        <img :src="`/barcodes/${existing.id}`" alt="Barcode" class="border rounded bg-white p-1" style="max-height: 64px" />
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product type</label>
                    <select v-model="form.product_type" class="form-select" required>
                        <option v-for="t in catalogOptions.productTypes" :key="t" :value="t">
                            {{ typeLabel(t) }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Base stock unit</label>
                    <select v-model="form.base_unit" class="form-select" required @change="onBaseUnitChange">
                        <option v-for="u in catalogOptions.sellUnits" :key="u" :value="u">{{ unitLabel(u) }}</option>
                    </select>
                    <p class="form-text small mb-0">Inventory is tracked in this unit.</p>
                </div>
                <div v-if="showPiecesPerStrip" class="col-md-4">
                    <label class="form-label">Pieces per strip</label>
                    <input
                        v-model="form.pieces_per_strip"
                        type="number"
                        min="1"
                        step="1"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.pieces_per_strip }"
                        placeholder="e.g. 10"
                        @input="syncPiecesPerStripToUnits"
                    />
                    <div v-if="form.errors.pieces_per_strip" class="text-danger small">{{ form.errors.pieces_per_strip }}</div>
                    <p v-else class="form-text small mb-0">Tablets/capsules in one strip — needed for piece sales &amp; stock count.</p>
                </div>
                <div v-if="showBoxesPerCarton" class="col-md-4">
                    <label class="form-label">Boxes per carton</label>
                    <input
                        v-model="form.boxes_per_carton"
                        type="number"
                        min="1"
                        step="1"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.boxes_per_carton }"
                        placeholder="e.g. 12"
                        @input="syncBoxesPerCartonToUnits"
                    />
                    <div v-if="form.errors.boxes_per_carton" class="text-danger small">{{ form.errors.boxes_per_carton }}</div>
                    <p v-else class="form-text small mb-0">
                        Boxes in one carton — syncs carton conversion (× box size).
                        <span v-if="cartonConversionPreview" class="d-block">
                            = {{ formatQty(cartonConversionPreview) }} {{ unitLabel(form.base_unit) }} per carton
                        </span>
                    </p>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Min stock alert</label>
                    <input v-model="form.min_stock" type="number" min="0" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select v-model="form.category_id" class="form-select">
                        <option :value="null">— None —</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Manufacturer</label>
                    <select v-model="form.manufacturer_id" class="form-select">
                        <option :value="null">— None —</option>
                        <option v-for="m in manufacturers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
            </div>

            <div v-if="!existing" class="mt-4 card border-primary border-2">
                <div class="card-header bg-primary-subtle py-3">
                    <h2 class="h5 mb-1">Opening stock</h2>
                    <p class="small text-muted mb-0">
                        Set how much you have on hand when adding this product (in <strong>{{ unitLabel(form.base_unit) }}</strong>).
                        Leave quantity empty if stock will come from a purchase later.
                    </p>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input
                                v-model.number="form.opening_quantity"
                                type="number"
                                min="0"
                                step="0.0001"
                                class="form-control form-control-lg"
                                placeholder="e.g. 100"
                            />
                            <div v-if="form.errors.opening_quantity" class="text-danger small">{{ form.errors.opening_quantity }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Batch no</label>
                            <input
                                v-model="form.opening_batch_no"
                                type="text"
                                class="form-control"
                                :placeholder="form.sku ? `OPEN-${form.sku}` : 'Auto if empty'"
                            />
                            <div v-if="form.errors.opening_batch_no" class="text-danger small">{{ form.errors.opening_batch_no }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry date</label>
                            <input v-model="form.opening_expiry_date" type="date" class="form-control" />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="existing" class="mt-4 card border-warning border-2">
                <div class="card-header bg-warning-subtle py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h2 class="h5 mb-1">Stock</h2>
                        <p class="small text-muted mb-0">Adjust on-hand quantity in <strong>{{ unitLabel(form.base_unit) }}</strong>.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge text-bg-dark fs-6 px-3 py-2">
                            Total: {{ formatQty(totalStock) }} {{ unitLabel(form.base_unit) }}
                        </span>
                        <span v-if="totalStockPieces !== null" class="badge text-bg-secondary fs-6 px-3 py-2">
                            {{ formatQty(totalStockPieces) }} pieces
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="batches.length" class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch</th>
                                    <th>Expiry</th>
                                    <th class="text-end">On hand</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="b in batches" :key="b.id">
                                    <td>{{ b.batch_no }}</td>
                                    <td>{{ b.expiry_date || '—' }}</td>
                                    <td class="text-end">{{ formatQty(b.quantity_on_hand) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-muted small mb-3">No batches yet. A positive adjustment will create one.</p>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Adjust by (+ / −)</label>
                            <input
                                v-model.number="form.stock_adjustment"
                                type="number"
                                step="0.0001"
                                class="form-control form-control-lg"
                                placeholder="e.g. 50 or -10"
                            />
                            <p class="form-text small mb-0">Positive adds stock; negative removes.</p>
                            <div v-if="form.errors.stock_adjustment" class="text-danger small">{{ form.errors.stock_adjustment }}</div>
                        </div>
                        <div v-if="batches.length > 1" class="col-md-4">
                            <label class="form-label">Apply to batch</label>
                            <select v-model="form.stock_adjust_batch_id" class="form-select">
                                <option :value="null">— Select batch —</option>
                                <option v-for="b in batches" :key="b.id" :value="b.id">
                                    {{ b.batch_no }} ({{ formatQty(b.quantity_on_hand) }})
                                </option>
                            </select>
                            <div v-if="form.errors.stock_adjust_batch_id" class="text-danger small">{{ form.errors.stock_adjust_batch_id }}</div>
                        </div>
                        <div v-else-if="!batches.length" class="col-md-4">
                            <label class="form-label">New batch no</label>
                            <input
                                v-model="form.stock_adjust_batch_no"
                                type="text"
                                class="form-control"
                                :placeholder="`ADJ-${existing.sku}`"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">Sell units &amp; prices</h2>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="addUnitRow">Add unit</button>
                </div>
                <div v-if="form.errors.units" class="text-danger small mb-2">{{ form.errors.units }}</div>
                <p v-if="hasStripUnitRow" class="small text-muted mb-2">
                    Strip purchase/sale prices auto-fill piece, box, and carton (using conversion factors).
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Unit</th>
                                <th style="width: 8rem">{{ conversionColumnLabel }}</th>
                                <th style="width: 9rem">Purchase</th>
                                <th style="width: 9rem">Sale</th>
                                <th>Default</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, idx) in form.units" :key="idx">
                                <td>
                                    <select v-model="row.sell_unit" class="form-select form-select-sm">
                                        <option v-for="u in catalogOptions.sellUnits" :key="u" :value="u">{{ unitLabel(u) }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input
                                        v-model.number="row.conversion_factor"
                                        type="number"
                                        min="0.0001"
                                        step="any"
                                        class="form-control form-control-sm"
                                        :disabled="row.sell_unit === form.base_unit"
                                        @input="onUnitConversionInput(row)"
                                        @blur="onConversionFactorBlur(row)"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="row.purchase_price"
                                        type="number"
                                        min="0"
                                        step="0.0001"
                                        class="form-control form-control-sm"
                                        required
                                        @input="onStripPriceInput(row)"
                                    />
                                </td>
                                <td>
                                    <input
                                        v-model="row.sale_price"
                                        type="number"
                                        min="0"
                                        step="0.0001"
                                        class="form-control form-control-sm"
                                        required
                                        @input="onStripPriceInput(row)"
                                    />
                                </td>
                                <td class="text-center">
                                    <input
                                        :id="`default-${idx}`"
                                        type="radio"
                                        name="default_unit"
                                        :checked="row.is_default"
                                        @change="setDefault(idx)"
                                    />
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        :disabled="form.units.length <= 1"
                                        @click="removeUnitRow(idx)"
                                    >
                                        ×
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <div class="form-check">
                    <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="active">Active</label>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
                <Link href="/products" class="btn btn-outline-secondary">Cancel</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    product: { type: Object, default: null },
    catalogOptions: {
        type: Object,
        default: () => ({ productTypes: ['other'], sellUnits: ['piece', 'strip', 'box', 'carton'] }),
    },
    categories: { type: Array, default: () => [] },
    manufacturers: { type: Array, default: () => [] },
});

/** Unwrap JsonResource { data: ... } if present */
function productData() {
    if (!props.product) {
        return null;
    }
    return props.product.data ?? props.product;
}

function typeLabel(t) {
    return t.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function unitLabel(u) {
    return u.charAt(0).toUpperCase() + u.slice(1);
}

function formatQty(n) {
    const v = Number(n);
    if (Number.isNaN(v)) {
        return '0';
    }
    return v % 1 === 0 ? String(v) : v.toFixed(2);
}

function buildDefaultUnits() {
    return [
        { sell_unit: 'strip', conversion_factor: 1, purchase_price: '0', sale_price: '0', is_default: true },
        { sell_unit: 'piece', conversion_factor: 0.1, purchase_price: '0', sale_price: '0', is_default: false },
        { sell_unit: 'box', conversion_factor: 10, purchase_price: '0', sale_price: '0', is_default: false },
    ];
}

function initialUnits() {
    const product = productData();
    if (product?.units?.length) {
        return product.units.map((u) => ({
            sell_unit: u.sell_unit,
            conversion_factor: formatConversionFactor(u.conversion_factor),
            purchase_price: String(u.purchase_price),
            sale_price: String(u.sale_price),
            is_default: Boolean(u.is_default),
        }));
    }
    return buildDefaultUnits();
}

function initialBoxesPerCarton(units) {
    const product = productData();
    if (product?.boxes_per_carton != null && product.boxes_per_carton !== '') {
        return Number(product.boxes_per_carton);
    }
    const carton = units.find((u) => u.sell_unit === 'carton');
    const box = units.find((u) => u.sell_unit === 'box');
    if (carton && box && Number(box.conversion_factor) > 0) {
        return Math.round((Number(carton.conversion_factor) / Number(box.conversion_factor)) * 10000) / 10000;
    }
    return '';
}

const existing = productData();

const batches = computed(() => {
    const raw = existing?.batches;
    if (!raw) {
        return [];
    }
    if (Array.isArray(raw)) {
        return raw;
    }
    return raw.data ?? [];
});

const totalStock = computed(() =>
    batches.value.reduce((sum, b) => sum + Number(b.quantity_on_hand ?? 0), 0),
);

const showPiecesPerStrip = computed(() => {
    const units = form.units.map((r) => r.sell_unit);
    return units.includes('piece') || units.includes('strip') || form.base_unit === 'piece' || form.base_unit === 'strip';
});

const conversionColumnLabel = computed(() => {
    const base = unitLabel(form.base_unit);
    return `${base} per 1 unit`;
});

const showBoxesPerCarton = computed(() => {
    const units = form.units.map((r) => r.sell_unit);
    return units.includes('carton') && units.includes('box');
});

const cartonConversionPreview = computed(() => {
    const bpc = Number(form.boxes_per_carton);
    const boxRow = form.units.find((r) => r.sell_unit === 'box');
    const boxFactor = boxRow ? unitRowFactor(boxRow) : 0;
    if (!bpc || bpc <= 0 || boxFactor <= 0) {
        return null;
    }
    return formatConversionFactor(bpc * boxFactor);
});

const hasStripUnitRow = computed(() => form.units.some((r) => r.sell_unit === 'strip'));

const totalStockPieces = computed(() => {
    const pps = Number(form.pieces_per_strip);
    if (!pps || pps <= 0) {
        return null;
    }
    if (form.base_unit === 'strip') {
        return totalStock.value * pps;
    }
    if (form.base_unit === 'piece') {
        return totalStock.value;
    }
    return null;
});

const form = useForm({
    name: existing?.name ?? '',
    sku: existing?.sku ?? '',
    barcode: existing?.barcode ?? '',
    category_id: existing?.category?.id ?? null,
    manufacturer_id: existing?.manufacturer?.id ?? null,
    product_type: existing?.product_type ?? 'tablet',
    base_unit: existing?.base_unit ?? 'strip',
    pieces_per_strip:
        existing?.pieces_per_strip != null && existing.pieces_per_strip !== ''
            ? Number(existing.pieces_per_strip)
            : '',
    boxes_per_carton: initialBoxesPerCarton(initialUnits()),
    units: initialUnits(),
    min_stock: existing?.min_stock ?? 0,
    is_active: existing?.is_active ?? true,
    opening_batch_no: '',
    opening_expiry_date: '',
    opening_quantity: null,
    stock_adjustment: null,
    stock_adjust_batch_id: null,
    stock_adjust_batch_no: '',
});

function onBaseUnitChange() {
    form.units.forEach((row) => {
        if (row.sell_unit === form.base_unit) {
            row.conversion_factor = 1;
        }
    });
    syncPiecesPerStripToUnits();
    syncBoxesPerCartonToUnits();
}

function unitRowFactor(row) {
    if (row.sell_unit === form.base_unit) {
        return 1;
    }
    const factor = Number(row.conversion_factor);
    return Number.isNaN(factor) || factor <= 0 ? 0 : factor;
}

const CONVERSION_FACTOR_DECIMALS = 4;

function formatConversionFactor(value) {
    const n = Number(value);
    if (Number.isNaN(n) || n <= 0) {
        return 0.0001;
    }
    const scale = 10 ** CONVERSION_FACTOR_DECIMALS;
    return Math.round(n * scale) / scale;
}

function formatDerivedPrice(value) {
    if (Number.isNaN(value) || value < 0) {
        return '0';
    }
    const rounded = Math.round(value * 10000) / 10000;
    return Number.isInteger(rounded) ? String(rounded) : String(rounded);
}

function syncDerivedUnitPricesFromStrip() {
    const stripRow = form.units.find((r) => r.sell_unit === 'strip');
    if (!stripRow) {
        return;
    }

    const stripFactor = unitRowFactor(stripRow);
    if (stripFactor <= 0) {
        return;
    }

    const stripPurchase = Number(stripRow.purchase_price);
    const stripSale = Number(stripRow.sale_price);
    const hasPurchase = stripRow.purchase_price !== '' && !Number.isNaN(stripPurchase);
    const hasSale = stripRow.sale_price !== '' && !Number.isNaN(stripSale);

    const basePurchase = hasPurchase ? stripPurchase / stripFactor : null;
    const baseSale = hasSale ? stripSale / stripFactor : null;

    form.units.forEach((row) => {
        if (row.sell_unit === 'strip') {
            return;
        }
        if (row.sell_unit === 'piece' || row.sell_unit === 'box' || row.sell_unit === 'carton') {
            const factor = unitRowFactor(row);
            if (factor <= 0) {
                return;
            }
            if (basePurchase !== null) {
                row.purchase_price = formatDerivedPrice(basePurchase * factor);
            }
            if (baseSale !== null) {
                row.sale_price = formatDerivedPrice(baseSale * factor);
            }
        }
    });
}

function onStripPriceInput(row) {
    if (row.sell_unit !== 'strip') {
        return;
    }
    syncDerivedUnitPricesFromStrip();
}

function onUnitConversionInput(row) {
    if (row.sell_unit === 'box') {
        syncBoxesPerCartonToUnits();
    }
    if ((row.sell_unit === 'box' || row.sell_unit === 'carton') && form.units.some((r) => r.sell_unit === 'strip')) {
        syncDerivedUnitPricesFromStrip();
    }
}

function onConversionFactorBlur(row) {
    if (row.sell_unit === form.base_unit) {
        return;
    }
    row.conversion_factor = formatConversionFactor(row.conversion_factor);
}

function syncPiecesPerStripToUnits() {
    const pps = Number(form.pieces_per_strip);
    if (!pps || pps <= 0) {
        return;
    }

    if (form.base_unit === 'strip') {
        let pieceRow = form.units.find((r) => r.sell_unit === 'piece');
        const pieceFactor = formatConversionFactor(1 / pps);
        if (!pieceRow) {
            form.units.push({
                sell_unit: 'piece',
                conversion_factor: pieceFactor,
                purchase_price: '0',
                sale_price: '0',
                is_default: false,
            });
        } else {
            pieceRow.conversion_factor = pieceFactor;
        }
    } else if (form.base_unit === 'piece') {
        let stripRow = form.units.find((r) => r.sell_unit === 'strip');
        const stripFactor = formatConversionFactor(pps);
        if (!stripRow) {
            form.units.push({
                sell_unit: 'strip',
                conversion_factor: stripFactor,
                purchase_price: '0',
                sale_price: '0',
                is_default: false,
            });
        } else {
            stripRow.conversion_factor = stripFactor;
        }
    }

    syncDerivedUnitPricesFromStrip();
}

function syncBoxesPerCartonToUnits() {
    const bpc = Number(form.boxes_per_carton);
    if (!bpc || bpc <= 0) {
        return;
    }

    const boxRow = form.units.find((r) => r.sell_unit === 'box');
    const boxFactor = boxRow ? unitRowFactor(boxRow) : 0;
    if (boxFactor <= 0) {
        return;
    }

    const cartonFactor = formatConversionFactor(bpc * boxFactor);
    let cartonRow = form.units.find((r) => r.sell_unit === 'carton');
    if (!cartonRow) {
        form.units.push({
            sell_unit: 'carton',
            conversion_factor: cartonFactor,
            purchase_price: '0',
            sale_price: '0',
            is_default: false,
        });
    } else {
        cartonRow.conversion_factor = cartonFactor;
    }

    syncDerivedUnitPricesFromStrip();
}

watch(
    () => form.units.map((r) => r.sell_unit).join(','),
    () => {
        if (showPiecesPerStrip.value && form.pieces_per_strip) {
            syncPiecesPerStripToUnits();
        }
        if (showBoxesPerCarton.value && form.boxes_per_carton) {
            syncBoxesPerCartonToUnits();
        }
    },
);

function setDefault(idx) {
    form.units.forEach((row, i) => {
        row.is_default = i === idx;
    });
}

function addUnitRow() {
    const used = form.units.map((r) => r.sell_unit);
    const next = props.catalogOptions.sellUnits.find((u) => !used.includes(u)) ?? 'piece';
    form.units.push({
        sell_unit: next,
        conversion_factor: next === form.base_unit ? 1 : 1,
        purchase_price: '0',
        sale_price: '0',
        is_default: false,
    });
}

function removeUnitRow(idx) {
    const wasDefault = form.units[idx].is_default;
    form.units.splice(idx, 1);
    if (wasDefault && form.units.length) {
        form.units[0].is_default = true;
    }
}

function normalizePiecesPerStripForSubmit(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }
    const n = Number(value);
    if (Number.isNaN(n) || n <= 0) {
        return null;
    }
    return n;
}

function normalizeBoxesPerCartonForSubmit(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }
    const n = Number(value);
    if (Number.isNaN(n) || n <= 0) {
        return null;
    }
    return n;
}

function buildPayload() {
    syncPiecesPerStripToUnits();
    syncBoxesPerCartonToUnits();

    const payload = {
        ...form.data(),
        pieces_per_strip: normalizePiecesPerStripForSubmit(form.pieces_per_strip),
        boxes_per_carton: normalizeBoxesPerCartonForSubmit(form.boxes_per_carton),
        units: form.units.map((row) => ({
            sell_unit: row.sell_unit,
            conversion_factor:
                row.sell_unit === form.base_unit ? 1 : formatConversionFactor(row.conversion_factor),
            purchase_price: row.purchase_price,
            sale_price: row.sale_price,
            is_default: row.is_default,
        })),
    };

    if (!existing) {
        delete payload.stock_adjustment;
        delete payload.stock_adjust_batch_id;
        delete payload.stock_adjust_batch_no;
    } else {
        delete payload.opening_batch_no;
        delete payload.opening_expiry_date;
        delete payload.opening_quantity;
        if (payload.stock_adjustment === null || payload.stock_adjustment === '') {
            delete payload.stock_adjustment;
            delete payload.stock_adjust_batch_id;
            delete payload.stock_adjust_batch_no;
        }
    }

    return payload;
}

function submit() {
    const payload = buildPayload();

    if (existing) {
        form.transform(() => payload).put(`/products/${existing.id}`);
    } else {
        form.transform(() => payload).post('/products');
    }
}
</script>
