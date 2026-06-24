<template>
    <TenantShellLayout :page-title="t('reports.title')">
        <Head :title="t('reports.title')" />
        <div class="report-hero card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <span class="badge text-bg-primary-subtle text-primary mb-3">{{ t('reports.hero_badge') }}</span>
                        <h1 class="display-6 fw-semibold mb-2">{{ t('reports.hero_title') }}</h1>
                        <p class="lead text-muted mb-0">
                            {{ t('reports.hero_description') }}
                        </p>
                        <p class="small text-muted mb-0 mt-3">
                            <span
                                v-html="t('reports.showing_range', {
                                    from: snapshot.range.dateFrom,
                                    to: snapshot.range.dateTo,
                                    branch: `<strong>${snapshot.range.branchLabel}</strong>`,
                                })"
                            ></span>
                        </p>
                    </div>
                    <div class="col-lg-5">
                        <div class="row g-2">
                            <div v-for="stat in stats" :key="stat.label" class="col-6">
                                <div class="report-stat rounded-3 p-3 h-100">
                                    <div class="h4 mb-0">{{ stat.value }}</div>
                                    <div class="small text-muted">{{ stat.label }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">{{ t('reports.open_report_section_title') }}</h2>
                <p class="small text-muted mb-0">{{ t('reports.open_report_section_help') }}</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div v-for="quick in quickLinks" :key="quick.titleKey" class="col-md-6 col-xl-3">
                <Link :href="quick.href" class="quick-card card border-0 shadow-sm h-100 text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="quick-icon rounded-3">{{ quick.short }}</span>
                            <span class="small text-primary fw-medium">{{ t('reports.open') }}</span>
                        </div>
                        <h2 class="h6 text-body mb-1">{{ t(quick.titleKey) }}</h2>
                        <p class="small text-muted mb-0">{{ t(quick.helpKey) }}</p>
                    </div>
                </Link>
            </div>
        </div>

        <section class="mb-4">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">{{ t('reports.business_snapshot') }}</h2>
                    <p class="small text-muted mb-0">{{ t('reports.business_snapshot_help') }}</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-xl-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <h3 class="h6 mb-1">{{ t('reports.sales_vs_purchases') }}</h3>
                                    <p class="small text-muted mb-0">{{ t('reports.sales_vs_purchases_help') }}</p>
                                </div>
                                <span class="badge text-bg-light">{{ t('reports.bar') }}</span>
                            </div>
                            <div class="d-flex flex-column gap-3">
                                <div v-for="bar in barRows" :key="bar.label">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ bar.label }}</span>
                                        <strong>{{ formatMoney(bar.value) }}</strong>
                                    </div>
                                    <div class="snapshot-bar">
                                        <div class="snapshot-bar-fill" :style="{ width: `${bar.percent}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <div>
                                    <h3 class="h6 mb-1">{{ t('reports.receivable_vs_payable') }}</h3>
                                    <p class="small text-muted mb-0">{{ t('reports.receivable_vs_payable_help') }}</p>
                                </div>
                                <span class="badge text-bg-light">{{ t('reports.donut') }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="donut-chart" :style="donutStyle">
                                    <div class="donut-hole"></div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between small mb-2">
                                        <span><span class="legend-dot bg-primary"></span>{{ t('reports.customer_due') }}</span>
                                        <strong>{{ formatMoney(snapshot.dues.customerDue) }}</strong>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between small">
                                        <span><span class="legend-dot bg-warning"></span>{{ t('reports.supplier_due') }}</span>
                                        <strong>{{ formatMoney(snapshot.dues.supplierDue) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h6 mb-1">{{ t('reports.inventory_alerts') }}</h3>
                            <p class="small text-muted mb-3">{{ t('reports.inventory_alerts_help') }}</p>
                            <div class="d-flex flex-column gap-2">
                                <div v-for="alert in inventoryAlerts" :key="alert.label" class="alert-row rounded-3 p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="small">{{ alert.label }}</span>
                                        <strong>{{ alert.value }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">{{ t('reports.coming_soon') }}</h2>
                    <p class="small text-muted mb-0">{{ t('reports.coming_soon_help') }}</p>
                </div>
            </div>
            <div class="row g-3">
                <div v-for="report in roadmapReports" :key="report.titleKey" class="col-md-6 col-xl-3">
                    <div class="report-card report-card--muted card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div class="report-icon rounded-3">{{ report.short }}</div>
                                <span class="badge text-bg-light">Roadmap</span>
                            </div>
                            <h3 class="h6 mb-2">{{ t(report.titleKey) }}</h3>
                            <p class="small text-muted mb-0">{{ t(report.helpKey) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    snapshot: { type: Object, required: true },
});

const { formatMoney } = useMoney();
const { t } = useLocale();

const quickLinks = [
    {
        titleKey: 'reports.quick_sales_title',
        short: 'S',
        href: '/reports/sales/summary',
        helpKey: 'reports.quick_sales_help',
    },
    {
        titleKey: 'reports.quick_purchase_title',
        short: 'P',
        href: '/reports/purchases/summary',
        helpKey: 'reports.quick_purchase_help',
    },
    {
        titleKey: 'reports.quick_inventory_title',
        short: 'I',
        href: '/reports/inventory/health',
        helpKey: 'reports.quick_inventory_help',
    },
    {
        titleKey: 'reports.quick_dues_title',
        short: 'D',
        href: '/reports/dues',
        helpKey: 'reports.quick_dues_help',
    },
];

const roadmapReports = [
    {
        titleKey: 'reports.roadmap_sales_returns_title',
        short: 'R',
        helpKey: 'reports.roadmap_sales_returns_help',
    },
    {
        titleKey: 'reports.roadmap_supplier_performance_title',
        short: 'SP',
        helpKey: 'reports.roadmap_supplier_performance_help',
    },
    {
        titleKey: 'reports.roadmap_stock_movement_title',
        short: 'M',
        helpKey: 'reports.roadmap_stock_movement_help',
    },
    {
        titleKey: 'reports.roadmap_cashflow_title',
        short: 'F',
        helpKey: 'reports.roadmap_cashflow_help',
    },
];

const stats = computed(() => {
    return [
        { label: t('reports.ready_reports'), value: quickLinks.length },
        { label: t('reports.roadmap_reports'), value: roadmapReports.length },
        { label: t('reports.output_formats'), value: 4 },
        { label: t('reports.scopes'), value: 2 },
    ];
});

const barRows = computed(() => {
    const rows = [
        { label: t('reports.net_sales'), value: Number(props.snapshot.sales.netSales || 0) },
        { label: t('reports.purchases'), value: Number(props.snapshot.purchases.purchaseTotal || 0) },
        { label: t('reports.sales_due'), value: Number(props.snapshot.sales.due || 0) },
    ];
    const max = Math.max(...rows.map((row) => row.value), 1);

    return rows.map((row) => ({
        ...row,
        percent: Math.max(4, Math.round((row.value / max) * 100)),
    }));
});

const donutStyle = computed(() => {
    const customerDue = Number(props.snapshot.dues.customerDue || 0);
    const supplierDue = Number(props.snapshot.dues.supplierDue || 0);
    const total = customerDue + supplierDue;
    const customerPercent = total > 0 ? Math.round((customerDue / total) * 100) : 50;

    return {
        background: `conic-gradient(var(--bs-primary) 0 ${customerPercent}%, var(--bs-warning) ${customerPercent}% 100%)`,
    };
});

const inventoryAlerts = computed(() => [
    { label: t('reports.low_stock'), value: props.snapshot.inventory.lowStockCount ?? 0 },
    { label: t('reports.expired'), value: props.snapshot.inventory.expiredCount ?? 0 },
    { label: t('reports.expiring_soon'), value: props.snapshot.inventory.expiringSoonCount ?? 0 },
]);
</script>

<style scoped>
.report-hero {
    background:
        radial-gradient(circle at top right, rgba(var(--bs-primary-rgb), 0.14), transparent 34%),
        linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
}

.report-stat {
    background: rgba(255, 255, 255, 0.72);
    border: 1px solid rgba(var(--bs-primary-rgb), 0.08);
}

.quick-card,
.report-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.quick-card:hover,
.report-card:not(.report-card--muted):hover {
    transform: translateY(-2px);
    box-shadow: 0 0.75rem 1.5rem rgba(15, 23, 42, 0.08) !important;
}

.quick-icon,
.report-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.4rem;
    height: 2.4rem;
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.1);
    font-weight: 700;
}

.report-card--muted {
    opacity: 0.78;
}

.snapshot-bar {
    height: 0.65rem;
    overflow: hidden;
    background: #eef2f7;
    border-radius: 999px;
}

.snapshot-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--bs-primary), rgba(var(--bs-primary-rgb), 0.55));
    border-radius: inherit;
}

.donut-chart {
    position: relative;
    flex: 0 0 auto;
    width: 8rem;
    height: 8rem;
    border-radius: 50%;
}

.donut-hole {
    position: absolute;
    inset: 1.55rem;
    background: #fff;
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
}

.legend-dot {
    display: inline-block;
    width: 0.65rem;
    height: 0.65rem;
    margin-right: 0.4rem;
    border-radius: 50%;
}

.alert-row {
    background: #f8fafc;
    border: 1px solid #eef2f7;
}
</style>
