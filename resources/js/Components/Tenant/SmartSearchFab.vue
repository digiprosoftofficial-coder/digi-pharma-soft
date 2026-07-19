<template>
    <button
        v-if="visible"
        type="button"
        class="smart-search-fab d-lg-none"
        :class="[`smart-search-fab--${side}`, { 'smart-search-fab--dragging': dragging }]"
        :style="fabStyle"
        :aria-label="t('common.smart_search_title')"
        :title="t('common.smart_search_title')"
        @pointerdown="onPointerDown"
        @click="onClick"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" aria-hidden="true">
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
        </svg>
    </button>

    <ProductSearchOverlay v-model:open="overlayOpen" />
</template>

<script setup>
import ProductSearchOverlay from '@/Components/Tenant/ProductSearchOverlay.vue';
import { useLocale } from '@/composables/useLocale';
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const STORAGE_KEY = 'saas_pharmacy_smart_search_side';

const { t } = useLocale();
const page = usePage();

const overlayOpen = ref(false);
const side = ref('right');
const dragging = ref(false);
const dragMoved = ref(false);
const offsetY = ref(0);
let startY = 0;
let startOffset = 0;
let activePointerId = null;

const smartSearchEnabled = computed(() => page.props.features?.smart_search ?? true);
const path = computed(() => (page.url || '').split('?')[0]);
const onPos = computed(() => path.value === '/pos' || path.value.startsWith('/pos/'));

const visible = computed(() => smartSearchEnabled.value && !onPos.value);

const fabStyle = computed(() => ({
    transform: `translateY(${offsetY.value}px)`,
}));

function onClick() {
    if (dragMoved.value) {
        dragMoved.value = false;
        return;
    }
    overlayOpen.value = true;
}

function onPointerDown(event) {
    if (event.button !== undefined && event.button !== 0) {
        return;
    }

    dragging.value = true;
    dragMoved.value = false;
    startY = event.clientY;
    startOffset = offsetY.value;
    activePointerId = event.pointerId;
    event.currentTarget.setPointerCapture?.(event.pointerId);

    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', onPointerUp);
    window.addEventListener('pointercancel', onPointerUp);
}

function onPointerMove(event) {
    if (!dragging.value) {
        return;
    }

    const dy = event.clientY - startY;
    if (Math.abs(dy) > 6 || Math.abs(event.movementX) > 6) {
        dragMoved.value = true;
    }

    offsetY.value = Math.max(-160, Math.min(120, startOffset + dy));

    const mid = window.innerWidth / 2;
    side.value = event.clientX < mid ? 'left' : 'right';
}

function onPointerUp() {
    if (!dragging.value) {
        return;
    }

    dragging.value = false;
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);
    window.removeEventListener('pointercancel', onPointerUp);
    activePointerId = null;

    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            side: side.value,
            offsetY: offsetY.value,
        }));
    } catch {
        // ignore storage errors
    }
}

onMounted(() => {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return;
        }
        const saved = JSON.parse(raw);
        if (saved?.side === 'left' || saved?.side === 'right') {
            side.value = saved.side;
        }
        if (typeof saved?.offsetY === 'number') {
            offsetY.value = saved.offsetY;
        }
    } catch {
        // ignore
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);
    window.removeEventListener('pointercancel', onPointerUp);
    if (activePointerId != null) {
        // no-op cleanup
    }
});
</script>

<style scoped>
.smart-search-fab {
    position: fixed;
    z-index: 1038;
    bottom: calc(5.5rem + env(safe-area-inset-bottom, 0px));
    width: 3.25rem;
    height: 3.25rem;
    border: 0;
    border-radius: 50%;
    color: #fff;
    background: var(--bs-primary, #2563eb);
    box-shadow: 0 0.45rem 1.1rem rgba(37, 99, 235, 0.4);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    touch-action: none;
    cursor: grab;
    transition: box-shadow 0.15s ease, background-color 0.15s ease;
}

.smart-search-fab--right {
    right: 0.85rem;
    left: auto;
}

.smart-search-fab--left {
    left: 0.85rem;
    right: auto;
}

.smart-search-fab--dragging {
    cursor: grabbing;
    box-shadow: 0 0.65rem 1.4rem rgba(37, 99, 235, 0.5);
}

.smart-search-fab:hover {
    background: #1d4ed8;
}
</style>
