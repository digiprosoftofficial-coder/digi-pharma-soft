<template>
    <TenantShellLayout :page-title="t('tenant_nav.categories')">
        <Head :title="t('tenant_nav.categories')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.errors?.category" class="alert alert-danger small">
            {{ $page.props.errors.category }}
        </div>
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">{{ t('tenant_nav.categories') }}</h1>
            <Link href="/categories/create" class="btn btn-primary btn-sm">{{ t('catalog.add_category') }}</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('common.name') }}</th>
                        <th>{{ t('common.slug') }}</th>
                        <th class="text-end">{{ t('tenant_nav.products') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in categories.data" :key="c.id">
                        <td>{{ c.name }}</td>
                        <td><code>{{ c.slug }}</code></td>
                        <td class="text-end">
                            <Link
                                v-if="c.products_count > 0"
                                :href="`/products?category_id=${c.id}`"
                                class="badge text-bg-light border text-decoration-none"
                                :title="t('catalog.category_view_products')"
                            >
                                {{ c.products_count }}
                            </Link>
                            <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-end">
                            <Link :href="`/categories/${c.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">{{ t('common.edit') }}</Link>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :disabled="c.products_count > 0"
                                :title="c.products_count > 0 ? t('catalog.category_delete_blocked') : ''"
                                @click="remove(c)"
                            >
                                {{ t('common.delete') }}
                            </button>
                            <div v-if="c.products_count > 0" class="small text-muted mt-1">
                                {{ t('catalog.category_delete_blocked') }}
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!categories.data?.length">
                        <td colspan="4" class="text-muted text-center py-3">{{ t('catalog.no_categories') }}</td>
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

defineProps({ categories: { type: Object, required: true } });

const { t } = useLocale();

function remove(category) {
    if (category.products_count > 0) return;
    if (!window.confirm(t('catalog.category_delete_confirm', { name: category.name }))) return;
    router.delete(`/categories/${category.id}`);
}
</script>
