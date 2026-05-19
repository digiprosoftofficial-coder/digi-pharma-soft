<template>
    <TenantShellLayout page-title="Product types">
        <Head title="Product types" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">Product types</h1>
            <Link href="/product-types/create" class="btn btn-primary btn-sm">Add product type</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th class="text-end">Sort</th>
                        <th class="text-end">Products</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in productTypes.data" :key="t.id">
                        <td>{{ t.name }}</td>
                        <td><code>{{ t.slug }}</code></td>
                        <td class="text-end">{{ t.sort_order }}</td>
                        <td class="text-end">{{ t.products_count }}</td>
                        <td class="text-end">
                            <Link :href="`/product-types/${t.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">Edit</Link>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(t)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!productTypes.data?.length">
                        <td colspan="5" class="text-muted text-center py-3">No product types yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ productTypes: { type: Object, required: true } });

function remove(type) {
    if (!window.confirm(`Delete product type "${type.name}"?`)) return;
    router.delete(`/product-types/${type.id}`);
}
</script>
