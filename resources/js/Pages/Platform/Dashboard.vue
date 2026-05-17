<template>
    <PlatformShellLayout :page-title="t('platform.dashboard_title')">
        <Head :title="t('platform.nav_overview')" />
        <header class="platform-hero rounded-3 border bg-body-tertiary px-4 py-4 mb-4">
            <div class="row align-items-center g-3">
                <div class="col-lg">
                    <p class="text-uppercase text-muted small fw-semibold mb-1">Platform admin</p>
                    <h2 class="h4 mb-2 fw-semibold">{{ t('platform.dashboard_title') }}</h2>
                    <p class="text-secondary mb-0 small">{{ t('platform.dashboard_sub') }}</p>
                </div>
                <div class="col-lg-auto d-flex flex-wrap gap-2">
                    <Link href="/platform/tenants/create" class="btn btn-primary">{{ t('platform.add_pharmacy') }}</Link>
                    <Link href="/platform/tenants" class="btn btn-outline-primary">{{ t('platform.manage_pharmacies') }}</Link>
                </div>
            </div>
        </header>
        <section class="mb-4">
            <div class="row g-3">
                <div v-for="card in kpiCards" :key="card.key" class="col-sm-6 col-xl-3">
                    <article class="platform-stat-card h-100 p-4 border-start border-4 rounded-3 bg-body" :class="card.border">
                        <span class="text-muted small fw-semibold text-uppercase">{{ card.label }}</span>
                        <p class="platform-stat-value display-6 fw-semibold mb-0 mt-2">{{ card.value }}</p>
                    </article>
                </div>
            </div>
        </section>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ t('platform.expiring_soon') }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Ends</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in expiringSoon" :key="row.id" class="cursor-pointer" @click="goTenant(row.id)">
                                    <td>{{ row.name }}</td>
                                    <td><TenantStatusBadge :status="row.status" /></td>
                                    <td class="small">{{ row.subscription_ends_at?.slice(0, 10) || row.trial_ends_at?.slice(0, 10) }}</td>
                                </tr>
                                <tr v-if="!expiringSoon.length">
                                    <td colspan="3" class="text-muted small">{{ t('common.no_results') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white fw-semibold">{{ t('platform.recent_pharmacies') }}</div>
                    <ul class="list-group list-group-flush small">
                        <li v-for="row in recentTenants" :key="row.id" class="list-group-item d-flex justify-content-between">
                            <Link :href="`/platform/tenants/${row.id}`" class="text-decoration-none">{{ row.name }}</Link>
                            <TenantStatusBadge :status="row.status" />
                        </li>
                    </ul>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ t('platform.recent_audit') }}</div>
                    <ul class="list-group list-group-flush small">
                        <li v-for="(a, i) in recentAudit" :key="i" class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span>{{ a.description }}</span>
                                <span class="text-muted">{{ a.created_at?.slice(0, 10) }}</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import TenantStatusBadge from '@/Components/TenantStatusBadge.vue';
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tenantCount: Number,
    activeTenantCount: Number,
    suspendedTenantCount: Number,
    inactiveTenantCount: Number,
    trialCount: Number,
    expiredCount: Number,
    expiringSoon: { type: Array, default: () => [] },
    recentTenants: { type: Array, default: () => [] },
    recentAudit: { type: Array, default: () => [] },
});

const { t } = useLocale();

const kpiCards = computed(() => [
    { key: 'total', label: t('platform.kpi_total'), value: props.tenantCount, border: 'border-primary' },
    { key: 'running', label: t('platform.kpi_running'), value: props.activeTenantCount, border: 'border-success' },
    { key: 'trial', label: t('platform.kpi_trial'), value: props.trialCount, border: 'border-info' },
    { key: 'suspended', label: t('platform.kpi_suspended'), value: props.suspendedTenantCount, border: 'border-warning' },
]);

function goTenant(id) {
    router.visit(`/platform/tenants/${id}`);
}
</script>

<style scoped>
.platform-stat-value {
    font-variant-numeric: tabular-nums;
}
.cursor-pointer {
    cursor: pointer;
}
</style>
