<template>
    <TenantShellLayout page-title="New return">
        <Head title="New return" />
        <h1 class="h4 mb-3">Record return to stock</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Link original sale (optional)</label>
                <select v-model="form.sale_id" class="form-select">
                    <option :value="null">— Walk-in return —</option>
                    <option v-for="s in sales" :key="s.id" :value="s.id">{{ s.invoice_no }} ({{ s.sold_at }})</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Notes</label>
                <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
            </div>

            <div class="card bg-light border-0 mb-3">
                <div class="card-body py-3">
                    <label class="form-label">{{ t('sales.return_search') }}</label>
                    <input
                        v-model="searchQ"
                        type="search"
                        class="form-control"
                        placeholder="Name, SKU, or barcode"
                        autocomplete="off"
                        @input="debouncedSearch"
                        @keydown.enter.prevent="onSearchEnter"
                    />
                    <ul v-if="searchResults.length" class="list-group list-group-flush mt-2 small">
                        <li
                            v-for="item in searchResults"
                            :key="item.id"
                            class="list-group-item list-group-item-action"
                            @click="pickProduct(item)"
                        >
                            {{ item.name }} <span class="text-muted">({{ item.sku }})</span>
                        </li>
                    </ul>
                    <div v-if="picked" class="mt-3 pt-3 border-top">
                        <p class="fw-medium mb-2">{{ picked.name }}</p>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small">Batch</label>
                                <select v-model.number="pendingBatchId" class="form-select form-select-sm">
                                    <option v-for="b in pickBatches" :key="b.id" :value="b.id">
                                        {{ formatBatchLabel(b) }} — stock {{ b.quantity_on_hand }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Qty</label>
                                <input
                                    v-model.number="pendingQty"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="form-control form-control-sm"
                                />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Refund unit price ({{ currencyCode() }})</label>
                                <input v-model.number="pendingPrice" type="number" min="0" step="0.01" class="form-control form-control-sm" />
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-sm btn-primary w-100" @click="appendReturnLine">
                                    {{ t('sales.return_add_product') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="h6">Lines</h2>
            <p v-if="!form.lines.length" class="text-muted small">Add products using search above.</p>
            <div v-for="(line, i) in form.lines" :key="i" class="row g-2 align-items-end border-bottom py-2">
                <div class="col-md-5">
                    <span class="small fw-medium">{{ line.label }}</span>
                    <div class="text-muted small">{{ formatBatchLabel({ batch_no: line.batch_no, expiry_date: line.expiry_date }) }}</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Qty</label>
                    <input
                        v-model.number="line.quantity"
                        type="number"
                        min="1"
                        step="1"
                        class="form-control form-control-sm"
                        required
                        @change="normalizeLineQuantity(line)"
                    />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Refund unit price ({{ currencyCode() }})</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model.number="line.unit_price" type="number" min="0" step="0.01" class="form-control form-control-sm" required />
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="form.lines.splice(i, 1)">×</button>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" :disabled="form.processing || !form.lines.length">Save return</button>
                <Link href="/sales/returns" class="btn btn-link">Cancel</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { batchesWithStock, formatBatchLabel } from '@/composables/usePosBatches';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { defaultSellUnit, unitSalePrice } from '@/composables/useProductUnits';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({ sales: { type: Array, required: true } });

const { t } = useLocale();
const { currencyCode, currencySymbol } = useMoney();

const searchQ = ref('');
const searchResults = ref([]);
const picked = ref(null);
const pendingBatchId = ref(null);
const pendingQty = ref(1);
const pendingPrice = ref(0);
let timer;

const form = useForm({
    sale_id: null,
    notes: '',
    lines: [],
});

const pickBatches = computed(() => (picked.value ? batchesWithStock(picked.value) : []));

function debouncedSearch() {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 250);
}

async function runSearch() {
    if (searchQ.value.length < 1) {
        searchResults.value = [];
        return;
    }
    const { data } = await window.axios.get('/catalog/product-search', { params: { q: searchQ.value } });
    searchResults.value = data.data;
}

async function onSearchEnter() {
    await runSearch();
    if (searchResults.value.length === 1) {
        pickProduct(searchResults.value[0]);
    }
}

function pickProduct(item) {
    const batches = batchesWithStock(item);
    if (!batches.length) {
        alert(t('catalog.pos_no_sellable_batch'));
        return;
    }
    picked.value = item;
    pendingBatchId.value = batches[0].id;
    pendingQty.value = 1;
    const sellUnit = defaultSellUnit(item);
    pendingPrice.value = unitSalePrice(
        { units: item.units, sale_price: item.sale_price },
        sellUnit,
    );
    searchQ.value = '';
    searchResults.value = [];
}

function appendReturnLine() {
    const batch = pickBatches.value.find((b) => b.id === pendingBatchId.value);
    if (!batch || !picked.value) {
        return;
    }
    form.lines.push({
        product_batch_id: batch.id,
        batch_no: batch.batch_no,
        expiry_date: batch.expiry_date,
        label: picked.value.name,
        quantity: Math.max(1, Math.round(Number(pendingQty.value) || 1)),
        unit_price: Number(pendingPrice.value || 0),
    });
    picked.value = null;
}

function normalizeLineQuantity(line) {
    const n = Math.round(Number(line.quantity) || 1);
    line.quantity = Math.max(1, n);
}

function submit() {
    form.lines.forEach(normalizeLineQuantity);
    form.transform((data) => ({
        ...data,
        lines: data.lines.map((l) => ({
            product_batch_id: l.product_batch_id,
            quantity: l.quantity,
            unit_price: l.unit_price,
        })),
    })).post('/sales/returns');
}
</script>
