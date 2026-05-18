<template>
    <TenantShellLayout page-title="Products">
        <Head title="Products" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0 d-lg-none">Products</h1>
            <Link href="/products/create" class="btn btn-primary">Add product</Link>
        </div>
        <div class="table-responsive card border-0 shadow-sm">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Barcode</th>
                        <th class="text-end">Sale ({{ currencyCode() }})</th>
                        <th>Stock</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in products.data" :key="p.id">
                        <td>{{ p.name }}</td>
                        <td>{{ p.sku }}</td>
                        <td>{{ p.barcode || '—' }}</td>
                        <td class="text-end">{{ formatMoney(p.sale_price) }}</td>
                        <td>
                            <span v-if="p.batches?.length">
                                {{ p.batches.reduce((s, b) => s + Number(b.quantity_on_hand), 0) }}
                            </span>
                            <span v-else>0</span>
                        </td>
                        <td class="text-end">
                            <a :href="`/barcodes/${p.id}`" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary me-1">Barcode</a>
                            <Link :href="`/products/${p.id}/edit`" class="btn btn-sm btn-outline-secondary">Edit</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <nav v-if="products.links?.length > 3" class="mt-3">
            <ul class="pagination">
                <li v-for="l in products.links" :key="l.label" class="page-item" :class="{ active: l.active, disabled: !l.url }">
                    <Link v-if="l.url" class="page-link" :href="l.url" v-html="l.label" />
                    <span v-else class="page-link" v-html="l.label" />
                </li>
            </ul>
        </nav>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    products: { type: Object, required: true },
});

const { formatMoney, currencyCode } = useMoney();
</script>
