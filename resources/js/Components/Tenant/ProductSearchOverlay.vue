<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="product-search-overlay"
            role="dialog"
            aria-modal="true"
            :aria-label="t('common.smart_search_title')"
        >
            <div class="product-search-overlay__backdrop" @click="close"></div>
            <div class="product-search-overlay__sheet">
                <div class="product-search-overlay__header">
                    <button type="button" class="btn btn-sm btn-light" :aria-label="t('common.back')" @click="close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <div class="product-search-overlay__title">
                        <span class="product-search-overlay__badge">{{ t('common.search_scope_products') }}</span>
                        <strong>{{ t('common.smart_search_title') }}</strong>
                    </div>
                </div>

                <form class="product-search-overlay__form" @submit.prevent="onSearchEnter({ onDone: close })">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                        </span>
                        <input
                            ref="inputEl"
                            v-model="searchQ"
                            type="search"
                            class="form-control"
                            :placeholder="t('common.search_products_placeholder')"
                            autocomplete="off"
                            @input="debouncedProductSearch"
                            @keydown.down.prevent="moveSearchHighlight(1)"
                            @keydown.up.prevent="moveSearchHighlight(-1)"
                            @keydown.enter.prevent="onSearchEnter({ onDone: close })"
                            @keydown.esc.prevent="close"
                        />
                    </div>
                    <p class="product-search-overlay__hint small text-muted mb-0">
                        {{ t('common.search_enter_hint') }}
                    </p>
                </form>

                <div class="product-search-overlay__results">
                    <div v-if="searchLoading" class="px-3 py-3 small text-muted">{{ t('common.searching') }}</div>
                    <template v-else-if="searchResults.length">
                        <button
                            v-for="(product, index) in searchResults"
                            :key="product.id"
                            type="button"
                            class="product-search-overlay__item"
                            :class="{ 'product-search-overlay__item--active': index === highlightedSearchIndex }"
                            @click="selectSearchResult(product, { onDone: close })"
                        >
                            <span class="d-block fw-semibold text-truncate">{{ product.name }}</span>
                            <span class="d-flex flex-wrap gap-2 small text-muted">
                                <span v-if="product.sku">{{ product.sku }}</span>
                                <span v-if="product.barcode">{{ product.barcode }}</span>
                                <span>{{ t('common.stock') }}: {{ product.stock_on_hand ?? '0' }}</span>
                            </span>
                            <span class="product-search-overlay__action small text-primary">{{ t('common.open_product') }}</span>
                        </button>
                        <button
                            type="button"
                            class="product-search-overlay__item product-search-overlay__item--all"
                            @click="runSearch({ onDone: close })"
                        >
                            {{ t('common.view_all_matching_products') }}
                        </button>
                    </template>
                    <div v-else-if="searchQ.trim().length >= 1" class="px-3 py-3 small text-muted">{{ t('common.no_results') }}</div>
                    <div v-else class="px-3 py-4 small text-muted text-center">{{ t('common.smart_search_empty') }}</div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { useProductSearch } from '@/composables/useProductSearch';
import { nextTick, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open']);

const { t } = useLocale();
const inputEl = ref(null);

const {
    searchQ,
    searchResults,
    searchLoading,
    highlightedSearchIndex,
    debouncedProductSearch,
    moveSearchHighlight,
    onSearchEnter,
    selectSearchResult,
    runSearch,
    resetSearch,
} = useProductSearch();

function close() {
    emit('update:open', false);
}

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            await nextTick();
            inputEl.value?.focus();
            return;
        }
        resetSearch();
    },
);
</script>

<style scoped>
.product-search-overlay {
    position: fixed;
    inset: 0;
    z-index: 1080;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}

.product-search-overlay__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
}

.product-search-overlay__sheet {
    position: relative;
    display: flex;
    flex-direction: column;
    max-height: min(92vh, 40rem);
    background: #fff;
    border-radius: 1rem 1rem 0 0;
    box-shadow: 0 -0.5rem 2rem rgba(15, 23, 42, 0.18);
    padding-bottom: env(safe-area-inset-bottom, 0px);
}

.product-search-overlay__header {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.85rem 1rem 0.35rem;
}

.product-search-overlay__title {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.product-search-overlay__badge {
    display: inline-flex;
    align-self: flex-start;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: none;
}

.product-search-overlay__form {
    padding: 0.5rem 1rem 0.75rem;
}

.product-search-overlay__hint {
    margin-top: 0.45rem;
}

.product-search-overlay__results {
    flex: 1 1 auto;
    overflow-y: auto;
    border-top: 1px solid var(--bs-border-color, #e2e8f0);
}

.product-search-overlay__item {
    display: block;
    width: 100%;
    padding: 0.85rem 1rem;
    border: 0;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
    text-align: left;
}

.product-search-overlay__item--active,
.product-search-overlay__item:hover {
    background: #f8fafc;
}

.product-search-overlay__action {
    display: inline-block;
    margin-top: 0.25rem;
}

.product-search-overlay__item--all {
    background: #f8fafc;
    color: var(--bs-primary);
    font-weight: 600;
}
</style>
