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
                                    <td class="text-end">{{ b.quantity_on_hand }}</td>
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
                                    <td class="text-end">{{ m.quantity_delta }}</td>
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
                        <td>{{ b.expiry_date }}</td>
                        <td class="text-end">{{ b.quantity_on_hand }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    lowStockBatches: { type: Array, required: true },
    recentMovements: { type: Array, required: true },
    batches: { type: Object, required: true },
});

function shelfLabel(batch) {
    const loc = batch.effective_storage_location;

    if (!loc) {
        return '—';
    }

    return loc.code ? `${loc.name} (${loc.code})` : loc.name;
}
</script>
