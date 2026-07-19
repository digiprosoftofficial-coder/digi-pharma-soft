import { router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

/**
 * Shared product search for topbar, mobile overlay, and smart-search FAB.
 * Behavior: pick a result → product detail; submit without a pick → product list.
 */
export function useProductSearch() {
    const searchQ = ref('');
    const searchResults = ref([]);
    const searchLoading = ref(false);
    const searchOpen = ref(false);
    const highlightedSearchIndex = ref(-1);
    let searchTimer;
    let closeSearchTimer;

    const showSearchDropdown = computed(() =>
        searchOpen.value
        && (searchLoading.value || searchResults.value.length > 0 || searchQ.value.trim().length >= 1),
    );

    function debouncedProductSearch() {
        clearTimeout(searchTimer);
        highlightedSearchIndex.value = -1;
        searchTimer = setTimeout(runProductSuggestSearch, 250);
    }

    async function runProductSuggestSearch() {
        const q = searchQ.value.trim();
        if (q.length < 1) {
            searchResults.value = [];
            searchLoading.value = false;
            highlightedSearchIndex.value = -1;
            return;
        }

        searchOpen.value = true;
        searchLoading.value = true;
        try {
            const { data } = await window.axios.get('/catalog/product-search', { params: { q } });
            searchResults.value = (data.data ?? []).slice(0, 6);
            highlightedSearchIndex.value = searchResults.value.length ? 0 : -1;
        } catch {
            searchResults.value = [];
            highlightedSearchIndex.value = -1;
        } finally {
            searchLoading.value = false;
        }
    }

    function openSearchSuggestions() {
        clearTimeout(closeSearchTimer);
        if (searchQ.value.trim().length >= 1) {
            searchOpen.value = true;
            if (!searchResults.value.length) {
                debouncedProductSearch();
            }
        }
    }

    function closeSearchSuggestionsSoon() {
        clearTimeout(closeSearchTimer);
        closeSearchTimer = setTimeout(closeSearchSuggestions, 150);
    }

    function closeSearchSuggestions() {
        searchOpen.value = false;
        highlightedSearchIndex.value = -1;
    }

    function moveSearchHighlight(direction) {
        if (!searchResults.value.length) {
            return;
        }

        searchOpen.value = true;
        highlightedSearchIndex.value = (highlightedSearchIndex.value + direction + searchResults.value.length) % searchResults.value.length;
    }

    function selectSearchResult(product, { onDone } = {}) {
        if (!product?.id) {
            return;
        }

        searchQ.value = product.name ?? '';
        closeSearchSuggestions();
        onDone?.();
        router.visit(`/products/${product.id}`);
    }

    function runSearch({ onDone } = {}) {
        const q = searchQ.value?.trim();
        closeSearchSuggestions();
        onDone?.();
        if (!q) {
            router.visit('/products');
            return;
        }
        router.visit('/products', { data: { q }, preserveState: true });
    }

    /**
     * Enter: open highlighted (or sole) product; otherwise go to product list.
     */
    function onSearchEnter({ onDone } = {}) {
        if (searchResults.value.length === 1) {
            selectSearchResult(searchResults.value[0], { onDone });
            return;
        }

        if (searchOpen.value && highlightedSearchIndex.value >= 0 && searchResults.value[highlightedSearchIndex.value]) {
            selectSearchResult(searchResults.value[highlightedSearchIndex.value], { onDone });
            return;
        }

        runSearch({ onDone });
    }

    function resetSearch() {
        searchQ.value = '';
        searchResults.value = [];
        searchLoading.value = false;
        closeSearchSuggestions();
    }

    function disposeSearch() {
        clearTimeout(searchTimer);
        clearTimeout(closeSearchTimer);
    }

    onBeforeUnmount(disposeSearch);

    return {
        searchQ,
        searchResults,
        searchLoading,
        searchOpen,
        highlightedSearchIndex,
        showSearchDropdown,
        debouncedProductSearch,
        openSearchSuggestions,
        closeSearchSuggestionsSoon,
        closeSearchSuggestions,
        moveSearchHighlight,
        onSearchEnter,
        selectSearchResult,
        runSearch,
        resetSearch,
        disposeSearch,
    };
}
