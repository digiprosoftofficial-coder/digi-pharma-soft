<template>
    <PlatformShellLayout :page-title="t('platform.leads_title')">
        <Head :title="t('platform.nav_leads')" />
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge text-bg-secondary">{{ t('platform.total') }}: {{ stats.total }}</span>
                <span class="badge text-bg-primary">{{ t('platform.status_new') }}: {{ stats.new }}</span>
                <span class="badge text-bg-success">{{ t('platform.status_converted') }}: {{ stats.converted }}</span>
                <span class="badge text-bg-warning">{{ t('platform.follow_ups_due') }}: {{ stats.follow_ups_due }}</span>
            </div>
            <Link href="/platform/leads/create" class="btn btn-primary btn-sm">{{ t('platform.new_lead') }}</Link>
        </div>
        <form class="row g-2 mb-3" @submit.prevent="filter">
            <div class="col-md-3"><input v-model="local.search" class="form-control form-control-sm" :placeholder="t('common.search')" /></div>
            <div class="col-md-2">
                <select v-model="local.status" class="form-select form-select-sm">
                    <option value="">{{ t('platform.all_statuses') }}</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <select v-model="local.source" class="form-select form-select-sm">
                    <option value="">{{ t('platform.all_sources') }}</option>
                    <option v-for="s in sources" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">{{ t('common.filter') || 'Filter' }}</button></div>
        </form>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('platform.pharmacy_name') }}</th>
                        <th>{{ t('platform.owner_name') }}</th>
                        <th>{{ t('platform.mobile') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th>{{ t('platform.assigned_to') }}</th>
                        <th>{{ t('platform.follow_up_at') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lead in leads.data" :key="lead.id">
                        <td>{{ lead.pharmacy_name }}</td>
                        <td>{{ lead.owner_name }}</td>
                        <td>{{ lead.phone }}</td>
                        <td><span class="badge text-bg-light">{{ lead.status }}</span></td>
                        <td>{{ lead.assignee?.name || '—' }}</td>
                        <td>{{ lead.follow_up_at ? new Date(lead.follow_up_at).toLocaleString() : '—' }}</td>
                        <td class="text-end"><Link :href="`/platform/leads/${lead.id}`" class="btn btn-sm btn-outline-primary">{{ t('common.view') || 'View' }}</Link></td>
                    </tr>
                    <tr v-if="!leads.data?.length"><td colspan="7" class="text-center text-muted py-4">{{ t('common.no_results') }}</td></tr>
                </tbody>
            </table>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    leads: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    statuses: { type: Array, required: true },
    sources: { type: Array, required: true },
    salesStaff: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
});

const { t } = useLocale();
const local = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    source: props.filters.source || '',
});

function filter() {
    router.get('/platform/leads', { ...local }, { preserveState: true });
}
</script>
