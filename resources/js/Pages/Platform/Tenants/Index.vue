<template>
    <PlatformShellLayout :page-title="t('platform.pharmacy_directory')">
        <Head :title="t('platform.nav_pharmacies')" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="text-muted small mb-0">{{ t('platform.dashboard_sub') }}</p>
            <Link href="/platform/tenants/create" class="btn btn-primary btn-sm">{{ t('platform.add_pharmacy') }}</Link>
        </div>
        <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="applyFilters">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small mb-0">{{ t('common.search') }}</label>
                    <input v-model="filters.q" type="search" class="form-control form-control-sm" />
                </div>
                <div class="col-md-4">
                    <label class="form-label small mb-0">Status</label>
                    <select v-model="filters.status" class="form-select form-select-sm">
                        <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">{{ t('common.search') }}</button>
                </div>
            </div>
        </form>
        <div v-if="!tenants.data.length" class="card border-0 shadow-sm card-body text-center py-5">
            <p class="text-muted mb-3">{{ t('platform.empty_pharmacies') }}</p>
            <Link href="/platform/tenants/create" class="btn btn-primary">{{ t('platform.add_pharmacy') }}</Link>
        </div>
        <div v-else class="card border-0 shadow-sm table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Plan</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in tenants.data" :key="row.id" class="cursor-pointer" @click="goShow(row.id)">
                        <td class="fw-semibold">{{ row.name }}</td>
                        <td><code>{{ row.slug }}</code></td>
                        <td>{{ row.plan_name || '—' }}</td>
                        <td>{{ row.users_count }}</td>
                        <td><TenantStatusBadge :status="row.status" /></td>
                        <td class="text-end" @click.stop>
                            <Link :href="`/platform/tenants/${row.id}`" class="btn btn-sm btn-outline-secondary">View</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import TenantStatusBadge from '@/Components/TenantStatusBadge.vue';
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    tenants: { type: Object, required: true },
    filters: { type: Object, required: true },
    statusOptions: { type: Array, required: true },
});

const { t } = useLocale();

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? 'all',
});

function applyFilters() {
    router.get('/platform/tenants', { q: filters.q || undefined, status: filters.status === 'all' ? undefined : filters.status }, {
        preserveState: true,
    });
}

function goShow(id) {
    router.visit(`/platform/tenants/${id}`);
}
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>
