<template>
    <PlatformShellLayout :page-title="t('platform.product_types_title')">
        <Head :title="t('platform.product_types_title')" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <p class="text-muted small mb-0">{{ t('platform.product_types_sub') }}</p>
            </div>
            <Link href="/platform/product-types/create" class="btn btn-primary btn-sm">{{ t('platform.product_type_new') }}</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 48px"></th>
                        <th>{{ t('platform.product_type_name') }}</th>
                        <th>Slug</th>
                        <th class="text-end">{{ t('platform.product_type_sort') }}</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="pt in productTypes" :key="pt.id">
                        <td>
                            <img v-if="pt.icon_url" :src="pt.icon_url" alt="" width="32" height="32" class="object-fit-contain" />
                            <span v-else class="text-muted small">—</span>
                        </td>
                        <td>{{ pt.name }}</td>
                        <td><code>{{ pt.slug }}</code></td>
                        <td class="text-end">{{ pt.sort_order }}</td>
                        <td>
                            <span class="badge" :class="pt.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ pt.is_active ? t('common.active') : t('common.inactive') }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <Link :href="`/platform/product-types/${pt.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">
                                {{ t('common.edit') }}
                            </Link>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(pt)">{{ t('common.delete') }}</button>
                        </td>
                    </tr>
                    <tr v-if="!productTypes?.length">
                        <td colspan="6" class="text-muted text-center py-3">{{ t('platform.product_types_empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    productTypes: { type: Array, default: () => [] },
});

const { t } = useLocale();

function remove(pt) {
    if (!window.confirm(t('platform.product_type_delete_confirm', { name: pt.name }))) {
        return;
    }
    router.delete(`/platform/product-types/${pt.id}`);
}
</script>
