<template>
    <PlatformShellLayout :page-title="template.name">
        <Head :title="template.name" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <Link href="/platform/catalog-templates" class="small text-decoration-none">← {{ t('platform.nav_catalog') }}</Link>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-1 mb-3">
            <div>
                <h1 class="h4 mb-0">{{ template.name }}</h1>
                <code class="small">{{ template.slug }}</code>
                <span class="badge ms-2" :class="template.is_published ? 'text-bg-success' : 'text-bg-secondary'">
                    {{ template.is_published ? t('platform.catalog_published') : t('platform.catalog_draft') }}
                </span>
            </div>
            <Link :href="`/platform/catalog-templates/${template.id}/edit`" class="btn btn-sm btn-outline-primary">
                {{ t('common.edit') }}
            </Link>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ t('platform.catalog_products') }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ t('platform.catalog_item_name') }}</th>
                                    <th>SKU</th>
                                    <th class="text-end">{{ t('platform.catalog_sale_price') }} ({{ currencyCode() }})</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in template.items" :key="item.id">
                                    <td>{{ item.name }}</td>
                                    <td><code>{{ item.sku }}</code></td>
                                    <td class="text-end">{{ formatMoney(item.sale_price) }}</td>
                                    <td class="text-end">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            @click="removeItem(item)"
                                        >
                                            {{ t('common.delete') }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!template.items?.length">
                                    <td colspan="4" class="text-muted text-center py-3">{{ t('common.no_results') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <form class="card border-0 shadow-sm card-body mt-3" @submit.prevent="addItem">
                    <h2 class="h6">{{ t('platform.catalog_add_item') }}</h2>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">{{ t('platform.catalog_item_name') }}</label>
                            <input v-model="itemForm.name" class="form-control" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU</label>
                            <input v-model="itemForm.sku" class="form-control" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ t('platform.catalog_purchase_price') }} ({{ currencyCode() }})</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currencySymbol() }}</span>
                                <input v-model.number="itemForm.purchase_price" type="number" min="0" step="0.01" class="form-control" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ t('platform.catalog_sale_price') }} ({{ currencyCode() }})</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currencySymbol() }}</span>
                                <input v-model.number="itemForm.sale_price" type="number" min="0" step="0.01" class="form-control" required />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ t('platform.catalog_unit') }}</label>
                            <input v-model="itemForm.unit" class="form-control" />
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-2" :disabled="itemForm.processing">
                        {{ t('platform.catalog_add_item') }}
                    </button>
                </form>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm card-body">
                    <h2 class="h6">{{ t('platform.catalog_apply_title') }}</h2>
                    <p class="small text-muted">{{ t('platform.catalog_apply_help') }}</p>
                    <form @submit.prevent="applyToTenant">
                        <label class="form-label">{{ t('platform.nav_pharmacies') }}</label>
                        <select v-model="applyForm.tenant_id" class="form-select mb-2" required>
                            <option :value="null" disabled>{{ t('platform.catalog_select_tenant') }}</option>
                            <option v-for="tn in tenants" :key="tn.id" :value="tn.id">{{ tn.name }}</option>
                        </select>
                        <button
                            type="submit"
                            class="btn btn-primary btn-sm"
                            :disabled="applyForm.processing || !template.is_published"
                        >
                            {{ t('platform.catalog_apply') }}
                        </button>
                        <p v-if="!template.is_published" class="small text-warning mt-2 mb-0">
                            {{ t('platform.catalog_publish_to_apply') }}
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    template: { type: Object, required: true },
    tenants: { type: Array, default: () => [] },
});

const { t } = useLocale();
const { formatMoney, currencyCode, currencySymbol } = useMoney();

const itemForm = useForm({
    name: '',
    sku: '',
    barcode: '',
    unit: 'pcs',
    generic_name: '',
    manufacturer_name: '',
    purchase_price: 0,
    sale_price: 0,
});

const applyForm = useForm({
    tenant_id: null,
});

function addItem() {
    itemForm.post(`/platform/catalog-templates/${props.template.id}/items`, {
        preserveScroll: true,
        onSuccess: () => itemForm.reset('name', 'sku', 'barcode', 'generic_name', 'manufacturer_name'),
    });
}

function removeItem(item) {
    if (!confirm(t('platform.catalog_item_delete_confirm'))) {
        return;
    }

    router.delete(`/platform/catalog-templates/${props.template.id}/items/${item.id}`, {
        preserveScroll: true,
    });
}

function applyToTenant() {
    applyForm.post(`/platform/catalog-templates/${props.template.id}/apply`, {
        preserveScroll: true,
    });
}
</script>
