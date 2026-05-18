<template>
    <TenantShellLayout page-title="New purchase">
        <Head title="New purchase" />
        <h1 class="h4 mb-3">Record purchase</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <select v-model="form.supplier_id" class="form-select" required>
                        <option value="" disabled>Select</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Invoice no</label>
                    <input v-model="form.invoice_no" class="form-control" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Purchased at</label>
                    <input v-model="form.purchased_at" type="date" class="form-control" required />
                </div>
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label">Tax ({{ currencyCode() }})</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model.number="form.tax" type="number" min="0" step="0.01" class="form-control" />
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount ({{ currencyCode() }})</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model.number="form.discount" type="number" min="0" step="0.01" class="form-control" />
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Paid ({{ currencyCode() }})</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model.number="form.paid" type="number" min="0" step="0.01" class="form-control" />
                    </div>
                </div>
            </div>
            <h2 class="h6">Lines</h2>
            <div v-for="(line, i) in form.lines" :key="i" class="row g-2 align-items-end border-bottom py-2">
                <div class="col-md-3">
                    <label class="form-label small">Product ID</label>
                    <input v-model.number="line.product_id" type="number" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Batch</label>
                    <input v-model="line.batch_no" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Expiry</label>
                    <input v-model="line.expiry_date" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Unit</label>
                    <select v-model="line.sell_unit" class="form-select form-select-sm">
                        <option value="piece">Piece</option>
                        <option value="strip">Strip</option>
                        <option value="box">Box</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Qty</label>
                    <input v-model.number="line.quantity" type="number" min="0.0001" step="0.0001" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Unit cost ({{ currencyCode() }})</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model.number="line.unit_cost" type="number" min="0" step="0.01" class="form-control form-control-sm" required />
                    </div>
                    <div v-if="Number(line.quantity) > 0 && Number(line.unit_cost) > 0" class="form-text">
                        = {{ formatMoney(Number(line.quantity) * Number(line.unit_cost)) }}
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="form.lines.splice(i, 1)">×</button>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" @click="addLine">Add line</button>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Save purchase</button>
                <Link href="/purchases" class="btn btn-link">Cancel</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ suppliers: { type: Array, required: true } });

const { formatMoney, currencyCode, currencySymbol } = useMoney();

const form = useForm({
    supplier_id: '',
    invoice_no: '',
    purchased_at: new Date().toISOString().slice(0, 10),
    tax: 0,
    discount: 0,
    paid: 0,
    lines: [{ product_id: null, batch_no: '', expiry_date: '', sell_unit: 'strip', quantity: 1, unit_cost: 0 }],
});

function addLine() {
    form.lines.push({ product_id: null, batch_no: '', expiry_date: '', sell_unit: 'strip', quantity: 1, unit_cost: 0 });
}

function submit() {
    form.post('/purchases');
}
</script>
