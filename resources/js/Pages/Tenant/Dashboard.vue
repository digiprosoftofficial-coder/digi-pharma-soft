<template>
    <TenantShellLayout :page-title="headline">
        <Head :title="headline" />
        <div v-if="$page.props.flash?.success" class="alert alert-success">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.flash?.info" class="alert alert-info">{{ $page.props.flash.info }}</div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <p class="text-muted small fw-semibold mb-1">{{ t('dashboard.owner_overview') }}</p>
                <h1 class="h3 mb-0">{{ headline }}</h1>
            </div>
            <span class="badge bg-primary-subtle text-primary">{{ t('dashboard.ceo_dashboard') }}</span>
        </div>

        <div class="row g-3 mb-4">
            <div v-for="card in kpiCards" :key="card.label" class="col-6 col-xl-3">
                <div class="card kpi-card border-0 shadow-sm h-100" :class="`kpi-card--${card.tone}`">
                    <div class="card-body d-flex align-items-start gap-3">
                        <span class="kpi-card__icon" :class="`kpi-card__icon--${card.tone}`">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path v-for="(d, i) in card.iconPaths" :key="i" :d="d" />
                            </svg>
                        </span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="kpi-card__label">{{ card.label }}</div>
                            <div class="kpi-card__value" :class="`kpi-card__value--${card.tone}`">
                                {{ card.money ? formatMoney(card.value) : formatNumber(card.value) }}
                            </div>
                            <div class="kpi-card__help">{{ card.help }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
                        <span class="fw-semibold">{{ t('dashboard.sales_trend_last_7_days') }}</span>
                        <span class="badge bg-success-subtle text-success">{{ t('dashboard.line_chart') }}</span>
                    </div>
                    <div class="card-body">
                        <div class="sales-chart">
                            <svg class="sales-chart__svg" viewBox="0 0 640 260" preserveAspectRatio="none" role="img" :aria-label="t('dashboard.sales_trend_last_7_days')">
                                <defs>
                                    <linearGradient id="salesAreaGradient" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#10b981" stop-opacity="0.24" />
                                        <stop offset="100%" stop-color="#10b981" stop-opacity="0.02" />
                                    </linearGradient>
                                </defs>
                                <g class="sales-chart__grid">
                                    <line v-for="line in chartGridLines" :key="line" x1="44" x2="620" :y1="line" :y2="line" />
                                </g>
                                <path v-if="chartAreaPath" class="sales-chart__area" :d="chartAreaPath" />
                                <path v-if="chartLinePath" class="sales-chart__line" :d="chartLinePath" />
                                <g v-for="point in chartPoints" :key="point.label">
                                    <circle class="sales-chart__point" :cx="point.x" :cy="point.y" r="5" />
                                    <title>{{ point.label }}: {{ formatMoney(point.total) }}</title>
                                </g>
                            </svg>
                            <div class="sales-chart__axis">
                                <span v-for="d in chartDays" :key="'axis-' + d.label">{{ d.label }}</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 small text-muted">
                            <span>{{ t('dashboard.highest_day') }}: {{ formatMoney(maxChartTotal) }}</span>
                            <strong class="text-success">{{ formatMoney(kpis.revenueToday) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2">
                        <span class="fw-semibold">{{ t('dashboard.top_selling_medicines') }}</span>
                        <span class="badge bg-info-subtle text-info">{{ t('dashboard.pie_chart') }}</span>
                    </div>
                    <div class="card-body">
                        <div v-if="donutRows.length" class="top-medicine-chart">
                            <div class="medicine-donut" :style="donutStyle">
                                <div class="medicine-donut__hole">
                                    <strong>{{ formatQty(totalMedicineQuantity) }}</strong>
                                    <span>{{ t('dashboard.sold') }}</span>
                                </div>
                            </div>
                            <div class="medicine-legend">
                                <div v-for="row in donutRows" :key="row.key" class="medicine-legend__row">
                                    <span class="medicine-legend__name">
                                        <span class="medicine-legend__dot" :style="{ backgroundColor: row.color }"></span>
                                        {{ row.label }}
                                    </span>
                                    <span class="medicine-legend__meta">
                                        <strong>{{ formatQty(row.quantity) }}</strong>
                                        <span>{{ row.percent }}%</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-muted small">{{ t('dashboard.no_medicines_sold_today') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">{{ t('dashboard.branch_performance') }}</div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('dashboard.branch') }}</th>
                            <th class="text-end">{{ t('dashboard.todays_sales_column') }}</th>
                            <th class="text-end">{{ t('dashboard.todays_purchase_column') }}</th>
                            <th class="text-end">{{ t('dashboard.sales_due') }}</th>
                            <th class="text-end">{{ t('dashboard.stock_value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="branch in branchPerformance" :key="branch.id">
                            <td>
                                <span class="fw-medium">{{ branch.name }}</span>
                                <span class="text-muted small ms-1">{{ branch.code }}</span>
                            </td>
                            <td class="text-end">{{ formatMoney(branch.sales_today) }}</td>
                            <td class="text-end">{{ formatMoney(branch.purchases_today) }}</td>
                            <td class="text-end text-danger">{{ formatMoney(branch.sales_due) }}</td>
                            <td class="text-end">{{ formatMoney(branch.stock_value) }}</td>
                        </tr>
                        <tr v-if="!branchPerformance?.length">
                            <td colspan="5" class="text-center text-muted py-4">{{ t('dashboard.no_branch_performance') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-danger-subtle text-danger-emphasis fw-semibold d-flex align-items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <path d="M12 9v4" />
                            <path d="M12 17h.01" />
                        </svg>
                        {{ t('dashboard.critical_stock') }}
                    </div>
                    <ul class="list-group list-group-flush">
                        <li v-for="b in criticalStock" :key="b.id" class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ b.product?.name ?? t('dashboard.product') }}</span>
                            <span class="badge bg-danger text-white">{{ formatQty(b.quantity_on_hand) }}</span>
                        </li>
                        <li v-if="!criticalStock?.length" class="list-group-item text-muted small">{{ t('dashboard.no_low_stock_batches') }}</li>
                    </ul>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">{{ t('dashboard.recent_activity') }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ t('dashboard.event') }}</th>
                                    <th>{{ t('dashboard.description') }}</th>
                                    <th>{{ t('dashboard.when') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(a, idx) in activities" :key="idx">
                                    <td><span class="badge bg-secondary-subtle text-secondary">{{ a.event }}</span></td>
                                    <td>{{ a.description }}</td>
                                    <td class="text-muted small">{{ formatDate(a.created_at) }}</td>
                                </tr>
                                <tr v-if="!activities?.length">
                                    <td colspan="3" class="text-muted small">{{ t('dashboard.no_activity') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import { formatHumanDateTime as formatDate } from '@/utils/dates';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    headline: { type: String, required: true },
    kpis: { type: Object, required: true },
    chartDays: { type: Array, required: true },
    criticalStock: { type: Array, required: true },
    topMedicines: { type: Array, required: true },
    branchPerformance: { type: Array, required: true },
    activities: { type: Array, required: true },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const { formatQty } = useQuantity();

const kpiIcons = {
    sales: ['M12 1v22', 'M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
    profit: ['M23 6 13.5 15.5 8.5 10.5 1 18', 'M17 6h6v6'],
    purchase: [
        'M9 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z',
        'M20 22a1 1 0 1 0 0-2 1 1 0 0 0 0 2z',
        'M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6',
    ],
    stock: [
        'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z',
        'M3.27 6.96 12 12.01l8.73-5.05',
        'M12 22.08V12',
    ],
    customerDue: [
        'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2',
        'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
        'M23 21v-2a4 4 0 0 0-3-3.87',
        'M16 3.13a4 4 0 0 1 0 7.75',
    ],
    supplierDue: [
        'M1 3h15v13H1z',
        'M16 8h4l3 3v5h-7V8z',
        'M5.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
        'M18.5 21a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
    ],
    expired: ['M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z', 'M12 9v4', 'M12 17h.01'],
    nearExpiry: ['M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20z', 'M12 6v6l4 2'],
};

const kpiCards = computed(() => [
    {
        label: t('dashboard.todays_sales'),
        value: props.kpis.revenueToday,
        money: true,
        help: t('dashboard.yesterday_amount', { amount: formatMoney(props.kpis.revenueYesterday) }),
        tone: 'success',
        iconPaths: kpiIcons.sales,
    },
    { label: t('dashboard.todays_profit'), value: props.kpis.profitToday, money: true, help: t('dashboard.gross_line_profit'), tone: 'primary', iconPaths: kpiIcons.profit },
    { label: t('dashboard.todays_purchase'), value: props.kpis.purchaseToday, money: true, help: t('dashboard.posted_purchase_total'), tone: 'info', iconPaths: kpiIcons.purchase },
    { label: t('dashboard.total_stock_value'), value: props.kpis.stockValue, money: true, help: t('dashboard.current_inventory_value'), tone: 'teal', iconPaths: kpiIcons.stock },
    {
        label: t('dashboard.total_due_customer'),
        value: props.kpis.customerDue,
        money: true,
        help: t('dashboard.due_sales_count', { count: formatNumber(props.kpis.pendingOrders) }),
        tone: 'danger',
        iconPaths: kpiIcons.customerDue,
    },
    { label: t('dashboard.total_due_supplier'), value: props.kpis.supplierDue, money: true, help: t('dashboard.open_supplier_payable'), tone: 'warning', iconPaths: kpiIcons.supplierDue },
    { label: t('dashboard.expired_products'), value: props.kpis.expiredProducts, money: false, help: t('dashboard.batches_past_expiry'), tone: 'danger', iconPaths: kpiIcons.expired },
    { label: t('dashboard.near_expiry_products'), value: props.kpis.nearExpiryProducts, money: false, help: t('dashboard.expiring_within_30_days'), tone: 'warning', iconPaths: kpiIcons.nearExpiry },
]);

const chartGridLines = [32, 78, 124, 170, 216];
const chartBounds = { left: 44, right: 620, top: 24, bottom: 220 };
const donutColors = ['#10b981', '#0ea5e9', '#06b6d4', '#38bdf8', '#7dd3fc', '#0f766e'];

const maxChartTotal = computed(() => Math.max(...props.chartDays.map((d) => Number(d.total || 0)), 1));

const chartPoints = computed(() => {
    const width = chartBounds.right - chartBounds.left;
    const height = chartBounds.bottom - chartBounds.top;
    const divisor = Math.max(props.chartDays.length - 1, 1);

    return props.chartDays.map((day, index) => {
        const total = Number(day.total || 0);

        return {
            label: day.label,
            total,
            x: chartBounds.left + (width / divisor) * index,
            y: chartBounds.bottom - (total / maxChartTotal.value) * height,
        };
    });
});

const chartLinePath = computed(() => smoothPath(chartPoints.value));

const chartAreaPath = computed(() => {
    if (!chartPoints.value.length || !chartLinePath.value) {
        return '';
    }

    const first = chartPoints.value[0];
    const last = chartPoints.value[chartPoints.value.length - 1];

    return `${chartLinePath.value} L ${last.x} ${chartBounds.bottom} L ${first.x} ${chartBounds.bottom} Z`;
});

const totalMedicineQuantity = computed(() =>
    props.topMedicines.reduce((total, medicine) => total + Number(medicine.quantity || 0), 0),
);

const donutRows = computed(() => {
    if (totalMedicineQuantity.value <= 0) {
        return [];
    }

    const visibleRows = props.topMedicines.slice(0, 5).map((medicine, index) => ({
        key: medicine.product_id ?? `medicine-${index}`,
        label: medicine.product_name,
        quantity: Number(medicine.quantity || 0),
        color: donutColors[index % donutColors.length],
    }));
    const otherQuantity = props.topMedicines
        .slice(5)
        .reduce((total, medicine) => total + Number(medicine.quantity || 0), 0);

    if (otherQuantity > 0) {
        visibleRows.push({
            key: 'others',
            label: t('dashboard.others'),
            quantity: otherQuantity,
            color: donutColors[visibleRows.length % donutColors.length],
        });
    }

    return visibleRows.map((row) => ({
        ...row,
        percent: Math.round((row.quantity / totalMedicineQuantity.value) * 100),
    }));
});

const donutStyle = computed(() => {
    if (!donutRows.value.length) {
        return { background: '#eef2f7' };
    }

    let cursor = 0;
    const segments = donutRows.value.map((row) => {
        const start = cursor;
        cursor += (row.quantity / totalMedicineQuantity.value) * 100;

        return `${row.color} ${start}% ${cursor}%`;
    });

    return {
        background: `conic-gradient(${segments.join(', ')})`,
    };
});

function smoothPath(points) {
    if (!points.length) {
        return '';
    }

    if (points.length === 1) {
        return `M ${points[0].x} ${points[0].y}`;
    }

    const commands = [`M ${points[0].x} ${points[0].y}`];

    for (let i = 0; i < points.length - 1; i += 1) {
        const previous = points[i - 1] ?? points[i];
        const current = points[i];
        const next = points[i + 1];
        const following = points[i + 2] ?? next;
        const cp1x = current.x + (next.x - previous.x) / 6;
        const cp1y = current.y + (next.y - previous.y) / 6;
        const cp2x = next.x - (following.x - current.x) / 6;
        const cp2y = next.y - (following.y - current.y) / 6;

        commands.push(`C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${next.x} ${next.y}`);
    }

    return commands.join(' ');
}

function formatNumber(value) {
    return new Intl.NumberFormat().format(Number(value || 0));
}

</script>

<style scoped>
.kpi-card {
    --kpi-color: #2563eb;
    --kpi-tint: rgba(37, 99, 235, 0.1);
    border-radius: 0.9rem;
    background: #ffffff;
    border-inline-start: 5px solid var(--kpi-color) !important;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.65rem 1.5rem rgba(15, 23, 42, 0.12) !important;
}

.kpi-card--success { --kpi-color: #16a34a; --kpi-tint: rgba(22, 163, 74, 0.12); }
.kpi-card--primary { --kpi-color: #2563eb; --kpi-tint: rgba(37, 99, 235, 0.12); }
.kpi-card--info { --kpi-color: #0891b2; --kpi-tint: rgba(8, 145, 178, 0.12); }
.kpi-card--teal { --kpi-color: #0d9488; --kpi-tint: rgba(13, 148, 136, 0.12); }
.kpi-card--danger { --kpi-color: #dc2626; --kpi-tint: rgba(220, 38, 38, 0.12); }
.kpi-card--warning { --kpi-color: #d97706; --kpi-tint: rgba(217, 119, 6, 0.14); }
.kpi-card--secondary { --kpi-color: #475569; --kpi-tint: rgba(71, 85, 105, 0.12); }

.kpi-card__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 3rem;
    height: 3rem;
    border-radius: 0.85rem;
    color: #ffffff;
    background: var(--kpi-color);
    box-shadow: 0 0.35rem 0.75rem var(--kpi-tint);
}

.kpi-card__label {
    color: #64748b;
    font-size: 0.82rem;
    font-weight: 600;
    line-height: 1.25;
}

.kpi-card__value {
    color: var(--kpi-color);
    font-size: 1.55rem;
    font-weight: 800;
    line-height: 1.15;
    margin: 0.15rem 0;
    word-break: break-word;
}

.kpi-card__help {
    color: #94a3b8;
    font-size: 0.76rem;
    line-height: 1.3;
}

@media (max-width: 575.98px) {
    .kpi-card__icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.7rem;
    }

    .kpi-card__value {
        font-size: 1.25rem;
    }
}

.sales-chart {
    min-height: 235px;
}

.sales-chart__svg {
    display: block;
    width: 100%;
    height: 215px;
}

.sales-chart__grid line {
    stroke: #e5edf3;
    stroke-width: 1;
}

.sales-chart__area {
    fill: url(#salesAreaGradient);
}

.sales-chart__line {
    fill: none;
    stroke: #10b981;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-width: 4;
}

.sales-chart__point {
    fill: #10b981;
    stroke: #ffffff;
    stroke-width: 3;
}

.sales-chart__axis {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    color: #64748b;
    font-size: 0.78rem;
}

.top-medicine-chart {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    min-height: 235px;
}

.medicine-donut {
    position: relative;
    flex: 0 0 auto;
    width: 11rem;
    height: 11rem;
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
}

.medicine-donut__hole {
    position: absolute;
    inset: 2.65rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    border-radius: 50%;
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.06);
}

.medicine-donut__hole strong {
    color: #0f172a;
    font-size: 1.05rem;
    line-height: 1;
}

.medicine-donut__hole span {
    color: #64748b;
    font-size: 0.7rem;
}

.medicine-legend {
    flex: 1 1 auto;
    min-width: 0;
}

.medicine-legend__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.65rem;
    color: #334155;
    font-size: 0.82rem;
}

.medicine-legend__name {
    display: inline-flex;
    align-items: center;
    min-width: 0;
}

.medicine-legend__dot {
    display: inline-block;
    flex: 0 0 auto;
    width: 0.65rem;
    height: 0.65rem;
    margin-right: 0.45rem;
    border-radius: 50%;
}

.medicine-legend__meta {
    display: inline-flex;
    flex: 0 0 auto;
    gap: 0.45rem;
    color: #64748b;
}

.medicine-legend__meta strong {
    color: #0f172a;
}

@media (max-width: 575.98px) {
    .top-medicine-chart {
        flex-direction: column;
        align-items: stretch;
    }

    .medicine-donut {
        align-self: center;
    }
}
</style>
