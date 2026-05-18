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

        <section v-if="health?.status === 'degraded'" class="mb-4">
            <div class="alert alert-danger d-flex flex-wrap align-items-center gap-2 mb-0">
                <span>{{ t('platform.health_alert_failed', { count: health.failed_jobs }) }}</span>
                <Link href="/platform/health" class="btn btn-sm btn-outline-danger ms-auto">{{ t('platform.health_view') }}</Link>
            </div>
        </section>

        <section class="mb-4">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <h3 class="h6 text-uppercase text-muted fw-semibold mb-0">{{ t('platform.billing_title') }}</h3>
                <Link href="/platform/billing" class="small">{{ t('platform.billing_view') }}</Link>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <article class="platform-stat-card h-100 p-3 border rounded-3 bg-body">
                        <span class="text-muted small fw-semibold text-uppercase">{{ t('platform.billing_mrr') }}</span>
                        <p class="h5 fw-semibold mb-0 mt-1">{{ formatBillingMoney(billing.mrr_cents) }}</p>
                    </article>
                </div>
                <div class="col-sm-4">
                    <article class="platform-stat-card h-100 p-3 border rounded-3 bg-body">
                        <span class="text-muted small fw-semibold text-uppercase">{{ t('platform.billing_past_due') }}</span>
                        <p class="h5 fw-semibold mb-0 mt-1" :class="{ 'text-warning': billing.past_due_tenants > 0 }">
                            {{ billing.past_due_tenants }}
                        </p>
                    </article>
                </div>
                <div class="col-sm-4">
                    <article class="platform-stat-card h-100 p-3 border rounded-3 bg-body">
                        <span class="text-muted small fw-semibold text-uppercase">{{ t('platform.billing_open_invoices') }}</span>
                        <p class="h5 fw-semibold mb-0 mt-1">{{ billing.open_invoices }}</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="mb-4">
            <h3 class="h6 text-uppercase text-muted fw-semibold mb-3">{{ t('platform.analytics_title') }}</h3>
            <div class="row g-3">
                <div v-for="card in revenueCards" :key="card.key" class="col-sm-6 col-lg-4">
                    <article class="platform-stat-card h-100 p-4 border rounded-3 bg-body">
                        <span class="text-muted small fw-semibold text-uppercase">{{ card.label }}</span>
                        <p class="platform-stat-value h3 fw-semibold mb-0 mt-2">{{ formatMoney(card.value) }}</p>
                        <p v-if="card.hint" class="small text-muted mb-0 mt-1">{{ card.hint }}</p>
                    </article>
                </div>
            </div>
        </section>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">{{ t('platform.analytics_adoption') }}</div>
                    <ul class="list-group list-group-flush small">
                        <li v-for="row in adoptionRows" :key="row.key" class="list-group-item">
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ row.label }}</span>
                                <span class="text-muted">{{ row.count }} / {{ analytics.onboarded_tenants }}</span>
                            </div>
                            <div class="progress" style="height: 6px">
                                <div class="progress-bar" role="progressbar" :style="{ width: row.percent + '%' }"></div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">{{ t('platform.health_title') }}</span>
                        <Link href="/platform/health" class="small">{{ t('platform.health_view') }}</Link>
                    </div>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ t('platform.health_status') }}</span>
                            <span class="badge" :class="healthBadgeClass">{{ healthStatusLabel }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ t('platform.health_failed_jobs') }}</span>
                            <span>{{ health.failed_jobs }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ t('platform.health_pending_jobs') }}</span>
                            <span>{{ health.pending_jobs }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ t('platform.health_queue') }}</span>
                            <code>{{ health.queue_connection }}</code>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <section class="mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">{{ t('platform.analytics_top_tenants') }}</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ t('platform.analytics_pharmacy') }}</th>
                                <th class="text-end">{{ t('platform.analytics_revenue_30d') }}</th>
                                <th class="text-end">{{ t('platform.analytics_sales_count') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in analytics.top_tenants_30d"
                                :key="row.tenant_id"
                                class="cursor-pointer"
                                @click="goTenant(row.tenant_id)"
                            >
                                <td>{{ row.name }}</td>
                                <td class="text-end">{{ formatMoney(row.revenue) }}</td>
                                <td class="text-end">{{ row.sales_count }}</td>
                            </tr>
                            <tr v-if="!analytics.top_tenants_30d?.length">
                                <td colspan="3" class="text-muted small">{{ t('platform.analytics_no_sales') }}</td>
                            </tr>
                        </tbody>
                    </table>
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
import { useMoney } from '@/composables/useMoney';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tenantCount: Number,
    activeTenantCount: Number,
    suspendedTenantCount: Number,
    inactiveTenantCount: Number,
    trialCount: Number,
    expiredCount: Number,
    analytics: { type: Object, required: true },
    billing: { type: Object, required: true },
    health: { type: Object, required: true },
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

const revenueCards = computed(() => [
    {
        key: 'month',
        label: t('platform.analytics_revenue_month'),
        value: props.analytics.revenue_this_month,
        hint: t('platform.analytics_sales_month', { count: props.analytics.sales_count_this_month }),
    },
    {
        key: 'all',
        label: t('platform.analytics_revenue_all'),
        value: props.analytics.revenue_all_time,
    },
    {
        key: 'active',
        label: t('platform.analytics_active_sellers'),
        value: props.analytics.active_selling_tenants,
        hint: t('platform.analytics_active_sellers_hint'),
    },
]);

const adoptionRows = computed(() => {
    const m = props.analytics.module_adoption;
    return [
        { key: 'products', label: t('platform.analytics_module_products'), count: m.products.count, percent: m.products.percent },
        { key: 'sales', label: t('platform.analytics_module_sales'), count: m.sales.count, percent: m.sales.percent },
        { key: 'purchases', label: t('platform.analytics_module_purchases'), count: m.purchases.count, percent: m.purchases.percent },
    ];
});

const healthStatusLabel = computed(() => {
    const map = {
        healthy: t('platform.health_status_healthy'),
        warning: t('platform.health_status_warning'),
        degraded: t('platform.health_status_degraded'),
    };
    return map[props.health.status] ?? props.health.status;
});

const healthBadgeClass = computed(() => {
    const map = {
        healthy: 'text-bg-success',
        warning: 'text-bg-warning',
        degraded: 'text-bg-danger',
    };
    return map[props.health.status] ?? 'text-bg-secondary';
});

const { formatMoney, formatCents: formatBillingMoney } = useMoney({
    currency: props.billing?.currency,
});

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
