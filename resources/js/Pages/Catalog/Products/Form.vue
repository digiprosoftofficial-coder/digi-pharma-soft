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
                        <span class="small text-muted d-block mb-1">Label preview</span>
                        <img :src="`/barcodes/${product.id}`" alt="Barcode" class="border rounded bg-white p-1" style="max-height: 64px" />
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product type</label>
                    <select v-model="form.product_type" class="form-select" required>
                        <option v-for="t in catalogOptions.productTypes" :key="t" :value="t">
                            {{ typeLabel(t) }}
                        </option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Base stock unit</label>
                    <select v-model="form.base_unit" class="form-select" required @change="onBaseUnitChange">
                        <option v-for="u in catalogOptions.sellUnits" :key="u" :value="u">{{ unitLabel(u) }}</option>
                    </select>
                    <p class="form-text small mb-0">Inventory is tracked in this unit.</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Min stock alert</label>
                    <input v-model="form.min_stock" type="number" min="0" class="form-control" />
                </div>
            </div>

            <div class="mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">Sell units &amp; prices</h2>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="addUnitRow">Add unit</button>
                </div>
                <div v-if="form.errors.units" class="text-danger small mb-2">{{ form.errors.units }}</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Unit</th>
                                <th style="width: 8rem">Per base unit</th>
                                <th style="width: 9rem">Purchase</th>
                                <th style="width: 9rem">Sale</th>
                                <th>Default</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, idx) in form.units" :key="idx">
                                <td>
                                    <select v-model="row.sell_unit" class="form-select form-select-sm">
                                        <option v-for="u in catalogOptions.sellUnits" :key="u" :value="u">{{ unitLabel(u) }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input
                                        v-model.number="row.conversion_factor"
                                        type="number"
                                        min="0.0001"
                                        step="0.0001"
                                        class="form-control form-control-sm"
                                        :disabled="row.sell_unit === form.base_unit"
                                    />
                                </td>
                                <td>
                                    <input v-model="row.purchase_price" type="number" min="0" step="0.0001" class="form-control form-control-sm" required />
                                </td>
                                <td>
                                    <input v-model="row.sale_price" type="number" min="0" step="0.0001" class="form-control form-control-sm" required />
                                </td>
                                <td class="text-center">
                                    <input
                                        :id="`default-${idx}`"
                                        type="radio"
                                        name="default_unit"
                                        :checked="row.is_default"
                                        @change="setDefault(idx)"
                                    />
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        :disabled="form.units.length <= 1"
                                        @click="removeUnitRow(idx)"
                                    >
                                        ×
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <div class="form-check">
                    <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="active">Active</label>
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
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    product: { type: Object, default: null },
    catalogOptions: {
        type: Object,
        default: () => ({ productTypes: ['other'], sellUnits: ['piece', 'strip', 'box'] }),
    },
});

function typeLabel(t) {
    return t.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function unitLabel(u) {
    return u.charAt(0).toUpperCase() + u.slice(1);
}

function buildDefaultUnits() {
    return [
        { sell_unit: 'strip', conversion_factor: 1, purchase_price: '0', sale_price: '0', is_default: true },
        { sell_unit: 'box', conversion_factor: 10, purchase_price: '0', sale_price: '0', is_default: false },
    ];
}

function initialUnits() {
    if (props.product?.units?.length) {
        return props.product.units.map((u) => ({
            sell_unit: u.sell_unit,
            conversion_factor: Number(u.conversion_factor),
            purchase_price: String(u.purchase_price),
            sale_price: String(u.sale_price),
            is_default: Boolean(u.is_default),
        }));
    }
    return buildDefaultUnits();
}

const form = useForm({
    name: props.product?.name ?? '',
    sku: props.product?.sku ?? '',
    barcode: props.product?.barcode ?? '',
    product_type: props.product?.product_type ?? 'tablet',
    base_unit: props.product?.base_unit ?? 'strip',
    units: initialUnits(),
    min_stock: props.product?.min_stock ?? 0,
    is_active: props.product?.is_active ?? true,
});

function onBaseUnitChange() {
    form.units.forEach((row) => {
        if (row.sell_unit === form.base_unit) {
            row.conversion_factor = 1;
        }
    });
}

function setDefault(idx) {
    form.units.forEach((row, i) => {
        row.is_default = i === idx;
    });
}

function addUnitRow() {
    const used = form.units.map((r) => r.sell_unit);
    const next = props.catalogOptions.sellUnits.find((u) => !used.includes(u)) ?? 'piece';
    form.units.push({
        sell_unit: next,
        conversion_factor: next === form.base_unit ? 1 : 1,
        purchase_price: '0',
        sale_price: '0',
        is_default: false,
    });
}

function removeUnitRow(idx) {
    const wasDefault = form.units[idx].is_default;
    form.units.splice(idx, 1);
    if (wasDefault && form.units.length) {
        form.units[0].is_default = true;
    }
}

function submit() {
    const payload = {
        ...form.data(),
        units: form.units.map((row) => ({
            sell_unit: row.sell_unit,
            conversion_factor: row.sell_unit === form.base_unit ? 1 : row.conversion_factor,
            purchase_price: row.purchase_price,
            sale_price: row.sale_price,
            is_default: row.is_default,
        })),
    };

    if (props.product) {
        form.transform(() => payload).put(`/products/${props.product.id}`);
    } else {
        form.transform(() => payload).post('/products');
    }
}
</script>
