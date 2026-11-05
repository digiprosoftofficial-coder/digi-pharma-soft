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
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('platform.pharmacy_name') }}</th>
                        <th>{{ t('platform.owner_name') }}</th>
                        <th>{{ t('platform.mobile') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th>{{ t('platform.scheduled_at') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="d in demos.data" :key="d.id">
                        <td>{{ d.pharmacy_name }}</td>
                        <td>{{ d.owner_name }}</td>
                        <td>{{ d.mobile }}</td>
                        <td>{{ d.status }}</td>
                        <td>{{ d.scheduled_at ? new Date(d.scheduled_at).toLocaleString() : '—' }}</td>
                        <td class="text-end"><Link :href="`/platform/demos/${d.id}`" class="btn btn-sm btn-outline-primary">View</Link></td>
                    </tr>
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
    demos: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    statuses: { type: Array, required: true },
    salesStaff: { type: Array, default: () => [] },
});

const { t } = useLocale();
const local = reactive({ search: props.filters.search || '', status: props.filters.status || '' });
function filter() { router.get('/platform/demos', { ...local }, { preserveState: true }); }
</script>
