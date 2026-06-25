<template>
    <TenantShellLayout :page-title="headline">
        <Head :title="headline" />
        <div v-if="$page.props.flash?.success" class="alert alert-success">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.flash?.info" class="alert alert-info">{{ $page.props.flash.info }}</div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <p class="text-muted small text-uppercase fw-semibold mb-1">{{ t('dashboard.owner_overview') }}</p>
                <h1 class="h3 mb-0">{{ headline }}</h1>
            </div>
            <span class="badge bg-primary-subtle text-primary">{{ t('dashboard.ceo_dashboard') }}</span>
        </div>

        <div class="row g-3 mb-4">
            <div v-for="card in kpiCards" :key="card.label" class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4" :class="card.border">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">{{ card.label }}</div>
                        <div class="h3 fw-semibold mb-1">{{ card.money ? formatMoney(card.value) : formatNumber(card.value) }}</div>
                        <div class="small text-muted">{{ card.help }}</div>
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
                    <div class="card-header bg-white fw-semibold">{{ t('dashboard.critical_stock') }}</div>
                    <ul class="list-group list-group-flush">
                        <li v-for="b in criticalStock" :key="b.id" class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ b.product?.name ?? t('dashboard.product') }}</span>
                            <span class="badge bg-danger-subtle text-danger">{{ formatQty(b.quantity_on_hand) }}</span>
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

const kpiCards = computed(() => [
    {
        label: t('dashboard.todays_sales'),
        value: props.kpis.revenueToday,
        money: true,
        help: t('dashboard.yesterday_amount', { amount: formatMoney(props.kpis.revenueYesterday) }),
        border: 'border-primary',
    },
    { label: t('dashboard.todays_profit'), value: props.kpis.profitToday, money: true, help: t('dashboard.gross_line_profit'), border: 'border-success' },
    { label: t('dashboard.todays_purchase'), value: props.kpis.purchaseToday, money: true, help: t('dashboard.posted_purchase_total'), border: 'border-info' },
    { label: t('dashboard.total_stock_value'), value: props.kpis.stockValue, money: true, help: t('dashboard.current_inventory_value'), border: 'border-secondary' },
    {
        label: t('dashboard.total_due_customer'),
        value: props.kpis.customerDue,
        money: true,
        help: t('dashboard.due_sales_count', { count: formatNumber(props.kpis.pendingOrders) }),
        border: 'border-danger',
    },
    { label: t('dashboard.total_due_supplier'), value: props.kpis.supplierDue, money: true, help: t('dashboard.open_supplier_payable'), border: 'border-warning' },
    { label: t('dashboard.expired_products'), value: props.kpis.expiredProducts, money: false, help: t('dashboard.batches_past_expiry'), border: 'border-danger' },
    { label: t('dashboard.near_expiry_products'), value: props.kpis.nearExpiryProducts, money: false, help: t('dashboard.expiring_within_30_days'), border: 'border-warning' },
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

function formatDate(iso) {
    if (!iso) {
        return '';
    }
    return new Date(iso).toLocaleString();
}
</script>

<style scoped>
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
