<template>
    <TenantShellLayout page-title="Products">
        <Head title="Products" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h4 mb-0 d-lg-none">Products</h1>
            <Link v-if="can('products.manage')" href="/products/create" class="btn btn-primary">Add product</Link>
        </div>
        <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="applyFilters">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-0">Search</label>
                    <input v-model="filterForm.q" type="search" class="form-control form-control-sm" placeholder="Name, SKU, barcode" />
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">Type</label>
                    <select v-model="filterForm.product_type" class="form-select form-select-sm">
                        <option value="">All types</option>
                        <option v-for="t in productTypes" :key="t" :value="t">{{ t }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">Status</label>
                    <select v-model="filterForm.is_active" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters">Reset</button>
                </div>
            </div>
        </form>
        <div class="table-responsive card border-0 shadow-sm">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>SKU</th>
                        <th>Unit</th>
                        <th class="text-end">Sale ({{ currencyCode() }})</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in products.data" :key="p.id">
                        <td>{{ p.name }}</td>
                        <td class="text-capitalize">{{ p.product_type || '—' }}</td>
                        <td>{{ p.category?.name || '—' }}</td>
                        <td>{{ p.sku }}</td>
                        <td class="text-capitalize">{{ p.unit || p.base_unit }}</td>
                        <td class="text-end">{{ formatMoney(p.sale_price) }}</td>
                        <td>
                            <span v-if="p.batches?.length">
                                {{ p.batches.reduce((s, b) => s + Number(b.quantity_on_hand), 0) }}
                            </span>
                            <span v-else>0</span>
                        </td>
                        <td>
                            <span class="badge" :class="p.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ p.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <a :href="`/barcodes/${p.id}`" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary me-1">Barcode</a>
                            <Link v-if="can('products.manage')" :href="`/products/${p.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">Edit</Link>
                            <button
                                v-if="can('products.manage')"
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                @click="confirmDelete(p)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!products.data?.length">
                        <td colspan="9" class="text-muted text-center py-4">No products found.</td>
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
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    productTypes: { type: Array, default: () => [] },
});

const { formatMoney, currencyCode } = useMoney();
const { can } = usePermissions();

const filterForm = reactive({
    q: props.filters.q ?? '',
    product_type: props.filters.product_type ?? '',
    is_active: props.filters.is_active ?? '',
});

function applyFilters() {
    router.get('/products', { ...filterForm }, { preserveState: true, replace: true });
}

function clearFilters() {
    filterForm.q = '';
    filterForm.product_type = '';
    filterForm.is_active = '';
    applyFilters();
}

function confirmDelete(product) {
    if (!window.confirm(`Delete product "${product.name}"? This cannot be undone.`)) {
        return;
    }
    router.delete(`/products/${product.id}`, { preserveScroll: true });
}
</script>
