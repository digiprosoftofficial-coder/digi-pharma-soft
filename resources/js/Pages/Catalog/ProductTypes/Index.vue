<template>
    <TenantShellLayout :page-title="tr('tenant_nav.product_types')">
        <Head :title="tr('tenant_nav.product_types')" />
        <div class="product-types-page">
            <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
            <div v-if="$page.props.errors?.product_type" class="alert alert-danger small">
                {{ $page.props.errors.product_type }}
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 product-types-header">
                <h1 class="h4 mb-0 product-types-title">{{ tr('tenant_nav.product_types') }}</h1>
                <Link href="/product-types/create" class="btn btn-primary btn-sm product-types-add">
                    {{ tr('catalog.add_product_type') }}
                </Link>
            </div>

            <div class="product-types-mobile d-md-none">
                <div v-if="!productTypes.data?.length" class="card border-0 shadow-sm card-body text-muted text-center small">
                    {{ tr('catalog.no_product_types') }}
                </div>
                <div
                    v-for="t in productTypes.data"
                    :key="t.id"
                    class="card border-0 shadow-sm mb-2 product-type-card"
                >
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="min-w-0 flex-grow-1">
                                <ProductTypeLabel
                                    class="product-type-card__label"
                                    :type="t.slug"
                                    :label="t.name"
                                    :icon-url="t.icon_url"
                                    size="sm"
                                />
                                <div class="small text-muted text-truncate mt-1">
                                    <code>{{ t.slug }}</code>
                                </div>
                            </div>
                            <span class="badge text-bg-light border flex-shrink-0">
                                {{ tr('catalog.sort_order') }}: {{ t.sort_order }}
                            </span>
                        </div>

                        <div class="product-type-card__meta mb-2">
                            <span class="text-muted">{{ tr('tenant_nav.products') }}</span>
                            <Link
                                v-if="t.products_count > 0"
                                :href="`/products?product_type=${t.slug}`"
                                class="badge text-bg-light border text-decoration-none"
                                :title="tr('catalog.product_type_view_products')"
                            >
                                {{ t.products_count }}
                            </Link>
                            <span v-else class="fw-semibold">0</span>
                        </div>

                        <p v-if="t.products_count > 0" class="small text-muted mb-2">
                            {{ tr('catalog.product_type_delete_blocked') }}
                        </p>

                        <div class="product-type-card__actions">
                            <Link :href="`/product-types/${t.id}/edit`" class="btn btn-sm btn-outline-secondary">
                                {{ tr('common.edit') }}
                            </Link>
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
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm d-none d-md-block">
                <div class="table-responsive product-types-table">
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
                                    <div class="d-inline-flex align-items-center justify-content-end gap-2 flex-wrap">
                                        <span v-if="t.products_count > 0" class="small text-muted">
                                            {{ tr('catalog.product_type_delete_blocked') }}
                                        </span>
                                        <Link :href="`/product-types/${t.id}/edit`" class="btn btn-sm btn-outline-secondary">
                                            {{ tr('common.edit') }}
                                        </Link>
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
            </div>
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

<style scoped>
.product-types-page {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: clip;
}

.product-types-title {
    min-width: 0;
}

.product-types-table table {
    min-width: 0;
}

@media (min-width: 768px) {
    .product-types-table table {
        min-width: 640px;
    }
}

.product-type-card {
    max-width: 100%;
    min-width: 0;
}

.product-type-card__label {
    max-width: 100%;
    min-width: 0;
}

.product-type-card__label :deep(span:last-child) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.product-type-card__meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.55rem 0.65rem;
    background: #f8f9fa;
    border: 1px solid #eef0f2;
    border-radius: 0.6rem;
}

.product-type-card__actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

@media (max-width: 767.98px) {
    .product-types-header {
        align-items: stretch !important;
    }

    .product-types-title {
        width: 100%;
    }

    .product-types-add {
        width: 100%;
        min-height: 2.25rem;
    }

    .product-type-card .card-body {
        padding: 0.85rem !important;
    }

    .product-type-card__actions .btn {
        width: 100%;
        min-height: 2.15rem;
        font-size: 0.8rem;
    }
}
</style>
