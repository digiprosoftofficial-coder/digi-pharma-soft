<template>
    <TenantShellLayout page-title="Reports">
        <Head title="Reports" />
        <div class="report-hero card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <span class="badge text-bg-primary-subtle text-primary mb-3">Report Hub</span>
                        <h1 class="display-6 fw-semibold mb-2">Understand your pharmacy faster</h1>
                        <p class="lead text-muted mb-0">
                            Sales, purchases, stock health, expiry risk, and dues are grouped in one place with branch-wise and tenant-wide views.
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

        <div class="row g-3 mb-4">
            <div v-for="quick in quickLinks" :key="quick.title" class="col-md-6 col-xl-3">
                <Link :href="quick.href" class="quick-card card border-0 shadow-sm h-100 text-decoration-none">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="quick-icon rounded-3">{{ quick.short }}</span>
                            <span class="small text-primary fw-medium">Open</span>
                        </div>
                        <h2 class="h6 text-body mb-1">{{ quick.title }}</h2>
                        <p class="small text-muted mb-0">{{ quick.help }}</p>
                    </div>
                </Link>
            </div>
        </div>

        <section v-for="section in sections" :key="section.title" class="mb-4">
            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1">{{ section.title }}</h2>
                    <p class="small text-muted mb-0">{{ section.description }}</p>
                </div>
                <span class="badge text-bg-light">{{ section.reports.length }} reports</span>
            </div>
            <div class="row g-3">
                <div v-for="report in section.reports" :key="report.title" class="col-md-6 col-xl-3">
                    <div class="report-card card border-0 shadow-sm h-100" :class="{ 'report-card--muted': !report.ready }">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                <div class="report-icon rounded-3">{{ report.short }}</div>
                                <span class="badge" :class="report.ready ? 'text-bg-success-subtle text-success' : 'text-bg-light'">
                                    {{ report.ready ? 'Ready' : 'Roadmap' }}
                                </span>
                            </div>
                            <h3 class="h6 mb-2">{{ report.title }}</h3>
                            <p class="small text-muted flex-grow-1 mb-3">{{ report.description }}</p>
                            <div class="mb-3">
                                <div class="small fw-semibold mb-2">Includes</div>
                                <div class="d-flex flex-wrap gap-1">
                                    <span v-for="metric in report.metrics" :key="metric" class="badge rounded-pill text-bg-light">{{ metric }}</span>
                                </div>
                            </div>
                            <Link
                                v-if="report.ready"
                                :href="report.href"
                                class="btn btn-sm btn-primary w-100"
                            >
                                Open report
                            </Link>
                            <button v-else type="button" class="btn btn-sm btn-outline-secondary w-100" disabled>
                                Planned
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const quickLinks = [
    {
        title: 'Sales summary',
        short: 'S',
        href: '/reports/sales/summary',
        help: 'Daily sales, paid, due, returns, and top products.',
    },
    {
        title: 'Purchase summary',
        short: 'P',
        href: '/reports/purchases/summary',
        help: 'Supplier purchase, payment, due, and return credit.',
    },
    {
        title: 'Inventory health',
        short: 'I',
        href: '/reports/inventory/health',
        help: 'Stock value, low stock, expired, and expiring soon.',
    },
    {
        title: 'Dues',
        short: 'D',
        href: '/reports/dues',
        help: 'Customer receivable and supplier payable in one view.',
    },
];

const sections = [
    {
        title: 'Sales reports',
        description: 'Daily revenue, returns, dues, and top products',
        reports: [
            {
                title: 'Sales summary',
                short: 'S',
                href: '/reports/sales/summary',
                ready: true,
                description: 'Gross sales, paid, due, returns, net sales, invoice list, and top products.',
                metrics: ['Tenant-wide', 'Branch view', 'Print/PDF/Excel/CSV'],
            },
            {
                title: 'Sales returns',
                short: 'R',
                ready: false,
                description: 'Return trends, refund totals, returned product quantities, and invoice drill-downs.',
                metrics: ['Returns', 'Refunds'],
            },
        ],
    },
    {
        title: 'Purchase reports',
        description: 'Supplier purchases and branch-scoped payable flow',
        reports: [
            {
                title: 'Purchase summary',
                short: 'P',
                href: '/reports/purchases/summary',
                ready: true,
                description: 'Purchase totals, paid, due, return credits, purchase list, and top suppliers.',
                metrics: ['Supplier-wide', 'Branch scoped', 'Exports'],
            },
            {
                title: 'Supplier performance',
                short: 'SP',
                ready: false,
                description: 'Supplier-wise purchase trend, return rate, and payment behavior.',
                metrics: ['Supplier', 'Trend'],
            },
        ],
    },
    {
        title: 'Inventory and expiry',
        description: 'Branch stock, valuation, low stock, and expiry risk',
        reports: [
            {
                title: 'Inventory health',
                short: 'I',
                href: '/reports/inventory/health',
                ready: true,
                description: 'Current batch stock, stock value, low stock, expired stock, and expiring soon products.',
                metrics: ['Stock value', 'Expiry', 'Low stock'],
            },
            {
                title: 'Stock movement audit',
                short: 'M',
                ready: false,
                description: 'Branch/location movement history by product, batch, and reference.',
                metrics: ['Audit', 'Movement'],
            },
        ],
    },
    {
        title: 'Due and finance reports',
        description: 'Receivables, payables, ledger, and cashflow',
        reports: [
            {
                title: 'Customer and supplier dues',
                short: 'D',
                href: '/reports/dues',
                ready: true,
                description: 'Customer receivables and supplier payables with tenant-wide or selected branch scope.',
                metrics: ['Receivable', 'Payable', 'Branch scope'],
            },
            {
                title: 'Cashflow and ledger',
                short: 'F',
                ready: false,
                description: 'Ledger balances, cash in/out, and profit estimate for management review.',
                metrics: ['Finance', 'Ledger'],
            },
        ],
    },
];

const stats = computed(() => {
    const reports = sections.flatMap((section) => section.reports);

    return [
        { label: 'Ready reports', value: reports.filter((report) => report.ready).length },
        { label: 'Roadmap reports', value: reports.filter((report) => !report.ready).length },
        { label: 'Output formats', value: 4 },
        { label: 'Scopes', value: 2 },
    ];
});
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
</style>
