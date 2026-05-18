<template>
    <PlatformShellLayout :page-title="t('platform.resellers_title')">
        <Head :title="t('platform.nav_resellers')" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted small mb-0">{{ t('platform.resellers_sub') }}</p>
            <Link href="/platform/resellers/create" class="btn btn-primary btn-sm">{{ t('platform.new_reseller') }}</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('platform.reseller_name') }}</th>
                        <th>Slug</th>
                        <th>{{ t('platform.reseller_tenants') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in resellers" :key="r.id">
                        <td>{{ r.name }}</td>
                        <td><code>{{ r.slug }}</code></td>
                        <td>{{ r.tenants_count }}</td>
                        <td>
                            <span class="badge" :class="r.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ r.is_active ? t('common.active') : t('common.inactive') }}
                            </span>
                        </td>
                        <td class="text-end">
                            <Link :href="`/platform/resellers/${r.id}/edit`" class="btn btn-sm btn-outline-primary me-1">
                                {{ t('common.edit') }}
                            </Link>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :disabled="r.tenants_count > 0"
                                @click="destroy(r)"
                            >
                                {{ t('common.delete') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!resellers.length">
                        <td colspan="5" class="text-muted text-center py-4">{{ t('common.no_results') }}</td>
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

defineProps({ resellers: { type: Array, required: true } });

const { t } = useLocale();

function destroy(reseller) {
    if (!confirm(t('platform.reseller_delete_confirm', { name: reseller.name }))) {
        return;
    }

    router.delete(`/platform/resellers/${reseller.id}`);
}
</script>
