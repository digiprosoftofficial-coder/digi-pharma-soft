<template>
    <TenantShellLayout :page-title="t('purchases.new_purchase_return')">
        <Head :title="t('purchases.new_purchase_return')" />
        <h1 class="h4 mb-3">{{ t('purchases.new_purchase_return') }}</h1>
        <div v-if="form.errors.lines" class="alert alert-danger small">{{ form.errors.lines }}</div>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label">{{ t('purchases.supplier') }}</label>
                    <select v-model.number="form.supplier_id" class="form-select" required>
                        <option :value="null" disabled>{{ t('purchases.all_suppliers') }}</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('purchases.link_purchase') }}</label>
                    <select v-model="form.purchase_id" class="form-select">
                        <option :value="null">—</option>
                        <option
                            v-for="p in purchasesForSupplier"
                            :key="p.id"
                            :value="p.id"
                        >
                            {{ p.invoice_no }} ({{ p.purchased_at }})
                        </option>
                    </select>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('purchases.notes') }}</label>
                <textarea v-model="form.notes" class="form-control" rows="2" :placeholder="t('purchases.notes_placeholder')" />
            </div>

            <div class="card bg-light border-0 mb-3">
                <div class="card-body py-3">
                    <label class="form-label">{{ t('purchases.return_search') }}</label>
                    <input
                        v-model="searchQ"
                        type="search"
                        class="form-control"
                        :placeholder="t('purchases.search_placeholder')"
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
                                <label class="form-label small">{{ t('purchases.batch') }}</label>
                                <select v-model.number="pendingBatchId" class="form-select form-select-sm">
                                    <option v-for="b in pickBatches" :key="b.id" :value="b.id">
                                        {{ formatBatchLabel(b) }} — {{ t('purchases.stock_on_hand') }} {{ formatQty(b.quantity_on_hand) }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">{{ t('purchases.qty') }}</label>
                                <input
                                    v-model.number="pendingQty"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    class="form-control form-control-sm"
                                />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">{{ t('purchases.unit_cost') }} ({{ currencyCode() }})</label>
                                <input v-model.number="pendingCost" type="number" min="0" step="0.01" class="form-control form-control-sm" />
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-sm btn-primary w-100" @click="appendReturnLine">
                                    {{ t('purchases.return_add_product') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="h6">{{ t('purchases.return_lines') }}</h2>
            <p v-if="!form.lines.length" class="text-muted small">{{ t('purchases.return_lines_hint') }}</p>
            <div v-for="(line, i) in form.lines" :key="i" class="row g-2 align-items-end border-bottom py-2">
                <div class="col-md-5">
                    <span class="small fw-medium">{{ line.label }}</span>
                    <div class="text-muted small">{{ formatBatchLabel({ batch_no: line.batch_no, expiry_date: line.expiry_date }) }}</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ t('purchases.qty') }}</label>
                    <input v-model.number="line.quantity" type="number" min="0.01" step="0.01" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ t('purchases.unit_cost') }} ({{ currencyCode() }})</label>
                    <input v-model.number="line.unit_cost" type="number" min="0" step="0.01" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="form.lines.splice(i, 1)">×</button>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" :disabled="form.processing || !form.lines.length">
                    {{ t('purchases.save_return') }}
                </button>
                <Link href="/purchases/returns" class="btn btn-link">{{ t('purchases.cancel') }}</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { batchesWithStock, formatBatchLabel } from '@/composables/usePosBatches';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    suppliers: { type: Array, required: true },
    purchases: { type: Array, required: true },
});

const { t } = useLocale();
const { currencyCode } = useMoney();
const { formatQty } = useQuantity();

const searchQ = ref('');
const searchResults = ref([]);
const picked = ref(null);
const pendingBatchId = ref(null);
const pendingQty = ref(1);
const pendingCost = ref(0);
let timer;

const form = useForm({
    supplier_id: props.suppliers[0]?.id ?? null,
    purchase_id: null,
    notes: '',
    lines: [],
});

const purchasesForSupplier = computed(() =>
    props.purchases.filter((p) => p.supplier_id === form.supplier_id),
);

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
    pendingCost.value = Number(batches[0].purchase_unit_cost ?? item.purchase_price ?? 0);
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
        quantity: Math.max(0.01, Number(pendingQty.value) || 1),
        unit_cost: Number(pendingCost.value || 0),
    });
    picked.value = null;
}

function submit() {
    form.transform((data) => ({
        ...data,
        lines: data.lines.map((l) => ({
            product_batch_id: l.product_batch_id,
            quantity: l.quantity,
            unit_cost: l.unit_cost,
        })),
    })).post('/purchases/returns');
}
</script>
