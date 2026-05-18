<template>
    <TenantShellLayout page-title="Manufacturers">
        <Head title="Manufacturers" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">Manufacturers</h1>
            <Link href="/manufacturers/create" class="btn btn-primary btn-sm">Add manufacturer</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th class="text-end">Products</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="m in manufacturers.data" :key="m.id">
                        <td>{{ m.name }}</td>
                        <td class="text-end">{{ m.products_count }}</td>
                        <td class="text-end">
                            <Link :href="`/manufacturers/${m.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">Edit</Link>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(m)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!manufacturers.data?.length">
                        <td colspan="3" class="text-muted text-center py-3">No manufacturers yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ manufacturers: { type: Object, required: true } });

function remove(manufacturer) {
    if (!window.confirm(`Delete manufacturer "${manufacturer.name}"?`)) return;
    router.delete(`/manufacturers/${manufacturer.id}`);
}
</script>
