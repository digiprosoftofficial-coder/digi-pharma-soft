<template>
    <TenantShellLayout :page-title="t('tenant_nav.stock_transfer')">
        <Head :title="t('tenant_nav.stock_transfer')" />
        <h1 class="h4 mb-3">{{ t('tenant_nav.stock_transfer') }}</h1>
        <div v-if="form.errors.lines" class="alert alert-danger small">{{ form.errors.lines }}</div>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="row g-2 mb-3">
                <div v-if="multiBranch" class="col-md-4">
                    <label class="form-label">{{ t('branches.transfer_to_branch') }}</label>
                    <select v-model.number="form.to_branch_id" class="form-select" required>
                        <option :value="null" disabled>—</option>
                        <option
                            v-for="b in destinationBranches"
                            :key="b.id"
                            :value="b.id"
                        >
                            {{ b.name }} ({{ b.code }})
                        </option>
                    </select>
                </div>
                <div :class="multiBranch ? 'col-md-8' : 'col-12'">
                    <label class="form-label">{{ t('purchases.notes') }}</label>
                    <input v-model="form.notes" class="form-control" />
                </div>
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
                            <div class="col-md-6">
                                <label class="form-label small">{{ t('purchases.batch') }}</label>
                                <select v-model.number="pendingBatchId" class="form-select form-select-sm">
                                    <option v-for="b in pickBatches" :key="b.id" :value="b.id">
                                        {{ formatBatchLabel(b) }} — {{ t('purchases.stock_on_hand') }} {{ b.quantity_on_hand }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3">
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
                                <button type="button" class="btn btn-sm btn-primary w-100" @click="appendLine">
                                    {{ t('purchases.return_add_product') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="h6">{{ t('purchases.return_lines') }}</h2>
            <p v-if="!form.lines.length" class="text-muted small">{{ t('purchases.return_lines_hint') }}</p>
            <div v-for="(line, i) in form.lines" :key="i" class="row g-2 align-items-center border-bottom py-2">
                <div class="col-md-8">
                    <span class="small fw-medium">{{ line.label }}</span>
                    <div class="text-muted small">{{ formatBatchLabel({ batch_no: line.batch_no, expiry_date: line.expiry_date }) }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ t('purchases.qty') }}</label>
                    <input v-model.number="line.quantity" type="number" min="0.01" step="0.01" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="form.lines.splice(i, 1)">×</button>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" :disabled="form.processing || !form.lines.length">{{ t('common.save') }}</button>
                <Link href="/stock-transfers" class="btn btn-link">{{ t('common.cancel') }}</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { batchesWithStock, formatBatchLabel } from '@/composables/usePosBatches';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    branches: { type: Array, default: () => [] },
    multiBranch: { type: Boolean, default: false },
    currentBranchId: { type: Number, default: null },
});

const { t } = useLocale();

const searchQ = ref('');
const searchResults = ref([]);
const picked = ref(null);
const pendingBatchId = ref(null);
const pendingQty = ref(1);
let timer;

const destinationBranches = computed(() =>
    props.branches.filter((b) => b.id !== props.currentBranchId),
);

const form = useForm({
    notes: '',
    to_branch_id: destinationBranches.value[0]?.id ?? null,
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
    searchQ.value = '';
    searchResults.value = [];
}

function appendLine() {
    const batch = pickBatches.value.find((b) => b.id === pendingBatchId.value);
    if (!batch || !picked.value) {
        return;
    }
    form.lines.push({
        from_batch_id: batch.id,
        batch_no: batch.batch_no,
        expiry_date: batch.expiry_date,
        label: picked.value.name,
        quantity: Math.max(0.01, Number(pendingQty.value) || 1),
    });
    picked.value = null;
}

function submit() {
    form.transform((data) => ({
        notes: data.notes,
        to_branch_id: data.to_branch_id,
        lines: data.lines.map((l) => ({
            from_batch_id: l.from_batch_id,
            quantity: l.quantity,
        })),
    })).post('/stock-transfers');
}
</script>
