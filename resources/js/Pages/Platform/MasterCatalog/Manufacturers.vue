<template>
    <PlatformShellLayout :page-title="t('platform.master_makers_title')">
        <Head :title="t('platform.master_makers_title')" />


        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <Link href="/platform/master-catalog" class="small text-decoration-none">← {{ t('platform.master_title') }}</Link>
                <h1 class="page-heading d-none d-lg-block mb-1 mt-1">{{ t('platform.master_makers_title') }}</h1>
                <p class="text-muted small mb-0">
                    {{ t('platform.master_makers_sub', { count: String(totalManufacturers) }) }}
                </p>
            </div>
        </div>

        <form class="card border-0 shadow-sm mb-3" @submit.prevent="applyFilters">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col">
                        <label class="form-label small mb-1">{{ t('platform.master_makers_search') }}</label>
                        <input
                            v-model="local.q"
                            type="search"
                            class="form-control"
                            :placeholder="t('platform.master_makers_search_placeholder')"
                            autocomplete="off"
                            @input="debouncedApplyFilters"
                        />
                    </div>
                    <div class="col-auto">
                        <button
                            type="button"
                            class="btn btn-outline-secondary product-filter-reset"
                            :title="t('platform.master_clear')"
                            :aria-label="t('platform.master_clear')"
                            @click="clearFilters"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 12a9 9 0 1 0 3-6.7" />
                                <polyline points="3 4 3 9 8 9" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 master-table">
                    <thead>
                        <tr>
                            <th>{{ t('platform.master_manufacturer') }}</th>
                            <th class="text-end">{{ t('platform.master_makers_medicines') }}</th>
                            <th class="text-end">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in manufacturers.data" :key="item.name">
                            <td class="fw-semibold">{{ item.name }}</td>
                            <td class="text-end">{{ item.medicines_count }}</td>
                            <td class="text-end">
                                <Link
                                    :href="catalogHref(item.name)"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    {{ t('platform.master_makers_view_medicines') }}
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!manufacturers.data?.length">
                            <td colspan="3" class="text-center text-muted py-5">
                                {{ t('platform.master_makers_empty') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div
                v-if="manufacturers.total > 0"
                class="card-footer bg-white d-flex flex-column flex-lg-row flex-wrap justify-content-between align-items-lg-center gap-3"
            >
                <span class="small text-muted">
                    {{ t('platform.master_showing', {
                        from: String(manufacturers.from ?? 0),
                        to: String(manufacturers.to ?? 0),
                        total: String(manufacturers.total ?? 0),
                    }) }}
                </span>
                <nav v-if="manufacturers.links?.length > 3" aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0 flex-wrap justify-content-lg-end">
                        <li
                            v-for="(link, index) in manufacturers.links"
                            :key="`${index}-${link.label}`"
                            class="page-item"
                            :class="{ active: link.active, disabled: !link.url }"
                        >
                            <Link
                                v-if="link.url"
                                class="page-link"
                                :href="link.url"
                                preserve-scroll
                                v-html="link.label"
                            />
                            <span v-else class="page-link" v-html="link.label" />
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router } from '@inertiajs/vue3';
import { onUnmounted, reactive } from 'vue';

const props = defineProps({
    manufacturers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    totalManufacturers: { type: Number, default: 0 },
});

const { t } = useLocale();
let filterTimer = null;

const local = reactive({
    q: props.filters.q ?? '',
});

function catalogHref(name) {
    return `/platform/master-catalog?manufacturer=${encodeURIComponent(name)}`;
}

function debouncedApplyFilters() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => applyFilters(), 350);
}

function applyFilters() {
    clearTimeout(filterTimer);
    router.get('/platform/master-catalog/manufacturers', {
        q: local.q || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['manufacturers', 'filters'],
    });
}

function clearFilters() {
    local.q = '';
    applyFilters();
}

onUnmounted(() => {
    clearTimeout(filterTimer);
});
</script>

<style scoped>
.product-filter-reset {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.4rem;
    height: 2.4rem;
    padding: 0;
}

.master-table thead th {
    background: #f8fafc;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    border-bottom-width: 1px;
}
</style>
