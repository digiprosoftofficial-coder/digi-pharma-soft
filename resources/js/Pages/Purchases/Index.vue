<template>
    <TenantShellLayout page-title="Purchases">
        <Head title="Purchases" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">{{ t('purchases.purchase_list') }}</h1>
            <div class="d-flex gap-2">
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

        <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="applyFilters">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">{{ t('purchases.search') }}</label>
                    <input
                        v-model="filterForm.q"
                        type="search"
                        class="form-control form-control-sm"
                        :placeholder="t('purchases.search_placeholder')"
                    />
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">{{ t('purchases.supplier') }}</label>
                    <select v-model="filterForm.supplier_id" class="form-select form-select-sm">
                        <option value="">{{ t('purchases.all_suppliers') }}</option>
                        <option v-for="s in suppliers" :key="s.id" :value="String(s.id)">{{ s.name }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">{{ t('purchases.date_from') }}</label>
                    <input v-model="filterForm.date_from" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">{{ t('purchases.date_to') }}</label>
                    <input v-model="filterForm.date_to" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-3 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">{{ t('purchases.filter') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters">
                        {{ t('purchases.reset') }}
                    </button>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('purchases.invoice') }}</th>
                            <th>{{ t('purchases.supplier') }}</th>
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
                            <td>{{ p.purchased_at }}</td>
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
                            <td colspan="6" class="text-muted text-center py-4">{{ t('purchases.no_results') }}</td>
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
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
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
