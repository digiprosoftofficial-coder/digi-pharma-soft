<template>
    <TenantShellLayout :page-title="t('catalog.storage_locations_title')">
        <Head :title="t('catalog.storage_locations_title')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.errors?.storage_location" class="alert alert-danger small">
            {{ $page.props.errors.storage_location }}
        </div>
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0 d-lg-none">{{ t('catalog.storage_locations_title') }}</h1>
            <Link href="/storage-locations/create" class="btn btn-primary btn-sm">{{ t('catalog.new_storage_location') }}</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('catalog.storage_location_name') }}</th>
                        <th>{{ t('catalog.storage_location_code') }}</th>
                        <th class="text-end">{{ t('catalog.storage_location_products') }}</th>
                        <th class="text-end">{{ t('catalog.storage_location_batches') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="loc in locations.data" :key="loc.id">
                        <td>{{ loc.name }}</td>
                        <td><code v-if="loc.code">{{ loc.code }}</code><span v-else class="text-muted">—</span></td>
                        <td class="text-end">
                            <Link
                                v-if="loc.products_count > 0"
                                :href="`/products?storage_location_id=${loc.id}`"
                                class="badge text-bg-light border text-decoration-none"
                            >
                                {{ loc.products_count }}
                            </Link>
                            <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-end">
                            <span v-if="loc.batches_count > 0" class="badge text-bg-light border">
                                {{ loc.batches_count }}
                            </span>
                            <span v-else class="text-muted">0</span>
                        </td>
                        <td>
                            <span class="badge" :class="loc.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ loc.is_active ? t('common.active') : t('common.inactive') }}
                            </span>
                        </td>
                        <td class="text-end">
                            <Link :href="`/storage-locations/${loc.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">
                                {{ t('common.edit') }}
                            </Link>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :disabled="locationUsageCount(loc) > 0"
                                :title="locationUsageCount(loc) > 0 ? t('catalog.storage_location_delete_blocked') : ''"
                                @click="remove(loc)"
                            >
                                {{ t('common.delete') }}
                            </button>
                            <div v-if="locationUsageCount(loc) > 0" class="small text-muted mt-1">
                                {{ t('catalog.storage_location_delete_blocked') }}
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!locations.data?.length">
                        <td colspan="6" class="text-muted text-center py-3">{{ t('catalog.storage_locations_empty') }}</td>
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

defineProps({ locations: { type: Object, required: true } });

const { t } = useLocale();

function locationUsageCount(location) {
    return Number(location.products_count ?? 0) + Number(location.batches_count ?? 0);
}

function remove(location) {
    if (locationUsageCount(location) > 0) {
        return;
    }
    if (!window.confirm(t('catalog.storage_location_delete_confirm', { name: location.name }))) {
        return;
    }

    router.delete(`/storage-locations/${location.id}`);
}
</script>
