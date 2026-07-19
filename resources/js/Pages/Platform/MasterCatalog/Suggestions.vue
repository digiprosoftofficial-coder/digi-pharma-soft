<template>
    <PlatformShellLayout :page-title="t('platform.suggestions_title')">
        <Head :title="t('platform.suggestions_title')" />

        <section class="suggest-hero rounded-4 mb-4 p-4 p-md-5 text-white">
            <div class="row align-items-end g-3">
                <div class="col-lg-8">
                    <p class="small text-white-50 text-uppercase mb-2 fw-semibold letter-space">{{ t('platform.suggestions_eyebrow') }}</p>
                    <h1 class="h2 fw-semibold mb-2">{{ t('platform.suggestions_title') }}</h1>
                    <p class="mb-0 opacity-90" style="max-width: 38rem">{{ t('platform.suggestions_sub') }}</p>
                </div>
                <div class="col-lg-4 d-flex flex-wrap justify-content-lg-end gap-2">
                    <Link href="/platform/master-catalog" class="btn btn-outline-light">{{ t('platform.master_title') }}</Link>
                </div>
            </div>
        </section>

        <div class="row g-3 mb-4">
            <div v-for="card in statCards" :key="card.key" class="col-6 col-md-3">
                <button
                    type="button"
                    class="stat-tile w-100 text-start border-0"
                    :class="{ 'stat-tile--active': filters.status === card.key }"
                    @click="setStatus(card.key)"
                >
                    <div class="stat-tile__label">{{ card.label }}</div>
                    <div class="stat-tile__value" :class="card.valueClass">{{ stats[card.key] ?? 0 }}</div>
                </button>
            </div>
        </div>

        <form class="card border-0 shadow-sm mb-3" @submit.prevent="applyFilters">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small mb-1">{{ t('common.search') }}</label>
                    <input v-model="local.q" type="search" class="form-control" :placeholder="t('platform.suggestions_search_placeholder')" />
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">{{ t('common.search') }}</button>
                    <button type="button" class="btn btn-outline-secondary" @click="clearFilters">{{ t('platform.master_clear') }}</button>
                </div>
            </div>
        </form>

        <div v-if="!suggestions.data?.length" class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="empty-icon mb-3">✓</div>
                <h2 class="h6 mb-1">{{ t('platform.suggestions_empty_title') }}</h2>
                <p class="small text-muted mb-0">{{ t('platform.suggestions_empty_sub') }}</p>
            </div>
        </div>

        <div v-for="item in suggestions.data" :key="item.id" class="card border-0 shadow-sm mb-3 suggestion-card">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <h2 class="h5 mb-0">{{ item.name }}</h2>
                            <span class="badge rounded-pill" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
                        </div>
                        <div class="small text-muted">
                            <span v-if="item.generic_name">{{ item.generic_name }}</span>
                            <span v-if="item.generic_name && item.strength"> · </span>
                            <span v-if="item.strength">{{ item.strength }}</span>
                            <span v-if="item.manufacturer_name"> · {{ item.manufacturer_name }}</span>
                        </div>
                    </div>
                    <div class="text-md-end small">
                        <div class="fw-semibold text-teal">{{ item.pharmacy?.name || '—' }}</div>
                        <div class="text-muted">{{ item.suggested_by || '—' }}</div>
                        <div class="text-muted">{{ item.created_at }}</div>
                    </div>
                </div>

                <div class="row g-2 small mb-3 meta-grid">
                    <div class="col-6 col-md-3"><span class="text-muted">SKU</span><div class="fw-medium">{{ item.sku || '—' }}</div></div>
                    <div class="col-6 col-md-3"><span class="text-muted">{{ t('catalog.barcode') }}</span><div class="fw-medium">{{ item.barcode || '—' }}</div></div>
                    <div class="col-6 col-md-3"><span class="text-muted">{{ t('platform.master_mrp') }}</span><div class="fw-medium">{{ formatMoney(item.mrp) }}</div></div>
                    <div class="col-6 col-md-3"><span class="text-muted">{{ t('catalog.product_type') }}</span><div class="fw-medium">{{ typeLabel(item.product_type) }}</div></div>
                </div>

                <div v-if="item.status === 'pending'" class="border-top pt-3">
                    <label class="form-label small">{{ t('platform.suggestions_note') }}</label>
                    <input v-model="notes[item.id]" type="text" class="form-control form-control-sm mb-3" :placeholder="t('platform.suggestions_note_placeholder')" />

                    <div v-if="item.candidates?.length" class="mb-3">
                        <div class="small fw-semibold mb-2">{{ t('platform.suggestions_possible_matches') }}</div>
                        <div class="d-flex flex-column gap-2">
                            <div
                                v-for="c in item.candidates"
                                :key="c.id"
                                class="candidate-row d-flex flex-wrap justify-content-between align-items-center gap-2"
                            >
                                <div class="small">
                                    <span class="fw-semibold">{{ c.name }}</span>
                                    <span class="text-muted"> · {{ c.generic_name || '—' }} {{ c.strength || '' }}</span>
                                    <span v-if="c.sku" class="text-muted"> · <code>{{ c.sku }}</code></span>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-teal"
                                    :disabled="busyId === item.id"
                                    @click="merge(item, c.id)"
                                >
                                    {{ t('platform.suggestions_merge') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" :disabled="busyId === item.id" @click="approve(item)">
                            {{ t('platform.suggestions_approve') }}
                        </button>
                        <button type="button" class="btn btn-outline-danger" :disabled="busyId === item.id" @click="reject(item)">
                            {{ t('platform.suggestions_reject') }}
                        </button>
                    </div>
                </div>

                <div v-else-if="item.review_note" class="border-top pt-3 small text-muted">
                    {{ t('platform.suggestions_note') }}: {{ item.review_note }}
                </div>
            </div>
        </div>

        <div v-if="suggestions.last_page > 1" class="d-flex justify-content-between align-items-center">
            <span class="small text-muted">
                {{ t('platform.master_showing', { from: String(suggestions.from ?? 0), to: String(suggestions.to ?? 0), total: String(suggestions.total ?? 0) }) }}
            </span>
            <div class="d-flex gap-1">
                <Link v-if="suggestions.prev_page_url" :href="suggestions.prev_page_url" class="btn btn-sm btn-outline-secondary" preserve-scroll>←</Link>
                <Link v-if="suggestions.next_page_url" :href="suggestions.next_page_url" class="btn btn-sm btn-outline-secondary" preserve-scroll>→</Link>
            </div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    suggestions: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const busyId = ref(null);
const notes = reactive({});

const local = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? 'pending',
});

const statCards = computed(() => [
    { key: 'pending', label: t('platform.suggestions_stat_pending'), valueClass: 'text-warning' },
    { key: 'approved', label: t('platform.suggestions_stat_approved'), valueClass: 'text-success' },
    { key: 'merged', label: t('platform.suggestions_stat_merged'), valueClass: 'text-teal' },
    { key: 'rejected', label: t('platform.suggestions_stat_rejected'), valueClass: 'text-secondary' },
]);

function typeLabel(type) {
    return t(`catalog.types.${type}`, type);
}

function statusLabel(status) {
    return t(`platform.suggestions_status_${status}`, status);
}

function statusClass(status) {
    return {
        pending: 'text-bg-warning-subtle text-warning border border-warning-subtle',
        approved: 'text-bg-success-subtle text-success border border-success-subtle',
        merged: 'text-bg-info-subtle text-info border border-info-subtle',
        rejected: 'text-bg-secondary-subtle text-secondary border',
    }[status] || 'text-bg-secondary';
}

function applyFilters() {
    router.get('/platform/master-catalog/suggestions', {
        q: local.q || undefined,
        status: local.status || 'pending',
    }, { preserveState: true, replace: true });
}

function setStatus(status) {
    local.status = status;
    applyFilters();
}

function clearFilters() {
    local.q = '';
    local.status = 'pending';
    applyFilters();
}

function approve(item) {
    busyId.value = item.id;
    router.post(`/platform/master-catalog/suggestions/${item.id}/approve`, {
        review_note: notes[item.id] || null,
    }, {
        preserveScroll: true,
        onFinish: () => { busyId.value = null; },
    });
}

function reject(item) {
    if (!window.confirm(t('platform.suggestions_reject_confirm', { name: item.name }))) {
        return;
    }
    busyId.value = item.id;
    router.post(`/platform/master-catalog/suggestions/${item.id}/reject`, {
        review_note: notes[item.id] || null,
    }, {
        preserveScroll: true,
        onFinish: () => { busyId.value = null; },
    });
}

function merge(item, masterId) {
    busyId.value = item.id;
    router.post(`/platform/master-catalog/suggestions/${item.id}/merge`, {
        master_product_id: masterId,
        review_note: notes[item.id] || null,
    }, {
        preserveScroll: true,
        onFinish: () => { busyId.value = null; },
    });
}
</script>

<style scoped>
.suggest-hero {
    background:
        radial-gradient(900px 320px at 85% -10%, rgba(255, 255, 255, 0.16), transparent 55%),
        linear-gradient(135deg, #0f766e 0%, #0e7490 55%, #155e75 100%);
    box-shadow: 0 18px 40px rgba(14, 116, 144, 0.2);
}

.letter-space { letter-spacing: 0.08em; }
.text-teal { color: #0f766e !important; }

.stat-tile {
    background: #fff;
    border-radius: 1rem;
    padding: 1rem 1.1rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.04);
    border: 1px solid rgba(15, 23, 42, 0.04) !important;
}

.stat-tile--active {
    outline: 2px solid #0f766e;
    outline-offset: 0;
}

.stat-tile__label { font-size: 0.78rem; color: #64748b; margin-bottom: 0.25rem; }
.stat-tile__value { font-size: 1.65rem; font-weight: 700; line-height: 1.1; color: #0f172a; }

.suggestion-card { border: 1px solid rgba(15, 23, 42, 0.04); }
.meta-grid > div { background: #f8fafc; border-radius: 0.75rem; padding: 0.65rem 0.8rem; }

.candidate-row {
    background: #f0fdfa;
    border: 1px solid #ccfbf1;
    border-radius: 0.75rem;
    padding: 0.55rem 0.75rem;
}

.btn-outline-teal {
    --bs-btn-color: #0f766e;
    --bs-btn-border-color: #0f766e;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #0f766e;
    --bs-btn-hover-border-color: #0f766e;
}

.empty-icon {
    width: 3rem;
    height: 3rem;
    margin-inline: auto;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: #ecfdf5;
    color: #0f766e;
    font-weight: 700;
}
</style>
