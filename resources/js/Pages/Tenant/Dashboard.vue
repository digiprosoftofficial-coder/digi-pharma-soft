<template>
    <TenantShellLayout :page-title="headline">
        <Head :title="headline" />
        <div v-if="$page.props.flash?.success" class="alert alert-success">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.flash?.info" class="alert alert-info">{{ $page.props.flash.info }}</div>

        <div class="dashboard-toolbar mb-3">
            <div class="dashboard-toolbar__title">
                <h1 class="h3 mb-0">{{ headline }}</h1>
            </div>
            <div class="dropdown dashboard-toolbar__filter" ref="rangeDropdownEl">
                <button
                    class="dashboard-range-trigger"
                    type="button"
                    :aria-expanded="rangeMenuOpen"
                    @click.stop="rangeMenuOpen = !rangeMenuOpen"
                >
                    <span class="dashboard-range-trigger__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4" />
                            <path d="M8 2v4" />
                            <path d="M3 10h18" />
                        </svg>
                    </span>
                    <span class="dashboard-range-trigger__copy">
                        <span class="dashboard-range-trigger__label">{{ t('dashboard.filter_by_date') }}</span>
                        <span class="dashboard-range-trigger__value">{{ selectedRangeLabel }}</span>
                        <span class="dashboard-range-trigger__dates">{{ rangeSummaryShort }}</span>
                    </span>
                    <span class="dashboard-range-trigger__caret" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6" />
                        </svg>
                    </span>
                </button>
                <ul
                    class="dropdown-menu dropdown-menu-end shadow dashboard-range-menu"
                    :class="{ show: rangeMenuOpen }"
                >
                    <li v-for="option in rangeOptions" :key="option.value">
                        <button
                            type="button"
                            class="dropdown-item"
                            :class="{ active: dateRange.key === option.value }"
                            @click="selectRange(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div v-if="dateRange.key === 'custom'" class="card border-0 shadow-sm mb-3 dashboard-custom-range">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-sm-4 col-md-3">
                        <label class="form-label small mb-0">{{ t('purchases.date_from') }}</label>
                        <input v-model="customFrom" type="date" class="form-control form-control-sm" />
                    </div>
                    <div class="col-6 col-sm-4 col-md-3">
                        <label class="form-label small mb-0">{{ t('purchases.date_to') }}</label>
                        <input v-model="customTo" type="date" class="form-control form-control-sm" />
                    </div>
                    <div class="col-12 col-sm-4 col-md-3">
                        <button type="button" class="btn btn-sm btn-primary dashboard-custom-range__apply" @click="applyCustomRange">
                            {{ t('dashboard.apply_range') }}
                        </button>
                    </div>
                </div>
            </div>
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
                <div class="card border-0 shadow-sm h-100 sales-trend-card">
                    <div class="card-header sales-trend-card__header">
                        <div class="d-flex align-items-center gap-3">
                            <span class="sales-trend-card__icon" aria-hidden="true">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 3v18h18" />
                                    <path d="m7 16 4-5 4 3 5-7" />
                                </svg>
                            </span>
                            <div>
                                <div class="sales-trend-card__title">{{ t('dashboard.sales_trend_last_7_days') }}</div>
                                <div class="sales-trend-card__range">{{ chartDateRange }}</div>
                            </div>
                        </div>
                        <div class="sales-trend-card__total">
                            <span>{{ t('dashboard.last_7_days_total') }}</span>
                            <strong>{{ formatMoney(chartTotal) }}</strong>
                        </div>
                    </div>
                    <div class="card-body sales-trend-card__body">
                        <div class="sales-chart">
                            <svg class="sales-chart__svg" viewBox="0 0 640 260" preserveAspectRatio="none" role="img" :aria-label="t('dashboard.sales_trend_last_7_days')">
                                <defs>
                                    <linearGradient id="salesAreaGradient" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#10b981" stop-opacity="0.3" />
                                        <stop offset="100%" stop-color="#10b981" stop-opacity="0" />
                                    </linearGradient>
                                </defs>
                                <g class="sales-chart__grid">
                                    <line v-for="line in chartGridLines" :key="line" x1="44" x2="620" :y1="line" :y2="line" />
                                </g>
                                <path v-if="chartAreaPath" class="sales-chart__area" :d="chartAreaPath" />
                                <path v-if="chartLinePath" class="sales-chart__line" :d="chartLinePath" />
                                <g v-for="point in chartPoints" :key="point.date" class="sales-chart__point-group">
                                    <circle class="sales-chart__point-halo" :cx="point.x" :cy="point.y" r="9" />
                                    <circle class="sales-chart__point" :cx="point.x" :cy="point.y" r="5" />
                                    <title>{{ point.date }}: {{ formatMoney(point.total) }}</title>
                                </g>
                            </svg>
                            <div class="sales-chart__axis">
                                <span v-for="d in chartDays" :key="'axis-' + d.date">
                                    <strong>{{ d.label }}</strong>
                                    <small>{{ d.date }}</small>
                                </span>
                            </div>
                        </div>
                        <div class="sales-trend-summary">
                            <div class="sales-trend-summary__item">
                                <span>{{ t('dashboard.highest_day') }}</span>
                                <strong>{{ highestChartDay.date }} · {{ formatMoney(highestChartDay.total) }}</strong>
                            </div>
                            <div class="sales-trend-summary__divider"></div>
                            <div class="sales-trend-summary__item text-end">
                                <span>{{ t('dashboard.daily_average') }}</span>
                                <strong>{{ formatMoney(chartDailyAverage) }}</strong>
                            </div>
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
                        <div v-else class="text-muted small">{{ t('dashboard.no_medicines_sold_in_range') }}</div>
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
                            <th class="text-end">{{ t('dashboard.sales_column') }}</th>
                            <th class="text-end">{{ t('dashboard.purchase_column') }}</th>
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
import { Head, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    headline: { type: String, required: true },
    kpis: { type: Object, required: true },
    chartDays: { type: Array, required: true },
    criticalStock: { type: Array, required: true },
    topMedicines: { type: Array, required: true },
    branchPerformance: { type: Array, required: true },
    activities: { type: Array, required: true },
    dateRange: {
        type: Object,
        default: () => ({
            key: 'today',
            date_from: '',
            date_to: '',
        }),
    },
    rangeOptions: { type: Array, default: () => [] },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const { formatQty } = useQuantity();

const customFrom = ref(props.dateRange.date_from || '');
const customTo = ref(props.dateRange.date_to || '');
const rangeMenuOpen = ref(false);
const rangeDropdownEl = ref(null);

const selectedRangeLabel = computed(() => {
    const match = props.rangeOptions.find((option) => option.value === props.dateRange.key);

    return match?.label ?? t('dashboard.range_today');
});

const rangeSummary = computed(() =>
    t('dashboard.showing_range', {
        from: props.dateRange.date_from,
        to: props.dateRange.date_to,
    }),
);

const rangeSummaryShort = computed(() => {
    const from = props.dateRange.date_from || '';
    const to = props.dateRange.date_to || '';

    if (!from || !to) {
        return '';
    }

    if (from === to) {
        return from;
    }

    return `${from} → ${to}`;
});

const isTodayRange = computed(() => props.dateRange.key === 'today');

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
        label: isTodayRange.value ? t('dashboard.todays_sales') : t('dashboard.total_sales'),
        value: props.kpis.revenue ?? props.kpis.revenueToday,
        money: true,
        help: isTodayRange.value
            ? t('dashboard.yesterday_amount', { amount: formatMoney(props.kpis.revenueYesterday) })
            : rangeSummary.value,
        tone: 'success',
        iconPaths: kpiIcons.sales,
    },
    {
        label: isTodayRange.value ? t('dashboard.todays_profit') : t('dashboard.total_profit'),
        value: props.kpis.profit ?? props.kpis.profitToday,
        money: true,
        help: t('dashboard.gross_line_profit'),
        tone: 'primary',
        iconPaths: kpiIcons.profit,
    },
    {
        label: isTodayRange.value ? t('dashboard.todays_purchase') : t('dashboard.total_purchase'),
        value: props.kpis.purchase ?? props.kpis.purchaseToday,
        money: true,
        help: t('dashboard.posted_purchase_total'),
        tone: 'info',
        iconPaths: kpiIcons.purchase,
    },
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

function selectRange(key) {
    rangeMenuOpen.value = false;

    if (key === 'custom') {
        router.get(
            '/dashboard',
            {
                range: 'custom',
                date_from: customFrom.value || props.dateRange.date_from,
                date_to: customTo.value || props.dateRange.date_to,
            },
            { preserveState: true, preserveScroll: true },
        );
        return;
    }

    router.get('/dashboard', { range: key }, { preserveState: true, preserveScroll: true });
}

function applyCustomRange() {
    if (!customFrom.value || !customTo.value) {
        return;
    }

    router.get(
        '/dashboard',
        {
            range: 'custom',
            date_from: customFrom.value,
            date_to: customTo.value,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function onDocumentClick(event) {
    if (!rangeDropdownEl.value) {
        return;
    }
    if (!rangeDropdownEl.value.contains(event.target)) {
        rangeMenuOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
});

const chartGridLines = [32, 78, 124, 170, 216];
const chartBounds = { left: 44, right: 620, top: 24, bottom: 220 };
const donutColors = ['#10b981', '#0ea5e9', '#06b6d4', '#38bdf8', '#7dd3fc', '#0f766e'];

const maxChartTotal = computed(() => Math.max(...props.chartDays.map((d) => Number(d.total || 0)), 1));
const chartTotal = computed(() =>
    props.chartDays.reduce((total, day) => total + Number(day.total || 0), 0),
);
const chartDailyAverage = computed(() =>
    props.chartDays.length ? chartTotal.value / props.chartDays.length : 0,
);
const highestChartDay = computed(() =>
    props.chartDays.reduce(
        (highest, day) => Number(day.total || 0) > Number(highest.total || 0) ? day : highest,
        props.chartDays[0] ?? { date: '—', total: 0 },
    ),
);
const chartDateRange = computed(() => {
    if (!props.chartDays.length) {
        return '';
    }

    return `${props.chartDays[0].date} — ${props.chartDays[props.chartDays.length - 1].date}`;
});

const chartPoints = computed(() => {
    const width = chartBounds.right - chartBounds.left;
    const height = chartBounds.bottom - chartBounds.top;
    const divisor = Math.max(props.chartDays.length - 1, 1);

    return props.chartDays.map((day, index) => {
        const total = Number(day.total || 0);

        return {
            label: day.label,
            date: day.date,
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
.dashboard-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.85rem;
}

.dashboard-toolbar__title {
    min-width: 0;
    flex: 1 1 auto;
}

.dashboard-toolbar__filter {
    flex: 0 0 auto;
}

.dashboard-range-trigger {
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    width: 100%;
    min-width: 15.5rem;
    padding: 0.55rem 0.75rem;
    border: 1px solid #dbe7f0;
    border-radius: 0.85rem;
    background: #ffffff;
    box-shadow: 0 0.2rem 0.55rem rgba(15, 23, 42, 0.04);
    text-align: start;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.dashboard-range-trigger:hover,
.dashboard-range-trigger[aria-expanded='true'] {
    border-color: rgba(var(--bs-primary-rgb), 0.45);
    box-shadow: 0 0.35rem 0.9rem rgba(var(--bs-primary-rgb), 0.12);
}

.dashboard-range-trigger__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.7rem;
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.1);
}

.dashboard-range-trigger__copy {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1 1 auto;
    line-height: 1.2;
}

.dashboard-range-trigger__label {
    color: #94a3b8;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.dashboard-range-trigger__value {
    color: #0f172a;
    font-size: 0.92rem;
    font-weight: 700;
}

.dashboard-range-trigger__dates {
    margin-top: 0.1rem;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dashboard-range-trigger__caret {
    display: inline-flex;
    color: #64748b;
    flex: 0 0 auto;
}

.dashboard-range-menu {
    min-width: 14rem;
    max-height: 22rem;
    overflow: auto;
    margin-top: 0.35rem !important;
}

.dashboard-range-menu .dropdown-item.active {
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    font-weight: 600;
}

@media (max-width: 767.98px) {
    .dashboard-toolbar {
        flex-direction: column;
        align-items: stretch;
        gap: 0.7rem;
    }

    .dashboard-toolbar__title .h3 {
        font-size: 1.35rem;
    }

    .dashboard-toolbar__filter {
        width: 100%;
    }

    .dashboard-range-trigger {
        min-width: 0;
        width: 100%;
    }

    .dashboard-range-menu {
        width: 100%;
        min-width: 0;
    }

    .dashboard-custom-range__apply {
        width: 100%;
    }
}

.kpi-card {
    --kpi-color: var(--bs-primary);
    --kpi-tint: rgba(var(--bs-primary-rgb), 0.1);
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
.kpi-card--primary { --kpi-color: var(--bs-primary); --kpi-tint: rgba(var(--bs-primary-rgb), 0.12); }
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
    font-weight: 700;
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

.sales-trend-card {
    overflow: hidden;
    border-radius: 1rem;
}

.sales-trend-card__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e8f3ef;
    background: linear-gradient(135deg, #ffffff 45%, #f0fdf7);
}

.sales-trend-card__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    width: 2.75rem;
    height: 2.75rem;
    color: #047857;
    border-radius: 0.8rem;
    background: #d1fae5;
}

.sales-trend-card__title {
    color: #0f172a;
    font-size: 0.95rem;
    font-weight: 700;
}

.sales-trend-card__range {
    margin-top: 0.15rem;
    color: #94a3b8;
    font-size: 0.75rem;
    font-weight: 500;
}

.sales-trend-card__total {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.sales-trend-card__total span,
.sales-trend-summary__item span {
    color: #94a3b8;
    font-size: 0.72rem;
    font-weight: 600;
}

.sales-trend-card__total strong {
    color: #047857;
    font-size: 1.15rem;
    font-weight: 700;
}

.sales-trend-card__body {
    padding: 1.1rem 1.25rem 1rem;
}

.sales-chart {
    min-height: 246px;
}

.sales-chart__svg {
    display: block;
    width: 100%;
    height: 215px;
    overflow: visible;
}

.sales-chart__grid line {
    stroke: #e8eef3;
    stroke-width: 1;
    stroke-dasharray: 4 5;
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
    filter: drop-shadow(0 4px 5px rgba(16, 185, 129, 0.2));
}

.sales-chart__point-halo {
    fill: rgba(16, 185, 129, 0.13);
    opacity: 0;
    transition: opacity 0.15s ease;
}

.sales-chart__point {
    fill: #10b981;
    stroke: #ffffff;
    stroke-width: 3;
}

.sales-chart__point-group:hover .sales-chart__point-halo {
    opacity: 1;
}

.sales-chart__axis {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    color: #475569;
    font-size: 0.76rem;
    text-align: center;
}

.sales-chart__axis span {
    display: flex;
    flex-direction: column;
    gap: 0.05rem;
}

.sales-chart__axis strong {
    font-weight: 600;
}

.sales-chart__axis small {
    color: #94a3b8;
    font-size: 0.65rem;
}

.sales-trend-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 0.85rem;
    padding: 0.8rem 1rem;
    border: 1px solid #e8f3ef;
    border-radius: 0.75rem;
    background: #f8fffc;
}

.sales-trend-summary__item {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.sales-trend-summary__item strong {
    color: #334155;
    font-size: 0.82rem;
    font-weight: 700;
}

.sales-trend-summary__divider {
    align-self: stretch;
    width: 1px;
    background: #dcece6;
}

@media (max-width: 575.98px) {
    .sales-trend-card__header {
        align-items: flex-start;
        padding: 0.9rem 1rem;
    }

    .sales-trend-card__total span {
        display: none;
    }

    .sales-trend-card__total strong {
        font-size: 0.95rem;
    }

    .sales-trend-card__body {
        padding: 0.85rem 0.75rem;
    }

    .sales-chart__axis small {
        display: none;
    }
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
