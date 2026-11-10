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
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('platform.pharmacy_name') }}</th>
                        <th>{{ t('platform.owner_name') }}</th>
                        <th>{{ t('platform.mobile') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th>{{ t('platform.assigned_to') }}</th>
                        <th>{{ t('platform.requested_at') }}</th>
                        <th>{{ t('platform.follow_up_at') }}</th>
                        <th class="text-end">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lead in leads.data" :key="lead.id">
                        <td>
                            <Link :href="`/platform/leads/${lead.id}`" class="text-decoration-none">{{ lead.pharmacy_name }}</Link>
                        </td>
                        <td>{{ lead.owner_name }}</td>
                        <td>{{ lead.phone }}</td>
                        <td><span class="badge text-bg-light">{{ lead.status }}</span></td>
                        <td>{{ lead.assignee?.name || '—' }}</td>
                        <td>{{ formatWhen(lead.created_at) }}</td>
                        <td>{{ lead.follow_up_at ? formatWhen(lead.follow_up_at) : '—' }}</td>
                        <td class="text-end text-nowrap">
                            <Link
                                :href="`/platform/leads/${lead.id}/edit`"
                                class="btn btn-sm btn-outline-primary me-1"
                                :title="t('common.edit')"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg>
                                <span class="visually-hidden">{{ t('common.edit') }}</span>
                            </Link>
                            <button
                                v-if="canDelete"
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :title="t('common.delete')"
                                @click="destroyLead(lead)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4h8v2" />
                                    <path d="M19 6l-1 14H6L5 6" />
                                </svg>
                                <span class="visually-hidden">{{ t('common.delete') }}</span>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!leads.data?.length"><td colspan="8" class="text-center text-muted py-4">{{ t('common.no_results') }}</td></tr>
                </tbody>
            </table>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

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
const page = usePage();
const canDelete = computed(() => {
    const user = page.props.auth?.user;
    return Boolean(user?.is_platform_super_admin || (user?.roles || []).includes('super admin'));
});
const local = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    source: props.filters.source || '',
});

function filter() {
    router.get('/platform/leads', { ...local }, { preserveState: true });
}

function formatWhen(value) {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString();
}

function destroyLead(lead) {
    if (!confirm(t('platform.lead_delete_confirm', { name: lead.pharmacy_name }))) {
        return;
    }
    router.delete(`/platform/leads/${lead.id}`);
}
</script>
