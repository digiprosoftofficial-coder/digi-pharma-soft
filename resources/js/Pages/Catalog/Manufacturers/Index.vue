<template>
    <TenantShellLayout page-title="Manufacturers">
        <Head title="Manufacturers" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.errors?.manufacturer" class="alert alert-danger small">
            {{ $page.props.errors.manufacturer }}
        </div>
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
                        <td class="text-end">
                            <span v-if="m.products_count > 0" class="badge text-bg-light border">
                                {{ m.products_count }}
                            </span>
                            <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-end">
                            <Link :href="`/manufacturers/${m.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">Edit</Link>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :disabled="m.products_count > 0"
                                :title="m.products_count > 0 ? t('catalog.manufacturer_delete_blocked') : ''"
                                @click="remove(m)"
                            >
                                Delete
                            </button>
                            <div v-if="m.products_count > 0" class="small text-muted mt-1">
                                {{ t('catalog.manufacturer_delete_blocked') }}
                            </div>
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
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ manufacturers: { type: Object, required: true } });

const { t } = useLocale();

function remove(manufacturer) {
    if (manufacturer.products_count > 0) return;
    if (!window.confirm(`Delete manufacturer "${manufacturer.name}"?`)) return;
    router.delete(`/manufacturers/${manufacturer.id}`);
}
</script>
