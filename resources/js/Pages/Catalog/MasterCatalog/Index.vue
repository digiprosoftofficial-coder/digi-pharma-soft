<template>
    <TenantShellLayout :page-title="t('catalog.master_catalog_title')">
        <Head :title="t('catalog.master_catalog_title')" />

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">{{ t('catalog.master_catalog_title') }}</h1>
                <p class="text-muted small mb-0">{{ t('catalog.master_catalog_hint') }}</p>
            </div>
            <Link href="/products" class="btn btn-outline-secondary btn-sm">{{ t('tenant_nav.product_list', 'All Products') }}</Link>
        </div>

        <div class="card border-0 shadow-sm card-body mb-3">
            <label class="form-label small mb-1">{{ t('catalog.master_catalog_search') }}</label>
            <input
                v-model="q"
                type="search"
                class="form-control"
                :placeholder="t('catalog.master_catalog_search_placeholder')"
                autocomplete="off"
                @input="debouncedSearch"
            />
            <p class="form-text small mb-0">
                {{ t('catalog.master_catalog_total', { count: String(totalCount) }) }}
            </p>
        </div>

        <div v-if="loading" class="text-muted small mb-2">{{ t('common.searching') }}</div>

        <div class="table-responsive card border-0 shadow-sm">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('catalog.product_name') }}</th>
                        <th class="d-none d-md-table-cell">{{ t('catalog.master_catalog_generic') }}</th>
                        <th class="d-none d-lg-table-cell">{{ t('catalog.master_catalog_manufacturer') }}</th>
                        <th class="text-end">{{ t('catalog.master_catalog_mrp') }}</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in results" :key="item.id">
                        <td>
                            <div class="fw-medium">{{ item.name }}</div>
                            <div class="small text-muted">
                                <span v-if="item.strength">{{ item.strength }}</span>
                                <span v-if="item.strength && item.product_type"> · </span>
                                <span v-if="item.product_type">{{ typeLabel(item.product_type) }}</span>
                            </div>
                            <div class="small text-muted d-md-none">{{ item.generic_name }}</div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <span v-if="item.generic_name">{{ item.generic_name }}</span>
                            <span v-else class="text-muted">—</span>
                            <div v-if="item.drug_class" class="small text-muted">{{ item.drug_class }}</div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <span v-if="item.manufacturer_name">{{ item.manufacturer_name }}</span>
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td class="text-end">{{ formatMoney(item.mrp) }}</td>
                        <td class="text-end text-nowrap">
                            <Link
                                v-if="item.is_activated && item.tenant_product_id"
                                :href="`/products/${item.tenant_product_id}`"
                                class="btn btn-sm btn-outline-success"
                            >
                                {{ t('catalog.master_catalog_added') }}
                            </Link>
                            <button
                                v-else-if="canManage"
                                type="button"
                                class="btn btn-sm btn-primary"
                                :disabled="activatingId === item.id"
                                @click="addToShop(item)"
                            >
                                {{ activatingId === item.id ? t('common.saving') : t('catalog.master_catalog_add') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!results.length && !loading">
                        <td colspan="5" class="text-muted text-center py-4">{{ t('catalog.master_catalog_empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    initialResults: { type: Array, default: () => [] },
    totalCount: { type: Number, default: 0 },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();

const canManage = can('products.manage');
const q = ref('');
const results = ref([...props.initialResults]);
const loading = ref(false);
const activatingId = ref(null);
let timer;

function typeLabel(type) {
    return t(`catalog.types.${type}`, type);
}

function debouncedSearch() {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 250);
}

async function runSearch() {
    loading.value = true;
    try {
        const { data } = await window.axios.get('/catalog/master/search', { params: { q: q.value } });
        results.value = data.data ?? [];
    } finally {
        loading.value = false;
    }
}

async function addToShop(item) {
    if (activatingId.value) {
        return;
    }
    activatingId.value = item.id;
    try {
        const { data } = await window.axios.post(`/catalog/master/${item.id}/activate`);
        if (data?.ok) {
            item.is_activated = true;
            item.tenant_product_id = data.product_id;
        }
    } finally {
        activatingId.value = null;
    }
}
</script>
