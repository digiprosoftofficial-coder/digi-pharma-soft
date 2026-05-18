<template>
    <TenantShellLayout page-title="Categories">
        <Head title="Categories" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">Categories</h1>
            <Link href="/categories/create" class="btn btn-primary btn-sm">Add category</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th class="text-end">Products</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in categories.data" :key="c.id">
                        <td>{{ c.name }}</td>
                        <td><code>{{ c.slug }}</code></td>
                        <td class="text-end">{{ c.products_count }}</td>
                        <td class="text-end">
                            <Link :href="`/categories/${c.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">Edit</Link>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(c)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!categories.data?.length">
                        <td colspan="4" class="text-muted text-center py-3">No categories yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ categories: { type: Object, required: true } });

function remove(category) {
    if (!window.confirm(`Delete category "${category.name}"?`)) return;
    router.delete(`/categories/${category.id}`);
}
</script>
