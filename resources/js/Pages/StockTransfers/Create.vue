<template>
    <TenantShellLayout page-title="New stock transfer">
        <Head title="Stock transfer" />
        <h1 class="h4 mb-3">Move quantity between batches (same product)</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Notes</label>
                <input v-model="form.notes" class="form-control" />
            </div>
            <div v-for="(line, i) in form.lines" :key="i" class="row g-2 border-bottom py-2">
                <div class="col-md-4">
                    <label class="form-label small">From batch ID</label>
                    <input v-model.number="line.from_batch_id" type="number" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label small">To batch ID</label>
                    <input v-model.number="line.to_batch_id" type="number" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Qty</label>
                    <input v-model.number="line.quantity" type="number" min="0.0001" step="0.0001" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="form.lines.splice(i, 1)">×</button>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" @click="addLine">Add line</button>
            <div class="mt-3">
                <button class="btn btn-primary" :disabled="form.processing">Submit</button>
                <Link href="/stock-transfers" class="btn btn-link">Cancel</Link>
            </div>
        </form>
        <p class="small text-muted mt-3">Tip: create two batches for the same product first, then move stock between them.</p>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    notes: '',
    lines: [{ from_batch_id: null, to_batch_id: null, quantity: 1 }],
});

function addLine() {
    form.lines.push({ from_batch_id: null, to_batch_id: null, quantity: 1 });
}

function submit() {
    form.post('/stock-transfers');
}
</script>
