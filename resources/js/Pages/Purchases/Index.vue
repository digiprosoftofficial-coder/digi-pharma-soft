<template>
    <TenantShellLayout page-title="Purchases">
        <Head title="Purchases" />
        <div class="purchases-page">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 purchases-page-header">
            <h1 class="h4 mb-0 purchases-page-title">{{ t('purchases.purchase_list') }}</h1>
            <div class="d-flex flex-wrap gap-2 purchases-page-actions">
                <a :href="exportUrl" class="btn btn-sm btn-outline-secondary">{{ t('purchases.export_csv') }}</a>
                <Link
                    v-if="$page.props.auth?.user?.permissions?.includes('purchases.manage')"
                    href="/purchases/create"
                    class="btn btn-primary btn-sm"
                >
                    {{ t('purchases.new_purchase') }}
                </Link>
            </div>
        </div>

        <form class="card border-0 shadow-sm card-body mb-3 purchase-filter-card" @submit.prevent="applyFilters">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3 purchase-filter-field">
                    <label class="form-label small mb-0">{{ t('purchases.search') }}</label>
                    <input
                        v-model="filterForm.q"
                        type="search"
                        class="form-control form-control-sm"
                        :placeholder="t('purchases.search_placeholder')"
                    />
                </div>
                <div class="col-12 col-sm-6 col-md-2 purchase-filter-field">
                    <label class="form-label small mb-0">{{ t('purchases.supplier') }}</label>
                    <select v-model="filterForm.supplier_id" class="form-select form-select-sm">
                        <option value="">{{ t('purchases.all_suppliers') }}</option>
                        <option v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                    </select>
                </div>
                <div class="col-6 col-md-2 purchase-filter-field">
                    <label class="form-label small mb-0">{{ t('purchases.date_from') }}</label>
                    <input v-model="filterForm.date_from" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-6 col-md-2 purchase-filter-field">
                    <label class="form-label small mb-0">{{ t('purchases.date_to') }}</label>
                    <input v-model="filterForm.date_to" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-12 col-md-3 d-grid gap-1 purchase-filter-actions" style="grid-template-columns: repeat(2, minmax(0, 1fr))">
                    <button type="submit" class="btn btn-sm btn-primary">{{ t('purchases.filter') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters">
                        {{ t('purchases.reset') }}
                    </button>
                </div>
            </div>
        </form>

        <div class="purchase-mobile-list d-md-none">
            <div v-if="!purchases.data?.length" class="card border-0 shadow-sm card-body text-muted text-center small">
                {{ t('purchases.no_results') }}
            </div>
            <div v-for="p in purchases.data" :key="p.id" class="card border-0 shadow-sm mb-2 purchase-mobile-card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0 flex-grow-1">
                            <Link :href="`/purchases/${p.id}`" class="fw-semibold text-decoration-none text-truncate d-block">
                                {{ p.invoice_no }}
                            </Link>
                            <div class="small text-muted text-truncate">{{ p.supplier?.name || t('purchases.supplier') }}</div>
                            <div class="small text-muted text-truncate">
                                {{ compactProductNames(p) }}
                            </div>
                            <div class="small text-muted text-truncate">
                                {{ compactLineDates(p) }}
                            </div>
                        </div>
                        <span class="badge text-bg-light border flex-shrink-0 purchase-mobile-card__date">{{ formatDate(p.purchased_at) }}</span>
                    </div>

                    <div class="purchase-mobile-card__amounts">
                        <div>
                            <span class="text-muted">{{ t('purchases.total') }}</span>
                            <strong>{{ formatMoney(p.total) }}</strong>
                        </div>
                        <div>
                            <span class="text-muted">{{ t('purchases.due') }}</span>
                            <strong :class="{ 'text-danger': Number(p.due) > 0 }">{{ formatMoney(p.due) }}</strong>
                        </div>
                    </div>

                    <div class="purchase-mobile-card__actions mt-2">
                        <Link :href="`/purchases/${p.id}`" class="btn btn-sm btn-outline-primary">
                            {{ t('purchases.view') }}
                        </Link>
                        <a
                            :href="`/purchases/${p.id}/print`"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-sm btn-outline-secondary"
                        >
                            {{ t('purchases.print') }}
                        </a>
                    </div>
                </div>
            </div>
            <div v-if="purchases.links?.length > 3" class="card border-0 shadow-sm overflow-hidden mt-2">
                <nav class="purchase-pagination d-flex flex-nowrap gap-1 justify-content-start p-2">
                    <Link
                        v-for="link in purchases.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="btn btn-sm flex-shrink-0"
                        :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                        :disabled="!link.url"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>

        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="table-responsive purchases-table">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('purchases.invoice') }}</th>
                            <th>{{ t('purchases.supplier') }}</th>
                            <th>{{ t('purchases.item') }}</th>
                            <th>{{ t('purchases.date') }}</th>
                            <th class="text-end">{{ t('purchases.total') }} ({{ currencyCode() }})</th>
                            <th class="text-end">{{ t('purchases.due') }} ({{ currencyCode() }})</th>
                            <th class="text-end" style="width: 9rem">{{ t('purchases.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in purchases.data" :key="p.id">
                            <td>
                                <Link :href="`/purchases/${p.id}`" class="fw-medium text-decoration-none">
                                    {{ p.invoice_no }}
                                </Link>
                            </td>
                            <td>{{ p.supplier?.name }}</td>
                            <td class="purchase-items-cell">
                                <span :title="productSummaries(p).join(', ')">{{ compactProductNames(p) }}</span>
                                <small class="text-muted" :title="lineDateSummaries(p).join(', ')">
                                    {{ compactLineDates(p) }}
                                </small>
                            </td>
                            <td>{{ formatDate(p.purchased_at) }}</td>
                            <td class="text-end">{{ formatMoney(p.total) }}</td>
                            <td class="text-end" :class="{ 'text-danger fw-medium': Number(p.due) > 0 }">
                                {{ formatMoney(p.due) }}
                            </td>
                            <td class="text-end text-nowrap">
                                <Link :href="`/purchases/${p.id}`" class="btn btn-sm btn-outline-primary me-1">
                                    {{ t('purchases.view') }}
                                </Link>
                                <a
                                    :href="`/purchases/${p.id}/print`"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn btn-sm btn-outline-secondary"
                                >
                                    {{ t('purchases.print') }}
                                </a>
                            </td>
                        </tr>
                        <tr v-if="!purchases.data?.length">
                            <td colspan="7" class="text-muted text-center py-4">{{ t('purchases.no_results') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="purchases.links?.length > 3" class="card-footer bg-white">
                <nav class="d-flex flex-wrap gap-1 justify-content-center">
                    <Link
                        v-for="link in purchases.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="btn btn-sm"
                        :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                        :disabled="!link.url"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { formatHumanDate as formatDate } from '@/utils/dates';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    purchases: { type: Object, required: true },
    suppliers: { type: Array, default: () => [] },
    filters: {
        type: Object,
        default: () => ({ q: '', supplier_id: '', date_from: '', date_to: '' }),
    },
});

const { t } = useLocale();
const { formatMoney, currencyCode } = useMoney();

function productNames(purchase) {
    return (purchase.lines ?? [])
        .map((line) => line.product?.name)
        .filter(Boolean);
}

function productSummaries(purchase) {
    return (purchase.lines ?? [])
        .map((line) => {
            const name = line.product?.name;
            if (!name) {
                return null;
            }

            const mfg = line.manufactured_at ? `MFG: ${formatDate(line.manufactured_at)}` : null;
            const exp = line.expiry_date ? `EXP: ${formatDate(line.expiry_date)}` : null;
            const dates = [mfg, exp].filter(Boolean).join(', ');

            return dates ? `${name} (${dates})` : name;
        })
        .filter(Boolean);
}

function compactProductNames(purchase) {
    const names = productNames(purchase);
    if (!names.length) {
        return '—';
    }

    const visible = names.slice(0, 2).join(', ');
    const more = names.length - 2;

    return more > 0 ? `${visible} +${more} more` : visible;
}

function lineDateSummaries(purchase) {
    return (purchase.lines ?? [])
        .map((line) => {
            const name = line.product?.name ?? t('purchases.item');
            const mfg = line.manufactured_at ? `MFG ${formatDate(line.manufactured_at)}` : null;
            const exp = line.expiry_date ? `EXP ${formatDate(line.expiry_date)}` : null;
            const dates = [mfg, exp].filter(Boolean).join(' · ');

            return dates ? `${name}: ${dates}` : null;
        })
        .filter(Boolean);
}

function compactLineDates(purchase) {
    const summaries = lineDateSummaries(purchase);
    if (!summaries.length) {
        return 'MFG/EXP: —';
    }

    const visible = summaries.slice(0, 1).join(', ');
    const more = summaries.length - 1;

    return more > 0 ? `${visible} +${more} more` : visible;
}

const filterForm = reactive({
    q: props.filters.q ?? '',
    supplier_id: props.filters.supplier_id ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

function applyFilters() {
    router.get('/purchases', { ...filterForm }, { preserveState: true, replace: true });
}

function clearFilters() {
    filterForm.q = '';
    filterForm.supplier_id = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    router.get('/purchases', {}, { preserveState: true, replace: true });
}

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filterForm.q) params.set('q', filterForm.q);
    if (filterForm.supplier_id) params.set('supplier_id', filterForm.supplier_id);
    if (filterForm.date_from) params.set('date_from', filterForm.date_from);
    if (filterForm.date_to) params.set('date_to', filterForm.date_to);
    const qs = params.toString();
    return qs ? `/purchases/export?${qs}` : '/purchases/export';
});
</script>

<style scoped>
.purchases-page {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: clip;
}

.purchases-page-title {
    min-width: 0;
    max-width: 100%;
}

.purchases-table table {
    min-width: 0;
}

@media (min-width: 768px) {
    .purchases-table table {
        min-width: 900px;
    }
}

.purchase-items-cell {
    max-width: 16rem;
}

.purchase-items-cell span,
.purchase-items-cell small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.purchase-filter-field,
.purchase-filter-actions {
    min-width: 0;
}

.purchase-filter-card :deep(.form-control),
.purchase-filter-card :deep(.form-select) {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.purchase-filter-card :deep(input[type='date']) {
    min-width: 0;
}

.purchase-mobile-list,
.purchase-mobile-card {
    max-width: 100%;
    min-width: 0;
}

.purchase-mobile-card__date {
    max-width: 6.5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.purchase-mobile-card__amounts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

.purchase-mobile-card__amounts > div {
    align-items: flex-start;
    background: #f8f9fa;
    border: 1px solid #eef0f2;
    border-radius: 0.6rem;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    min-width: 0;
    padding: 0.55rem 0.65rem;
}

.purchase-mobile-card__actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

.purchase-pagination {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 767.98px) {
    .purchases-page-header {
        align-items: stretch !important;
    }

    .purchases-page-title {
        width: 100%;
    }

    .purchases-page-actions {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        width: 100%;
    }

    .purchase-filter-card {
        padding: 0.85rem;
    }

    .purchase-filter-field .form-label {
        display: block;
        overflow: hidden;
        font-size: 0.76rem;
        font-weight: 600;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .purchase-filter-card .form-control,
    .purchase-filter-card .form-select {
        font-size: 0.86rem;
        min-height: 2.1rem;
        padding: 0.35rem 0.5rem;
    }

    .purchase-mobile-card .card-body {
        padding: 0.85rem !important;
    }

    .purchase-mobile-card__amounts > div {
        padding: 0.45rem 0.55rem;
    }

    .purchase-mobile-card__actions .btn,
    .purchases-page-actions .btn {
        width: 100%;
        font-size: 0.8rem;
        min-height: 2.15rem;
    }
}
</style>
