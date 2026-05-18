<template>
    <TenantShellLayout :page-title="product ? 'Edit product' : 'New product'">
        <Head :title="product ? 'Edit product' : 'New product'" />
        <h1 class="h4 mb-4 d-lg-none">{{ product ? 'Edit product' : 'New product' }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" type="text" class="form-control" required />
                    <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">SKU</label>
                    <input v-model="form.sku" type="text" class="form-control" required />
                    <div v-if="form.errors.sku" class="text-danger small">{{ form.errors.sku }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Barcode</label>
                    <input v-model="form.barcode" type="text" class="form-control" />
                    <div v-if="product" class="mt-2">
                        <span class="small text-muted d-block mb-1">Label preview (Code 128 / EAN-13 by format)</span>
                        <img :src="`/barcodes/${product.id}`" alt="Barcode" class="border rounded bg-white p-1" style="max-height: 64px" />
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Purchase price ({{ currencyCode() }})</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model="form.purchase_price" type="number" step="0.0001" min="0" class="form-control" required />
                    </div>
                    <div v-if="Number(form.purchase_price) > 0" class="form-text">
                        {{ formatMoney(form.purchase_price) }}
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sale price ({{ currencyCode() }})</label>
                    <div class="input-group">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model="form.sale_price" type="number" step="0.0001" min="0" class="form-control" required />
                    </div>
                    <div v-if="Number(form.sale_price) > 0" class="form-text">
                        {{ formatMoney(form.sale_price) }}
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min stock alert</label>
                    <input v-model="form.min_stock" type="number" min="0" class="form-control" />
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                        <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                        <label class="form-check-label" for="active">Active</label>
                    </div>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
                <Link href="/products" class="btn btn-outline-secondary">Cancel</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    product: { type: Object, default: null },
});

const { formatMoney, currencyCode, currencySymbol } = useMoney();

const form = useForm({
    name: props.product?.name ?? '',
    sku: props.product?.sku ?? '',
    barcode: props.product?.barcode ?? '',
    unit: props.product?.unit ?? 'pcs',
    purchase_price: props.product?.purchase_price ?? '0',
    sale_price: props.product?.sale_price ?? '0',
    min_stock: props.product?.min_stock ?? 0,
    is_active: props.product?.is_active ?? true,
});

function submit() {
    if (props.product) {
        form.put(`/products/${props.product.id}`);
    } else {
        form.post('/products');
    }
}
</script>
