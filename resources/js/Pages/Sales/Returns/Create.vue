<template>
    <TenantShellLayout page-title="New return">
        <Head title="New return" />
        <h1 class="h4 mb-3">Record return to stock</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Link original sale (optional)</label>
                <select v-model="form.sale_id" class="form-select">
                    <option :value="null">— Walk-in return —</option>
                    <option v-for="s in sales" :key="s.id" :value="s.id">{{ s.invoice_no }} ({{ s.sold_at }})</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">Notes</label>
                <textarea v-model="form.notes" class="form-control" rows="2"></textarea>
            </div>
            <h2 class="h6">Lines</h2>
            <div v-for="(line, i) in form.lines" :key="i" class="row g-2 align-items-end border-bottom py-2">
                <div class="col-md-4">
                    <label class="form-label small">Batch ID</label>
                    <input v-model.number="line.product_batch_id" type="number" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Qty</label>
                    <input v-model.number="line.quantity" type="number" min="0.0001" step="0.0001" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Refund unit price</label>
                    <input v-model.number="line.unit_price" type="number" min="0" step="0.01" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="form.lines.splice(i, 1)">×</button>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" @click="addLine">Add line</button>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Save return</button>
                <Link href="/sales/returns" class="btn btn-link">Cancel</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ sales: { type: Array, required: true } });

const form = useForm({
    sale_id: null,
    notes: '',
    lines: [{ product_batch_id: null, quantity: 1, unit_price: 0 }],
});

function addLine() {
    form.lines.push({ product_batch_id: null, quantity: 1, unit_price: 0 });
}

function submit() {
    form.post('/sales/returns');
}
</script>
