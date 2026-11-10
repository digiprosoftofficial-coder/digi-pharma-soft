<template>
    <PlatformShellLayout :page-title="t('platform.demos_title')">
        <Head :title="t('platform.nav_demos')" />
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <span class="badge text-bg-secondary">{{ stats.total }}</span>
            <span class="badge text-bg-warning">pending: {{ stats.pending }}</span>
            <span class="badge text-bg-primary">scheduled: {{ stats.scheduled }}</span>
            <span class="badge text-bg-success">completed: {{ stats.completed }}</span>
        </div>
        <form class="row g-2 mb-3" @submit.prevent="filter">
            <div class="col-md-4"><input v-model="local.search" class="form-control form-control-sm" :placeholder="t('common.search')" /></div>
            <div class="col-md-3">
                <select v-model="local.status" class="form-select form-select-sm">
                    <option value="">{{ t('platform.all_statuses') }}</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Filter</button></div>
        </form>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('platform.pharmacy_name') }}</th>
                        <th>{{ t('platform.owner_name') }}</th>
                        <th>{{ t('platform.mobile') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th>{{ t('platform.requested_at') }}</th>
                        <th>{{ t('platform.scheduled_at') }}</th>
                        <th class="text-end">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="d in demos.data" :key="d.id">
                        <td>{{ d.pharmacy_name }}</td>
                        <td>{{ d.owner_name }}</td>
                        <td>{{ d.mobile }}</td>
                        <td>{{ d.status }}</td>
                        <td>{{ formatWhen(d.created_at) }}</td>
                        <td>{{ d.scheduled_at ? formatWhen(d.scheduled_at) : '—' }}</td>
                        <td class="text-end text-nowrap">
                            <Link
                                :href="`/platform/demos/${d.id}`"
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
                                @click="destroyDemo(d)"
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
                    <tr v-if="!demos.data?.length">
                        <td colspan="7" class="text-center text-muted py-4">{{ t('common.no_results') }}</td>
                    </tr>
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
    demos: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    statuses: { type: Array, required: true },
    salesStaff: { type: Array, default: () => [] },
});

const { t } = useLocale();
const page = usePage();
const canDelete = computed(() => {
    const user = page.props.auth?.user;
    return Boolean(user?.is_platform_super_admin || (user?.roles || []).includes('super admin'));
});
const local = reactive({ search: props.filters.search || '', status: props.filters.status || '' });

function filter() {
    router.get('/platform/demos', { ...local }, { preserveState: true });
}

function formatWhen(value) {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString();
}

function destroyDemo(demo) {
    if (!confirm(t('platform.demo_delete_confirm', { name: demo.pharmacy_name }))) {
        return;
    }
    router.delete(`/platform/demos/${demo.id}`);
}
</script>
