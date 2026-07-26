<template>
    <TenantShellLayout page-title="New purchase">
        <Head title="New purchase" />
        <h1 class="h4 mb-3">Record purchase</h1>
        <form class="card border-0 shadow-sm card-body purchase-form" @submit.prevent="submit">
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

            <div class="card purchase-add-product mb-3">
                <div class="card-body py-3">
                    <div class="purchase-add-product__eyebrow">{{ t('purchases.add_product_eyebrow') }}</div>
                    <label class="form-label fw-semibold mb-1" for="purchase-product-search">{{ t('purchases.add_product') }}</label>
                    <p class="small text-muted mb-2">{{ t('purchases.add_product_hint') }}</p>
                    <div class="purchase-add-product__search input-group input-group-lg">
                        <span class="input-group-text purchase-add-product__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                        </span>
                        <input
                            id="purchase-product-search"
                            v-model="searchQuery"
                            type="search"
                            class="form-control purchase-add-product__input"
                            :placeholder="t('purchases.add_product_placeholder')"
                            autocomplete="off"
                            @input="debouncedSearch"
                        />
                    </div>
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
                    <p v-else-if="searchQuery.length >= 2 && !searching" class="small text-muted mb-0 mt-2">{{ t('purchases.no_products_found') }}</p>
                </div>
            </div>

            <div class="alert alert-info small py-2 mb-3" role="note">
                {{ purchaseBatchTip }}
            </div>

            <h2 class="h6">{{ t('purchases.purchase_lines') }}</h2>
            <p v-if="!form.lines.length" class="text-muted small">{{ t('purchases.lines_hint') }}</p>
            <div v-if="form.errors.lines" class="text-danger small mb-2">{{ form.errors.lines }}</div>
            <div v-if="lineEditorError" class="alert alert-warning py-2 small mb-2">{{ lineEditorError }}</div>
            <div
                v-if="incompleteLinesCount > 0"
                class="alert alert-warning py-2 small mb-2 d-md-none"
                role="status"
            >
                {{ t('purchases.incomplete_lines_count', { count: incompleteLinesCount }) }}
            </div>

            <!-- Mobile: compact summaries -->
            <div class="d-md-none">
                <div
                    v-for="(line, i) in form.lines"
                    :key="line._key"
                    class="purchase-line-summary card border-0 shadow-sm mb-2"
                    :class="{ 'purchase-line-summary--incomplete': lineIsIncomplete(line) }"
                >
                    <button type="button" class="purchase-line-summary__body text-start" @click="openLineEditor(i)">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="min-w-0">
                                <div class="fw-semibold text-truncate">{{ line.product_name }}</div>
                                <div class="small text-muted text-truncate">{{ line.product_sku }}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                                <span class="badge text-bg-light text-dark border">{{ i + 1 }}</span>
                                <span
                                    v-if="lineIsIncomplete(line)"
                                    class="badge text-bg-warning text-dark"
                                >
                                    {{ t('purchases.line_incomplete_badge') }}
                                </span>
                            </div>
                        </div>
                        <div class="purchase-line-summary__meta mt-2">
                            <div :class="{ 'purchase-line-summary__meta-item--missing': !hasValidQuantity(line) }">
                                <span class="text-muted">{{ t('purchases.qty') }}</span>
                                <strong>{{ formatQty(line.quantity) }} {{ unitLabel(line.sell_unit) }}</strong>
                            </div>
                            <div :class="{ 'purchase-line-summary__meta-item--missing': !hasValidUnitCost(line) }">
                                <span class="text-muted">{{ t('purchases.unit_cost') }}</span>
                                <strong>{{ formatMoney(line.unit_cost) }}</strong>
                            </div>
                            <div>
                                <span class="text-muted">{{ t('purchases.line_total') }}</span>
                                <strong>{{ formatMoney(Number(line.quantity || 0) * Number(line.unit_cost || 0)) }}</strong>
                            </div>
                            <div :class="{ 'purchase-line-summary__meta-item--missing': !hasValidBatch(line) }">
                                <span class="text-muted">{{ t('purchases.batch') }}</span>
                                <strong>{{ line.batch_no || t('purchases.new_batch_needed') }}</strong>
                            </div>
                            <div :class="{ 'purchase-line-summary__meta-item--missing': !hasValidExpiry(line) }">
                                <span class="text-muted">{{ t('purchases.expiry') }}</span>
                                <strong>{{ line.expiry_date || t('purchases.required_date_missing') }}</strong>
                            </div>
                            <div :class="{ 'purchase-line-summary__meta-item--missing': !hasValidManufacturedAt(line) }">
                                <span class="text-muted">{{ t('purchases.manufactured_at') }}</span>
                                <strong>{{ line.manufactured_at || t('purchases.required_date_missing') }}</strong>
                            </div>
                        </div>
                        <div
                            v-if="lineIsIncomplete(line)"
                            class="small text-warning-emphasis fw-semibold mt-2"
                        >
                            {{ t('purchases.line_missing_fields', { fields: lineMissingFieldsLabel(line) }) }}
                        </div>
                        <div v-else class="small text-primary mt-2">{{ t('purchases.tap_to_edit_line') }}</div>
                    </button>
                    <div class="purchase-line-summary__actions px-3 pb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" @click="openLineEditor(i)">
                            {{ t('common.edit') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(i)">
                            {{ t('common.delete') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop: full inline editors -->
            <div class="d-none d-md-block">
                <div v-for="(line, i) in form.lines" :key="line._key" class="purchase-line-card border rounded p-3 mb-2 bg-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <span class="fw-semibold">{{ line.product_name }}</span>
                            <span class="text-muted small ms-2">{{ line.product_sku }}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger purchase-line-remove" @click="removeLine(i)">
                            {{ t('common.delete') }}
                        </button>
                    </div>
                    <PurchaseLineFields
                        :line="line"
                        :storage-locations="storageLocations"
                        :require-fields="true"
                        @batch-change="applyBatchPick"
                        @unit-change="onUnitChange"
                    />
                </div>
            </div>

            <!-- Mobile bottom sheet editor -->
            <Teleport to="body">
                <div
                    v-if="editingLineIndex !== null && editingLine"
                    class="purchase-line-sheet-root"
                >
                    <div class="purchase-line-sheet-backdrop" @click="tryCloseLineEditor" />
                    <div
                        class="purchase-line-sheet"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="t('purchases.edit_line')"
                    >
                        <div class="purchase-line-sheet__handle" aria-hidden="true" />
                        <div class="purchase-line-sheet__header">
                            <div class="min-w-0">
                                <div class="small text-muted mb-0">{{ t('purchases.edit_line') }}</div>
                                <div class="fw-semibold text-truncate">{{ editingLine.product_name }}</div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-light purchase-line-sheet__close"
                                :aria-label="t('common.close', 'Close')"
                                @click="tryCloseLineEditor"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M18 6 6 18" />
                                    <path d="m6 6 12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="purchase-line-sheet__body">
                            <div
                                v-if="lineEditorError"
                                class="alert alert-warning py-2 small mb-3"
                                role="alert"
                            >
                                {{ lineEditorError }}
                            </div>
                            <PurchaseLineFields
                                :line="editingLine"
                                :storage-locations="storageLocations"
                                :require-fields="true"
                                @batch-change="applyBatchPick"
                                @unit-change="onUnitChange"
                            />
                        </div>
                        <div class="purchase-line-sheet__footer">
                            <button
                                type="button"
                                class="btn btn-outline-danger purchase-line-sheet__remove"
                                :aria-label="t('purchases.remove_line')"
                                :title="t('purchases.remove_line')"
                                @click="removeEditingLine"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4h8v2" />
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                    <path d="M10 11v6" />
                                    <path d="M14 11v6" />
                                </svg>
                            </button>
                            <button type="button" class="btn btn-primary purchase-line-sheet__done" @click="tryCloseLineEditor">
                                {{ t('purchases.done_editing_line') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold">Purchase summary</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Tax ({{ currencyCode() }})</label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ currencySymbol() }}</span>
                                        <input v-model.number="form.tax" type="number" min="0" step="0.01" class="form-control" />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">{{ discountLabel }}</label>
                                    <div class="input-group">
                                        <select
                                            v-model="form.discount_type"
                                            class="form-select"
                                            style="max-width: 5.5rem"
                                            :aria-label="t('purchases.discount_type')"
                                        >
                                            <option value="amount">{{ currencySymbol() }}</option>
                                            <option value="percent">%</option>
                                        </select>
                                        <input v-model.number="form.discount" type="number" min="0" step="0.01" class="form-control" />
                                    </div>
                                    <div v-if="form.discount_type === 'percent'" class="form-text">
                                        {{ t('purchases.discount_amount_preview', { amount: formatMoney(normalizedDiscount) }) }}
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
                        </div>
                        <div class="col-lg-5">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted">Subtotal</td>
                                        <td class="text-end">{{ formatMoney(purchaseSubtotal) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Discount</td>
                                        <td class="text-end">-{{ formatMoney(normalizedDiscount) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Tax</td>
                                        <td class="text-end">{{ formatMoney(normalizedTax) }}</td>
                                    </tr>
                                    <tr class="fw-semibold border-top">
                                        <td>Total</td>
                                        <td class="text-end">{{ formatMoney(purchaseTotal) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Paid</td>
                                        <td class="text-end">{{ formatMoney(normalizedPaid) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Due</td>
                                        <td class="text-end fw-semibold" :class="{ 'text-danger': purchaseDue > 0 }">
                                            {{ formatMoney(purchaseDue) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="purchase-actions mt-3 d-grid d-sm-flex gap-2">
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="form.processing || !form.lines.length"
                >
                    <template v-if="incompleteLinesCount > 0">
                        {{ t('purchases.save_purchase_incomplete', { count: incompleteLinesCount }) }}
                    </template>
                    <template v-else>
                        {{ t('purchases.save_purchase') }}
                    </template>
                </button>
                <Link href="/purchases" class="btn btn-link">Cancel</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import PurchaseLineFields from '@/Components/Purchasing/PurchaseLineFields.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import {
    boxConversionFactor,
    buildPurchaseUnitOptions,
    catalogBoxesPerCarton,
    catalogPiecesPerBox,
    catalogPiecesPerStrip,
    catalogStripsPerBox,
    defaultSellUnit,
    unitLabel,
    unitPurchasePrice,
    unitSalePrice,
} from '@/composables/useProductUnits';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    paymentMethods: { type: Array, default: () => [] },
    storageLocations: { type: Array, default: () => [] },
    prefillProducts: { type: Array, default: () => [] },
});

function locationLabel(loc) {
    if (!loc) {
        return '';
    }

    return loc.code ? `${loc.name} (${loc.code})` : loc.name;
}

const { t } = useLocale();
const { formatMoney, currencyCode, currencySymbol } = useMoney();
const { formatQty } = useQuantity();
const page = usePage();
const stripProductTypes = computed(() => page.props.catalogOptions?.stripProductTypes ?? ['tablet', 'capsule']);

const purchaseBatchTip = computed(() => t('catalog.purchase_batch_pack_tip'));
const editingLineIndex = ref(null);
const lineEditorError = ref('');

const editingLine = computed(() => {
    if (editingLineIndex.value === null) {
        return null;
    }
    return form.lines[editingLineIndex.value] ?? null;
});

const incompleteLinesCount = computed(() => form.lines.filter((line) => lineIsIncomplete(line)).length);

function isMobilePurchaseUi() {
    return typeof window !== 'undefined' && window.matchMedia('(max-width: 767.98px)').matches;
}

function openLineEditor(index) {
    editingLineIndex.value = index;
    lineEditorError.value = '';
}

function closeLineEditor() {
    editingLineIndex.value = null;
    lineEditorError.value = '';
}

function tryCloseLineEditor() {
    const line = editingLine.value;
    if (line && lineIsIncomplete(line)) {
        lineEditorError.value = t('purchases.done_line_incomplete', {
            fields: lineMissingFieldsLabel(line),
        });
        void nextTick(scrollSheetBodyToTop);
        return;
    }
    closeLineEditor();
}

function scrollSheetBodyToTop() {
    document.querySelector('.purchase-line-sheet__body')?.scrollTo?.({ top: 0, behavior: 'smooth' });
}

function removeEditingLine() {
    const index = editingLineIndex.value;
    closeLineEditor();
    if (index !== null) {
        removeLine(index);
    }
}

watch(editingLineIndex, (index) => {
    if (typeof document === 'undefined') {
        return;
    }
    document.body.classList.toggle('purchase-line-sheet-open', index !== null);
});

watch(
    () => {
        const line = editingLine.value;
        if (!line) {
            return true;
        }
        return lineIsIncomplete(line);
    },
    (incomplete) => {
        if (!incomplete) {
            lineEditorError.value = '';
        }
    },
);

onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
        document.body.classList.remove('purchase-line-sheet-open');
    }
});

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
    discount_type: 'amount',
    discount: 0,
    paid: 0,
    payment_method: props.paymentMethods[0]?.value ?? 'cash',
    lines: [],
});

const purchaseSubtotal = computed(() =>
    form.lines.reduce((sum, line) => {
        const quantity = Number(line.quantity);
        const unitCost = Number(line.unit_cost);

        if (Number.isNaN(quantity) || Number.isNaN(unitCost)) {
            return sum;
        }

        return sum + quantity * unitCost;
    }, 0)
);
const normalizedTax = computed(() => Math.max(0, Number(form.tax) || 0));
const discountInput = computed(() => Math.max(0, Number(form.discount) || 0));
const normalizedDiscount = computed(() => {
    if (form.discount_type === 'percent') {
        return Math.min(purchaseSubtotal.value, purchaseSubtotal.value * Math.min(100, discountInput.value) / 100);
    }

    return Math.min(purchaseSubtotal.value, discountInput.value);
});
const normalizedPaid = computed(() => Math.max(0, Number(form.paid) || 0));
const purchaseTotal = computed(() => Math.max(0, purchaseSubtotal.value + normalizedTax.value - normalizedDiscount.value));
const purchaseDue = computed(() => Math.max(0, purchaseTotal.value - normalizedPaid.value));
const discountLabel = computed(() =>
    form.discount_type === 'percent'
        ? t('purchases.discount_percent')
        : t('purchases.discount_amount', { currency: currencyCode() }),
);

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
    return buildPurchaseUnitOptions(product, { stripTypes: stripProductTypes.value });
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

function resolveBoxFactor(line) {
    if (line.base_unit === 'box') {
        return 1;
    }
    const fromUnits = boxConversionFactor(line.unit_options);
    if (fromUnits > 0) {
        return fromUnits;
    }
    if (line.base_unit === 'strip') {
        const strips = Number(line.pack_strips_per_box ?? line.catalog_strips_per_box);
        return strips > 0 ? strips : 0;
    }
    if (line.base_unit === 'piece') {
        const pieces = Number(line.pack_pieces_per_box ?? line.catalog_pieces_per_box);
        return pieces > 0 ? pieces : 0;
    }
    return 0;
}

function usesBoxesPerCarton(line) {
    return line.sell_unit === 'carton' && resolveBoxFactor(line) > 0;
}

function usesStripsPerBox(line) {
    return line.sell_unit === 'box' && line.base_unit === 'strip';
}

function usesPiecesPerBox(line) {
    return line.sell_unit === 'box' && line.base_unit === 'piece';
}

function usesPiecesPerStrip(line) {
    return line.sell_unit === 'piece' && line.base_unit === 'strip';
}

function usesPackSizeFriendlyInput(line) {
    return usesStripsPerBox(line) || usesPiecesPerBox(line) || usesBoxesPerCarton(line) || usesPiecesPerStrip(line);
}

function packSizeLabel(line) {
    if (usesBoxesPerCarton(line)) {
        return t('catalog.purchase_boxes_per_carton');
    }
    if (usesStripsPerBox(line)) {
        return t('catalog.purchase_strips_per_box');
    }
    if (usesPiecesPerBox(line)) {
        return t('catalog.purchase_pieces_per_box');
    }
    if (usesPiecesPerStrip(line)) {
        return t('catalog.purchase_pieces_per_strip');
    }
    return `${unitLabel(line.base_unit)} per 1 ${unitLabel(line.sell_unit)} (this receipt)`;
}

function defaultStripsPerBox(line) {
    const product = { units: line.unit_options, strips_per_box: line.catalog_strips_per_box, base_unit: line.base_unit };
    const value = catalogStripsPerBox(product);
    return value && value > 0 ? value : defaultConversion(line);
}

function defaultPiecesPerBox(line) {
    const product = { units: line.unit_options, pieces_per_box: line.catalog_pieces_per_box, base_unit: line.base_unit };
    const value = catalogPiecesPerBox(product);
    return value && value > 0 ? value : defaultConversion(line);
}

function defaultPiecesPerStrip(line) {
    const value = catalogPiecesPerStrip({ pieces_per_strip: line.catalog_pieces_per_strip });
    if (value && value > 0) {
        return value;
    }
    const factor = defaultConversion(line);
    return factor > 0 && factor < 1 ? formatConversionFactor(1 / factor) : 10;
}

function defaultBoxesPerCarton(line) {
    const product = { units: line.unit_options, boxes_per_carton: line.catalog_boxes_per_carton };
    const value = catalogBoxesPerCarton(product);
    const boxFactor = resolveBoxFactor(line);
    return value && value > 0 ? value : defaultConversion(line) / Math.max(0.0001, boxFactor);
}

function syncLineConversionFromPackInput(line) {
    if (usesStripsPerBox(line)) {
        const strips = Number(line.pack_strips_per_box);
        if (!Number.isNaN(strips) && strips > 0) {
            line.conversion_factor = formatConversionFactor(strips);
        }
        return;
    }
    if (usesPiecesPerBox(line)) {
        const pieces = Number(line.pack_pieces_per_box);
        if (!Number.isNaN(pieces) && pieces > 0) {
            line.conversion_factor = formatConversionFactor(pieces);
        }
        return;
    }
    if (usesPiecesPerStrip(line)) {
        const pieces = Number(line.pack_pieces_per_strip);
        if (!Number.isNaN(pieces) && pieces > 0) {
            line.conversion_factor = formatConversionFactor(1 / pieces);
        }
        return;
    }
    if (!usesBoxesPerCarton(line)) {
        return;
    }
    const boxes = Number(line.pack_boxes_per_carton);
    const boxFactor = resolveBoxFactor(line);
    if (Number.isNaN(boxes) || boxes <= 0 || boxFactor <= 0) {
        return;
    }
    line.conversion_factor = formatConversionFactor(boxes * boxFactor);
}

function initLinePackFields(line) {
    if (usesStripsPerBox(line)) {
        line.pack_strips_per_box = defaultStripsPerBox(line);
        syncLineConversionFromPackInput(line);
    } else if (usesPiecesPerBox(line)) {
        line.pack_pieces_per_box = defaultPiecesPerBox(line);
        syncLineConversionFromPackInput(line);
    } else if (usesPiecesPerStrip(line)) {
        line.pack_pieces_per_strip = defaultPiecesPerStrip(line);
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
    if (usesPiecesPerBox(line)) {
        const def = defaultPiecesPerBox(line);
        const current = Number(line.pack_pieces_per_box);
        return !Number.isNaN(current) && Math.abs(current - def) > 0.0001;
    }
    if (usesPiecesPerStrip(line)) {
        const def = defaultPiecesPerStrip(line);
        const current = Number(line.pack_pieces_per_strip);
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
    if (usesPiecesPerBox(line)) {
        return t('catalog.purchase_catalog_default_pieces', { qty: formatQty(defaultPiecesPerBox(line)) });
    }
    if (usesPiecesPerStrip(line)) {
        return t('catalog.purchase_catalog_default_pieces', { qty: formatQty(defaultPiecesPerStrip(line)) });
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
    const existingBatches = Array.isArray(product.batches)
        ? product.batches
        : (product.batches?.data ?? []);
    const unitOptions = buildUnitOptions(product);
    const line = {
        _key: `${product.id}-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
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
        product_type: product.product_type ?? 'other',
        catalog_pieces_per_strip: product.pieces_per_strip ?? null,
        catalog_strips_per_box: product.strips_per_box ?? null,
        catalog_pieces_per_box: product.pieces_per_box ?? null,
        catalog_boxes_per_carton: product.boxes_per_carton ?? null,
        pack_strips_per_box: null,
        pack_pieces_per_box: null,
        pack_pieces_per_strip: null,
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

    if (isMobilePurchaseUi()) {
        openLineEditor(form.lines.length - 1);
    }
}

function applyPrefillProducts() {
    if (!props.prefillProducts?.length || form.lines.length) {
        return;
    }

    const seen = new Set();
    for (const product of props.prefillProducts) {
        if (!product?.id || seen.has(product.id)) {
            continue;
        }
        seen.add(product.id);
        addProductLine(product);
    }

    if (typeof window !== 'undefined' && window.history?.replaceState) {
        window.history.replaceState({}, '', window.location.pathname);
    }
}

onMounted(() => {
    applyPrefillProducts();
});

function applyBatchPick(line) {
    if (line.batch_pick === '__new__') {
        line.batch_no = '';
        line.expiry_date = '';
        line.manufactured_at = '';
        return;
    }
    const batch = line.existing_batches.find((b) => b.batch_no === line.batch_pick);
    if (batch) {
        line.batch_no = batch.batch_no;
        line.expiry_date = batch.expiry_date ?? '';
        line.manufactured_at = batch.manufactured_at ?? '';
        line.storage_location_id = batch.storage_location_id ?? line.storage_location_id;
        if (batch.pack_sell_unit && batch.pack_conversion_factor) {
            line.sell_unit = batch.pack_sell_unit;
            line.conversion_factor = Number(batch.pack_conversion_factor);
            if (usesStripsPerBox(line)) {
                line.pack_strips_per_box = Number(batch.pack_conversion_factor);
            } else if (usesPiecesPerBox(line)) {
                line.pack_pieces_per_box = Number(batch.pack_conversion_factor);
            } else if (usesPiecesPerStrip(line)) {
                const factor = Number(batch.pack_conversion_factor);
                line.pack_pieces_per_strip = factor > 0 ? formatConversionFactor(1 / factor) : null;
            } else if (usesBoxesPerCarton(line)) {
                const boxFactor = resolveBoxFactor(line);
                line.pack_boxes_per_carton =
                    boxFactor > 0 ? formatConversionFactor(Number(batch.pack_conversion_factor) / boxFactor) : null;
            }
        }
    }
}

async function onUnitChange(line) {
    const product = {
        units: line.unit_options,
        purchase_price: line.unit_options.find((u) => u.sell_unit === line.base_unit)?.purchase_price
            ?? line.unit_options[0]?.purchase_price,
        sale_price: line.unit_options.find((u) => u.sell_unit === line.base_unit)?.sale_price
            ?? line.unit_options[0]?.sale_price,
    };
    line.unit_cost = unitPurchasePrice(product, line.sell_unit);
    line.sale_price = unitSalePrice(product, line.sell_unit);
    line.pack_strips_per_box = null;
    line.pack_pieces_per_box = null;
    line.pack_pieces_per_strip = null;
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
    if (editingLineIndex.value === index) {
        closeLineEditor();
    } else if (editingLineIndex.value !== null && editingLineIndex.value > index) {
        editingLineIndex.value -= 1;
    }
    form.lines.splice(index, 1);
}

function hasValidQuantity(line) {
    return Number(line.quantity) > 0;
}

function hasValidUnitCost(line) {
    return !(
        line.unit_cost === ''
        || line.unit_cost === null
        || Number.isNaN(Number(line.unit_cost))
        || Number(line.unit_cost) < 0
    );
}

function hasValidBatch(line) {
    if (line.batch_pick !== '__new__') {
        return Boolean(String(line.batch_no || '').trim());
    }
    return Boolean(String(line.batch_no || '').trim());
}

function hasValidExpiry(line) {
    return Boolean(String(line.expiry_date || '').trim());
}

function hasValidManufacturedAt(line) {
    return Boolean(String(line.manufactured_at || '').trim());
}

function hasValidPackSize(line) {
    if (!needsPackSize(line)) {
        return true;
    }
    if (usesStripsPerBox(line) && !(Number(line.pack_strips_per_box) > 0)) {
        return false;
    }
    if (usesPiecesPerBox(line) && !(Number(line.pack_pieces_per_box) > 0)) {
        return false;
    }
    if (usesPiecesPerStrip(line) && !(Number(line.pack_pieces_per_strip) > 0)) {
        return false;
    }
    if (usesBoxesPerCarton(line) && !(Number(line.pack_boxes_per_carton) > 0)) {
        return false;
    }
    if (!usesPackSizeFriendlyInput(line) && !(Number(line.conversion_factor) > 0)) {
        return false;
    }
    return true;
}

function lineMissingFieldKeys(line) {
    const missing = [];
    if (!hasValidBatch(line)) {
        missing.push('batch');
    }
    if (!hasValidExpiry(line)) {
        missing.push('expiry');
    }
    if (!hasValidManufacturedAt(line)) {
        missing.push('manufactured_at');
    }
    if (!hasValidQuantity(line)) {
        missing.push('qty');
    }
    if (!hasValidUnitCost(line)) {
        missing.push('unit_cost');
    }
    if (!hasValidPackSize(line)) {
        missing.push('pack_size');
    }
    return missing;
}

function lineMissingFieldsLabel(line) {
    return lineMissingFieldKeys(line)
        .map((key) => {
            if (key === 'batch') {
                return t('purchases.batch');
            }
            if (key === 'qty') {
                return t('purchases.qty');
            }
            if (key === 'expiry') {
                return t('purchases.expiry');
            }
            if (key === 'manufactured_at') {
                return t('purchases.manufactured_at');
            }
            if (key === 'unit_cost') {
                return t('purchases.unit_cost');
            }
            return t('purchases.pack_size');
        })
        .join(', ');
}

function lineIsIncomplete(line) {
    return lineMissingFieldKeys(line).length > 0;
}

function submit() {
    lineEditorError.value = '';
    const incompleteIndex = form.lines.findIndex((line) => lineIsIncomplete(line));
    if (incompleteIndex >= 0) {
        const line = form.lines[incompleteIndex];
        const message = t('purchases.line_incomplete_detail', {
            fields: lineMissingFieldsLabel(line),
        });
        if (isMobilePurchaseUi()) {
            editingLineIndex.value = incompleteIndex;
            lineEditorError.value = message;
            void nextTick(scrollSheetBodyToTop);
        } else {
            lineEditorError.value = message;
        }
        return;
    }

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

<style scoped>
.purchase-form :deep(.form-control),
.purchase-form :deep(.form-select),
.purchase-form :deep(.btn) {
    min-height: 2.35rem;
}

.purchase-add-product {
    border: 1px solid rgba(var(--bs-primary-rgb), 0.22);
    background: linear-gradient(180deg, rgba(var(--bs-primary-rgb), 0.07) 0%, #fff 55%);
    box-shadow: 0 0.4rem 1.1rem rgba(var(--bs-primary-rgb), 0.1);
}

.purchase-add-product__eyebrow {
    color: var(--bs-primary);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    margin-bottom: 0.15rem;
}

.purchase-add-product__search {
    --purchase-search-border: rgba(var(--bs-primary-rgb), 0.5);
    border: 2px solid var(--purchase-search-border);
    border-radius: 0.75rem;
    overflow: hidden;
    background: rgba(var(--bs-primary-rgb), 0.06);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.1);
}

.purchase-add-product__icon {
    color: var(--bs-primary);
    background: transparent;
    border: 0;
    padding-left: 0.95rem;
}

.purchase-add-product__input {
    border: 0 !important;
    background: transparent !important;
    font-size: 1.05rem;
    font-weight: 600;
    min-height: 3.15rem !important;
    box-shadow: none !important;
}

.purchase-add-product__input::placeholder {
    color: #64748b;
    font-weight: 500;
    opacity: 1;
}

.purchase-add-product__search:focus-within {
    --purchase-search-border: var(--bs-primary);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.18);
}

.purchase-line-summary__body {
    width: 100%;
    padding: 0.85rem 1rem 0.35rem;
    border: 0;
    background: transparent;
}

.purchase-line-summary--incomplete {
    border: 1px solid rgba(var(--bs-warning-rgb), 0.55) !important;
    box-shadow: 0 0.35rem 0.9rem rgba(var(--bs-warning-rgb), 0.18) !important;
    background: linear-gradient(180deg, rgba(var(--bs-warning-rgb), 0.08) 0%, #fff 55%);
}

.purchase-line-summary__meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem;
    font-size: 0.82rem;
}

.purchase-line-summary__meta > div {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 0.1rem;
    padding: 0.4rem 0.45rem;
    background: var(--bs-tertiary-bg);
    border-radius: 0.4rem;
}

.purchase-line-summary__meta-item--missing {
    background: rgba(var(--bs-warning-rgb), 0.16) !important;
    outline: 1px solid rgba(var(--bs-warning-rgb), 0.35);
}

.purchase-line-summary__meta-item--missing strong {
    color: #92400e;
}

.purchase-line-summary__actions {
    display: flex;
    gap: 0.5rem;
}

.purchase-line-summary__actions .btn {
    flex: 1 1 0;
}

@media (max-width: 575.98px) {
    .purchase-form {
        padding: 0.75rem !important;
    }

    .purchase-line-card {
        padding: 0.75rem !important;
    }

    .purchase-line-remove,
    .purchase-actions .btn {
        width: 100%;
    }
}
</style>

<style>
body.purchase-line-sheet-open {
    overflow: hidden;
}

.purchase-line-sheet-root {
    position: fixed;
    inset: 0;
    z-index: 1080;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.purchase-line-sheet-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
}

.purchase-line-sheet {
    position: relative;
    z-index: 1;
    display: flex;
    width: 100%;
    max-width: 40rem;
    max-height: min(92vh, 44rem);
    flex-direction: column;
    background: #fff;
    border-radius: 1rem 1rem 0 0;
    box-shadow: 0 -8px 28px rgba(15, 23, 42, 0.18);
}

.purchase-line-sheet__handle {
    width: 2.5rem;
    height: 0.28rem;
    margin: 0.55rem auto 0.15rem;
    background: #cbd5e1;
    border-radius: 999px;
}

.purchase-line-sheet__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.65rem 1rem 0.75rem;
    border-bottom: 1px solid var(--bs-border-color);
}

.purchase-line-sheet__body {
    flex: 1 1 auto;
    overflow: auto;
    padding: 1rem;
    -webkit-overflow-scrolling: touch;
}

.purchase-line-sheet__footer {
    display: flex;
    align-items: stretch;
    gap: 0.65rem;
    padding: 0.75rem 1rem calc(0.85rem + env(safe-area-inset-bottom));
    border-top: 1px solid var(--bs-border-color);
    background: #fff;
}

.purchase-line-sheet__close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 2.35rem;
    height: 2.35rem;
    padding: 0;
    border-radius: 999px;
}

.purchase-line-sheet__remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 3rem;
    min-height: 2.85rem;
    padding: 0;
}

.purchase-line-sheet__done {
    flex: 1 1 auto;
    min-height: 2.85rem;
    font-weight: 600;
}
</style>
