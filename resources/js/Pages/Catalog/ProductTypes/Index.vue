<template>
    <TenantShellLayout :page-title="tr('tenant_nav.product_types')">
        <Head :title="tr('tenant_nav.product_types')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.errors?.product_type" class="alert alert-danger small">
            {{ $page.props.errors.product_type }}
        </div>
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">{{ tr('tenant_nav.product_types') }}</h1>
            <Link href="/product-types/create" class="btn btn-primary btn-sm">{{ tr('catalog.add_product_type') }}</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ tr('common.name') }}</th>
                        <th>{{ tr('common.slug') }}</th>
                        <th class="text-end">{{ tr('catalog.sort_order') }}</th>
                        <th class="text-end">{{ tr('tenant_nav.products') }}</th>
                        <th class="text-end">{{ tr('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in productTypes.data" :key="t.id">
                        <td>
                            <ProductTypeLabel :type="t.slug" :label="t.name" :icon-url="t.icon_url" size="sm" />
                        </td>
                        <td><code>{{ t.slug }}</code></td>
                        <td class="text-end">{{ t.sort_order }}</td>
                        <td class="text-end">
                            <Link
                                v-if="t.products_count > 0"
                                :href="`/products?product_type=${t.slug}`"
                                class="badge text-bg-light border text-decoration-none"
                                :title="tr('catalog.product_type_view_products')"
                            >
                                {{ t.products_count }}
                            </Link>
                            <span v-else class="text-muted">0</span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center justify-content-end gap-2">
                                <span v-if="t.products_count > 0" class="small text-muted">
                                    {{ tr('catalog.product_type_delete_blocked') }}
                                </span>
                                <Link :href="`/product-types/${t.id}/edit`" class="btn btn-sm btn-outline-secondary">{{ tr('common.edit') }}</Link>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :disabled="t.products_count > 0"
                                :title="t.products_count > 0 ? tr('catalog.product_type_delete_blocked') : ''"
                                @click="remove(t)"
                            >
                                {{ tr('common.delete') }}
                            </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!productTypes.data?.length">
                        <td colspan="5" class="text-muted text-center py-3">{{ tr('catalog.no_product_types') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import ProductTypeLabel from '@/Components/Catalog/ProductTypeLabel.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ productTypes: { type: Object, required: true } });

const { t: tr } = useLocale();

function remove(type) {
    if (type.products_count > 0) return;
    if (!window.confirm(tr('catalog.product_type_delete_confirm', { name: type.name }))) return;
    router.delete(`/product-types/${type.id}`);
}
</script>
