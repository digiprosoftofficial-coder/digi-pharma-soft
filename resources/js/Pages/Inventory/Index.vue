<template>
    <TenantShellLayout :page-title="t('tenant_nav.inventory')">
        <Head :title="t('tenant_nav.inventory')" />
        <h1 class="h4 mb-3">{{ t('reports.inventory_overview') }}</h1>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ t('reports.low_stock_batches') }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ t('sales.product') }}</th>
                                    <th>{{ t('purchases.batch') }}</th>
                                    <th>{{ t('catalog.storage_location_shelf') }}</th>
                                    <th class="text-end">{{ t('sales.qty') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="b in lowStockBatches" :key="b.id">
                                    <td>{{ b.product?.name }}</td>
                                    <td>{{ b.batch_no }}</td>
                                    <td class="small">{{ shelfLabel(b) }}</td>
                                    <td class="text-end">{{ formatQty(b.quantity_on_hand) }}</td>
                                </tr>
                                <tr v-if="!lowStockBatches.length">
                                    <td colspan="4" class="text-muted small">{{ t('reports.no_batches_below_min_stock') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ t('reports.recent_movements') }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ t('dashboard.when') }}</th>
                                    <th>{{ t('catalog.product_type') }}</th>
                                    <th class="text-end">{{ t('sales.qty') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in recentMovements" :key="m.id">
                                    <td class="small">{{ m.created_at }}</td>
                                    <td>{{ m.type }}</td>
                                    <td class="text-end">{{ formatQty(m.quantity_delta) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <h2 class="h6 mt-4">{{ t('reports.expiry_alerts') }}</h2>
        <div class="row g-3 mb-4">
            <div class="col-lg-6" v-for="section in expirySections" :key="section.key">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold" :class="section.headerClass">{{ section.title }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ t('sales.product') }}</th>
                                    <th>{{ t('purchases.batch') }}</th>
                                    <th>{{ t('purchases.expiry') }}</th>
                                    <th class="text-end">{{ t('sales.qty') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="b in section.items" :key="b.id">
                                    <td>{{ b.product?.name }}</td>
                                    <td>{{ b.batch_no }}</td>
                                    <td :class="section.dateClass">{{ formatHumanDate(b.expiry_date) }}</td>
                                    <td class="text-end">{{ formatQty(b.quantity_on_hand) }}</td>
                                </tr>
                                <tr v-if="!section.items.length">
                                    <td colspan="4" class="text-muted small">{{ section.empty }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="h6 mt-4">{{ t('reports.all_batches') }}</h2>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('sales.product') }}</th>
                        <th>{{ t('purchases.batch') }}</th>
                        <th>{{ t('catalog.storage_location_shelf') }}</th>
                        <th>{{ t('purchases.expiry') }}</th>
                        <th class="text-end">{{ t('catalog.current_stock') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in batches.data" :key="b.id">
                        <td>{{ b.product?.name }}</td>
                        <td>{{ b.batch_no }}</td>
                        <td class="small">{{ shelfLabel(b) }}</td>
                        <td>{{ formatHumanDate(b.expiry_date) }}</td>
                        <td class="text-end">{{ formatQty(b.quantity_on_hand) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useQuantity } from '@/composables/useQuantity';
import { formatHumanDate } from '@/utils/dates';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    lowStockBatches: { type: Array, required: true },
    recentMovements: { type: Array, required: true },
    batches: { type: Object, required: true },
    expiredBatches: { type: Array, default: () => [] },
    expiringWithin30: { type: Array, default: () => [] },
    expiringWithin60: { type: Array, default: () => [] },
    expiringWithin90: { type: Array, default: () => [] },
});

const { formatQty } = useQuantity();
const { t } = useLocale();

const expirySections = computed(() => [
    {
        key: 'expired',
        title: t('reports.expired'),
        items: props.expiredBatches,
        empty: t('reports.no_expired_stock'),
        headerClass: 'text-danger',
        dateClass: 'text-danger fw-medium',
    },
    {
        key: '30',
        title: t('reports.expiring_30_days'),
        items: props.expiringWithin30,
        empty: t('reports.nothing_expiring_30'),
        headerClass: 'text-warning',
        dateClass: 'text-warning',
    },
    {
        key: '60',
        title: t('reports.expiring_31_60_days'),
        items: props.expiringWithin60,
        empty: t('reports.nothing_expiring_31_60'),
        headerClass: '',
        dateClass: '',
    },
    {
        key: '90',
        title: t('reports.expiring_61_90_days'),
        items: props.expiringWithin90,
        empty: t('reports.nothing_expiring_61_90'),
        headerClass: '',
        dateClass: '',
    },
]);

function shelfLabel(batch) {
    const loc = batch.effective_storage_location;

    if (!loc) {
        return '—';
    }

    return loc.code ? `${loc.name} (${loc.code})` : loc.name;
}
</script>
