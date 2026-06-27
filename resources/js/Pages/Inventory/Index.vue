<template>
    <TenantShellLayout page-title="Inventory">
        <Head title="Inventory" />
        <h1 class="h4 mb-3">Inventory overview</h1>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">Low stock batches</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Batch</th>
                                    <th>Shelf</th>
                                    <th class="text-end">Qty</th>
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
                                    <td colspan="4" class="text-muted small">No batches below min stock.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">Recent movements</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>When</th>
                                    <th>Type</th>
                                    <th class="text-end">Qty</th>
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
        <h2 class="h6 mt-4">Expiry alerts</h2>
        <div class="row g-3 mb-4">
            <div class="col-lg-6" v-for="section in expirySections" :key="section.key">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold" :class="section.headerClass">{{ section.title }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Batch</th>
                                    <th>Expiry</th>
                                    <th class="text-end">Qty</th>
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

        <h2 class="h6 mt-4">All batches</h2>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Batch</th>
                        <th>Shelf</th>
                        <th>Expiry</th>
                        <th class="text-end">On hand</th>
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

const expirySections = computed(() => [
    {
        key: 'expired',
        title: 'Expired',
        items: props.expiredBatches,
        empty: 'No expired stock on hand.',
        headerClass: 'text-danger',
        dateClass: 'text-danger fw-medium',
    },
    {
        key: '30',
        title: 'Expiring within 30 days',
        items: props.expiringWithin30,
        empty: 'Nothing expiring in the next 30 days.',
        headerClass: 'text-warning',
        dateClass: 'text-warning',
    },
    {
        key: '60',
        title: 'Expiring in 31–60 days',
        items: props.expiringWithin60,
        empty: 'Nothing in the 31–60 day window.',
        headerClass: '',
        dateClass: '',
    },
    {
        key: '90',
        title: 'Expiring in 61–90 days',
        items: props.expiringWithin90,
        empty: 'Nothing in the 61–90 day window.',
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
