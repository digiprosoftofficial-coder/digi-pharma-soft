<template>
    <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="applyFilters">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">{{ t('purchases.date_from') }}</label>
                <input v-model="filterForm.date_from" type="date" class="form-control form-control-sm" />
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">{{ t('purchases.date_to') }}</label>
                <input v-model="filterForm.date_to" type="date" class="form-control form-control-sm" />
            </div>
            <div v-if="canViewAllBranches" class="col-md-3">
                <label class="form-label small mb-0">{{ t('reports.branch_scope') }}</label>
                <select v-model="filterForm.branch_id" class="form-select form-select-sm">
                    <option value="all">{{ t('purchases.all_branches') }}</option>
                    <option v-for="branch in branches" :key="branch.id" :value="String(branch.id)">
                        {{ branchLabelText(branch) }}
                    </option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">{{ t('reports.apply') }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="resetFilters">{{ t('purchases.reset') }}</button>
            </div>
            <div class="col-md text-md-end">
                <div class="btn-group btn-group-sm">
                    <a :href="outputUrl('print')" target="_blank" rel="noopener" class="btn btn-outline-secondary">{{ t('sales.print') }}</a>
                    <a :href="outputUrl('pdf')" class="btn btn-outline-secondary">PDF</a>
                    <a :href="outputUrl('xlsx')" class="btn btn-outline-secondary">Excel</a>
                    <a :href="outputUrl('csv')" class="btn btn-outline-secondary">CSV</a>
                </div>
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2">
            {{ t('reports.current_scope') }}: <strong>{{ branchLabel }}</strong>
        </p>
    </form>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { useLocale } from '@/composables/useLocale';
import { reactive } from 'vue';

const props = defineProps({
    filters: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    branchLabel: { type: String, required: true },
    canViewAllBranches: { type: Boolean, default: false },
    reportPath: { type: String, required: true },
    exportPath: { type: String, required: true },
});

const { t } = useLocale();

const filterForm = reactive({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    branch_id: props.filters.branch_id ?? 'all',
});

function cleanParams(extra = {}) {
    const params = { ...filterForm, ...extra };

    return Object.fromEntries(Object.entries(params).filter(([, value]) => value !== null && value !== ''));
}

function applyFilters() {
    router.get(props.reportPath, cleanParams(), { preserveState: true, replace: true });
}

function resetFilters() {
    filterForm.date_from = '';
    filterForm.date_to = '';
    filterForm.branch_id = props.canViewAllBranches ? 'all' : '';
    router.get(props.reportPath, {}, { preserveState: true, replace: true });
}

function outputUrl(format) {
    const params = new URLSearchParams(cleanParams({ format }));

    return `${props.exportPath}?${params.toString()}`;
}

function branchLabelText(branch) {
    return branch.code ? `${branch.name} (${branch.code})` : branch.name;
}
</script>
