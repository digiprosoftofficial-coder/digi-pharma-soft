<template>
    <TenantShellLayout page-title="Notes">
        <Head title="Notes" />

        <div class="notes-page">
            <!-- Header row: title + subtitle + search + new note -->
            <header class="notes-header">
                <div class="notes-header__title">
                    <h1>Notes</h1>
                    <p>Organize buy lists, phone numbers &amp; important reminders</p>
                </div>
                <div class="notes-header__actions">
                    <label class="notes-search">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
                        </svg>
                        <input v-model="searchInput" type="search" placeholder="Search notes..." @input="onSearchInput" />
                        <span class="notes-search__hint">Ctrl+/</span>
                    </label>
                    <button
                        v-if="canManage"
                        type="button"
                        class="btn btn-primary btn-sm notes-newbtn"
                        aria-label="New note"
                        @click="openComposer"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        <span class="notes-newbtn__label">New Note</span>
                    </button>
                </div>
            </header>

            <!-- Summary cards row -->
            <div class="notes-stats">
                <button
                    v-for="stat in stats"
                    :key="stat.key"
                    type="button"
                    class="notes-stat"
                    :class="[`notes-stat--${stat.tone}`, { 'notes-stat--active': stat.active }]"
                    @click="stat.onClick"
                >
                    <span class="notes-stat__icon">
                        <component :is="stat.icon" />
                    </span>
                    <span class="notes-stat__body">
                        <span class="notes-stat__label">{{ stat.label }}</span>
                        <span class="notes-stat__value">{{ stat.value }}</span>
                    </span>
                </button>
            </div>

            <!-- Filter bar: category / sort / view toggle -->
            <div class="notes-filterbar">
                <div class="notes-filterbar__filters">
                    <div class="notes-select">
                        <select v-model="categoryValue" class="form-select form-select-sm" @change="onCategoryChange">
                            <option value="">All Categories</option>
                            <option v-for="type in types" :key="type" :value="type">{{ typeLabel(type) }}</option>
                        </select>
                    </div>
                    <div class="notes-select">
                        <select v-model="tabValue" class="form-select form-select-sm" @change="onTabChange">
                            <option value="open">Open</option>
                            <option value="pinned">Pinned</option>
                            <option value="done">Completed</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                    <div class="notes-select">
                        <select v-model="sortValue" class="form-select form-select-sm">
                            <option value="latest">Sort: Latest</option>
                            <option value="oldest">Sort: Oldest</option>
                            <option value="az">Sort: A → Z</option>
                        </select>
                    </div>
                </div>

                <div class="notes-viewtoggle" role="group" aria-label="View mode">
                    <button
                        type="button"
                        class="notes-viewtoggle__btn"
                        :class="{ 'notes-viewtoggle__btn--active': viewMode === 'list' }"
                        :aria-pressed="viewMode === 'list'"
                        title="List view"
                        @click="viewMode = 'list'"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="notes-viewtoggle__btn"
                        :class="{ 'notes-viewtoggle__btn--active': viewMode === 'grid' }"
                        :aria-pressed="viewMode === 'grid'"
                        title="Grid view"
                        @click="viewMode = 'grid'"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Main content: list/grid + detail panel -->
            <div class="notes-main" :class="{ 'notes-main--split': selectedNote }">
                <section class="notes-main__list">
                    <!-- Pinned section -->
                    <div v-if="pinnedNotes.length" class="notes-section">
                        <h2 class="notes-section__title">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 17v5" /><path d="M9 3h6l-1 5 3 3v3H7v-3l3-3-1-5z" />
                            </svg>
                            Pinned
                            <span class="notes-section__count">{{ pinnedNotes.length }}</span>
                        </h2>
                        <component
                            :is="viewMode === 'grid' ? 'div' : 'ul'"
                            class="notes-listgroup"
                            :class="`notes-listgroup--${viewMode}`"
                        >
                            <NoteRow
                                v-for="note in pinnedNotes"
                                :key="`p-${note.id}`"
                                :note="note"
                                :view="viewMode"
                                :selected="selectedNote?.id === note.id"
                                :can-manage="canManage"
                                @select="selectNote"
                                @toggle-done="toggleDone"
                                @toggle-pin="togglePin"
                                @edit="startEdit"
                                @delete="askDelete"
                            />
                        </component>
                    </div>

                    <!-- Other notes section -->
                    <div v-if="otherNotes.length" class="notes-section">
                        <h2 v-if="pinnedNotes.length" class="notes-section__title">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z M14 3v6h6 M8 13h8M8 17h5" />
                            </svg>
                            Notes
                            <span class="notes-section__count">{{ otherNotes.length }}</span>
                        </h2>
                        <component
                            :is="viewMode === 'grid' ? 'div' : 'ul'"
                            class="notes-listgroup"
                            :class="`notes-listgroup--${viewMode}`"
                        >
                            <NoteRow
                                v-for="note in otherNotes"
                                :key="note.id"
                                :note="note"
                                :view="viewMode"
                                :selected="selectedNote?.id === note.id"
                                :can-manage="canManage"
                                @select="selectNote"
                                @toggle-done="toggleDone"
                                @toggle-pin="togglePin"
                                @edit="startEdit"
                                @delete="askDelete"
                            />
                        </component>
                    </div>

                    <!-- Empty state -->
                    <div v-if="sortedNotes.length === 0" class="notes-empty">
                        <div class="notes-empty__icon">
                            <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z" />
                                <path d="M14 3v6h6" /><path d="M8 13h8M8 17h5" />
                            </svg>
                        </div>
                        <h3>{{ emptyCopy.title }}</h3>
                        <p>{{ emptyCopy.body }}</p>
                        <button v-if="hasActiveFilters" type="button" class="btn btn-outline-secondary btn-sm" @click="clearAllFilters">
                            Clear filters
                        </button>
                        <button v-else-if="canManage" type="button" class="btn btn-primary btn-sm" @click="openComposer">
                            + New Note
                        </button>
                    </div>
                </section>

                <!-- Detail panel -->
                <aside v-if="selectedNote" class="notes-detail">
                    <header class="notes-detail__header">
                        <div class="notes-detail__tags">
                            <span class="notes-typechip" :class="`notes-typechip--${selectedNote.type} notes-typechip--active`">
                                <span class="notes-typechip__dot" />
                                {{ typeLabel(selectedNote.type) }}
                            </span>
                            <span v-if="selectedNote.is_pinned" class="notes-typechip notes-typechip--buy notes-typechip--active">
                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 17v5" /><path d="M9 3h6l-1 5 3 3v3H7v-3l3-3-1-5z" />
                                </svg>
                                Pinned
                            </span>
                            <span v-if="selectedNote.is_done" class="notes-typechip notes-typechip--done notes-typechip--active">
                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12l5 5L20 7" />
                                </svg>
                                Completed
                            </span>
                        </div>
                        <button type="button" class="notes-iconbtn" aria-label="Close" @click="selectedNote = null">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <path d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </header>

                    <h2 v-if="selectedNote.title" class="notes-detail__title">{{ selectedNote.title }}</h2>
                    <h2 v-else class="notes-detail__title notes-detail__title--muted">Untitled note</h2>

                    <div class="notes-detail__meta">
                        <div class="notes-detail__metarow">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" />
                            </svg>
                            <span>{{ formatDateFull(selectedNote.created_at) }}</span>
                        </div>
                        <div v-if="selectedNote.user?.name" class="notes-detail__metarow">
                            <span class="notes-avatar notes-avatar--sm">{{ initials(selectedNote.user.name) }}</span>
                            <div class="notes-detail__assign">
                                <span class="notes-detail__assign-label">Created by</span>
                                <span class="notes-detail__assign-name">{{ selectedNote.user.name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="notes-detail__section">
                        <h3 class="notes-detail__section-title">Description</h3>
                        <p class="notes-detail__body">{{ selectedNote.body }}</p>
                    </div>

                    <div v-if="selectedNote.updated_at !== selectedNote.created_at" class="notes-detail__section">
                        <h3 class="notes-detail__section-title">Activity</h3>
                        <ul class="notes-timeline">
                            <li>
                                <span class="notes-timeline__dot notes-timeline__dot--primary" />
                                <span>Note created</span>
                                <span class="notes-timeline__when">{{ formatDateFull(selectedNote.created_at) }}</span>
                            </li>
                            <li v-if="selectedNote.updated_at !== selectedNote.created_at">
                                <span class="notes-timeline__dot" />
                                <span>Last updated</span>
                                <span class="notes-timeline__when">{{ formatDateFull(selectedNote.updated_at) }}</span>
                            </li>
                            <li v-if="selectedNote.done_at">
                                <span class="notes-timeline__dot notes-timeline__dot--success" />
                                <span>Marked completed</span>
                                <span class="notes-timeline__when">{{ formatDateFull(selectedNote.done_at) }}</span>
                            </li>
                        </ul>
                    </div>

                    <footer v-if="canManage" class="notes-detail__footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" @click="startEdit(selectedNote)">
                            Edit Note
                        </button>
                        <button
                            type="button"
                            class="btn btn-primary btn-sm flex-fill"
                            @click="toggleDone(selectedNote)"
                        >
                            {{ selectedNote.is_done ? 'Reopen' : 'Mark as Completed' }}
                        </button>
                    </footer>
                </aside>
            </div>
        </div>

        <!-- Composer modal (create + edit) — Bootstrap modal pattern -->
        <Teleport to="body">
            <div
                v-if="composerOpen"
                class="modal fade show d-block notes-modal"
                tabindex="-1"
                role="dialog"
                aria-modal="true"
                aria-labelledby="notes-composer-title"
                @keydown.esc.prevent="cancelComposer"
                @mousedown.self="cancelComposer"
            >
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" @mousedown.stop>
                    <div class="modal-content notes-modal__content" :class="`notes-modal__content--${composer.type}`">
                        <div class="modal-header">
                            <h2 id="notes-composer-title" class="modal-title h5 mb-0">
                                {{ editingId ? 'Edit note' : 'New note' }}
                            </h2>
                            <button
                                type="button"
                                class="btn-close"
                                aria-label="Close"
                                :disabled="composer.processing"
                                @click="cancelComposer"
                            />
                        </div>

                        <form @submit.prevent="submitComposer">
                            <div class="modal-body">
                                <input
                                    ref="titleInputRef"
                                    v-model="composer.title"
                                    type="text"
                                    class="notes-modal__title"
                                    maxlength="120"
                                    placeholder="Add a title (optional)"
                                />
                                <textarea
                                    ref="bodyRef"
                                    v-model="composer.body"
                                    class="notes-modal__body"
                                    rows="6"
                                    placeholder="What came to mind? Medicine to buy, a phone number, a reminder..."
                                    @keydown="onComposerKeydown"
                                />

                                <div class="notes-modal__chips">
                                    <button
                                        v-for="type in types"
                                        :key="type"
                                        type="button"
                                        class="notes-typechip"
                                        :class="[`notes-typechip--${type}`, { 'notes-typechip--active': composer.type === type }]"
                                        @click="composer.type = type"
                                    >
                                        <span class="notes-typechip__dot" />
                                        {{ typeLabel(type) }}
                                    </button>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <span class="notes-modal__count me-auto">{{ composer.body.length }} / 5000</span>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    :disabled="composer.processing"
                                    @click="cancelComposer"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                    :disabled="composer.processing || !composer.body.trim()"
                                >
                                    <span v-if="composer.processing" class="spinner-border spinner-border-sm me-1" role="status" />
                                    {{ editingId ? 'Save changes' : 'Save note' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div v-if="composerOpen" class="modal-backdrop fade show" />
        </Teleport>

        <ConfirmModal
            :show="deleteTarget !== null"
            title="Delete this note?"
            confirm-label="Delete"
            confirm-class="btn-danger"
            :processing="deleteProcessing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        >
            <p class="mb-0 text-muted">This note will be permanently removed and cannot be recovered.</p>
            <p v-if="deleteTarget?.title" class="mt-2 mb-0 fw-semibold">{{ deleteTarget.title }}</p>
            <p v-if="deleteTarget?.body" class="mb-0 text-truncate-2 small text-muted">{{ deleteTarget.body }}</p>
        </ConfirmModal>
    </TenantShellLayout>
</template>

<script setup>
import ConfirmModal from '@/Components/ConfirmModal.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, h, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import NoteRow from './NoteRow.vue';

const props = defineProps({
    notes: { type: Array, required: true },
    filters: { type: Object, required: true },
    types: { type: Array, required: true },
    counts: { type: Object, default: () => ({ open: 0, pinned: 0, done: 0, all: 0, today: 0, by_type: {} }) },
});

const { can } = usePermissions();
const canManage = computed(() => can('notes.manage'));

const TYPE_LABELS = { buy: 'Buy', contact: 'Contact', reminder: 'Reminder', general: 'General' };

/* ---------- Local UI state ---------- */
const composerOpen = ref(false);
const editingId = ref(null);
const searchInput = ref(props.filters.q ?? '');
const viewMode = ref(loadViewMode());
const sortValue = ref('latest');
const categoryValue = ref(props.filters.type ?? '');
const tabValue = ref(props.filters.tab ?? 'open');
const selectedNote = ref(null);
const deleteTarget = ref(null);
const deleteProcessing = ref(false);
const bodyRef = ref(null);
const titleInputRef = ref(null);

let searchTimer = null;

const composer = useForm({ title: '', body: '', type: 'general' });

watch(() => props.filters.q, (q) => { searchInput.value = q ?? ''; });
watch(() => props.filters.type, (v) => { categoryValue.value = v ?? ''; });
watch(() => props.filters.tab, (v) => { tabValue.value = v ?? 'open'; });
watch(viewMode, (v) => { try { localStorage.setItem('notes.viewMode', v); } catch { /* ignore */ } });

/* ---------- Derived ---------- */
const hasActiveFilters = computed(() =>
    (props.filters.tab && props.filters.tab !== 'open') || !!props.filters.type || !!props.filters.q,
);

const sortedNotes = computed(() => {
    const list = [...props.notes];
    if (sortValue.value === 'oldest') {
        list.sort((a, b) => new Date(a.updated_at) - new Date(b.updated_at));
    } else if (sortValue.value === 'az') {
        list.sort((a, b) => (a.title || a.body).localeCompare(b.title || b.body));
    } else {
        list.sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));
    }
    return list;
});

const pinnedNotes = computed(() => sortedNotes.value.filter((n) => n.is_pinned && !n.is_done));
const otherNotes = computed(() => sortedNotes.value.filter((n) => !(n.is_pinned && !n.is_done)));

const emptyCopy = computed(() => {
    if (props.filters.q || props.filters.type) {
        return { title: 'No matching notes', body: 'Try a different category, tab, or search term.' };
    }
    if (props.filters.tab === 'pinned') return { title: 'No pinned notes', body: 'Pin important notes so they stay on top.' };
    if (props.filters.tab === 'done') return { title: 'No completed notes yet', body: 'Notes you mark as done will show up here.' };
    return { title: 'Nothing on your list', body: 'Capture medicines to buy, phone numbers, or reminders here.' };
});

/* ---------- Stat cards ---------- */
const StatIcon = {
    file: () => h('svg', { viewBox: '0 0 24 24', width: 18, height: 18, fill: 'none', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
        h('path', { d: 'M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z' }),
        h('path', { d: 'M14 3v6h6' }),
    ]),
    today: () => h('svg', { viewBox: '0 0 24 24', width: 18, height: 18, fill: 'none', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
        h('rect', { x: 3, y: 4, width: 18, height: 18, rx: 2 }),
        h('path', { d: 'M16 2v4M8 2v4M3 10h18' }),
    ]),
    open: () => h('svg', { viewBox: '0 0 24 24', width: 18, height: 18, fill: 'none', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
        h('circle', { cx: 12, cy: 12, r: 9 }),
        h('path', { d: 'M12 7v5l3 2' }),
    ]),
    pin: () => h('svg', { viewBox: '0 0 24 24', width: 18, height: 18, fill: 'none', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
        h('path', { d: 'M12 17v5' }),
        h('path', { d: 'M9 3h6l-1 5 3 3v3H7v-3l3-3-1-5z' }),
    ]),
    done: () => h('svg', { viewBox: '0 0 24 24', width: 18, height: 18, fill: 'none', stroke: 'currentColor', 'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round' }, [
        h('circle', { cx: 12, cy: 12, r: 9 }),
        h('path', { d: 'M8 12l3 3 5-6' }),
    ]),
};

const stats = computed(() => [
    { key: 'all',    label: 'All Notes', value: props.counts.all ?? 0,    tone: 'indigo', icon: StatIcon.file,  active: tabValue.value === 'all',    onClick: () => setTab('all') },
    { key: 'today',  label: 'Today',     value: props.counts.today ?? 0,  tone: 'sky',    icon: StatIcon.today, active: false,                        onClick: () => setTab('all') },
    { key: 'open',   label: 'Open',      value: props.counts.open ?? 0,   tone: 'amber',  icon: StatIcon.open,  active: tabValue.value === 'open',   onClick: () => setTab('open') },
    { key: 'pinned', label: 'Pinned',    value: props.counts.pinned ?? 0, tone: 'rose',   icon: StatIcon.pin,   active: tabValue.value === 'pinned', onClick: () => setTab('pinned') },
    { key: 'done',   label: 'Completed', value: props.counts.done ?? 0,   tone: 'green',  icon: StatIcon.done,  active: tabValue.value === 'done',   onClick: () => setTab('done') },
]);

/* ---------- Router helpers ---------- */
function filterParams(overrides = {}) {
    const tab = overrides.tab !== undefined ? overrides.tab : (props.filters.tab ?? 'open');
    const type = overrides.type !== undefined ? overrides.type : (props.filters.type ?? null);
    const q = overrides.q !== undefined ? overrides.q : (props.filters.q ?? null);
    return Object.fromEntries(Object.entries({ tab, type, q }).filter(([, v]) => v !== null && v !== ''));
}
function preservedFilterFields() {
    const p = filterParams();
    return {
        filter_tab: p.tab ?? 'open',
        ...(p.type ? { filter_type: p.type } : {}),
        ...(p.q ? { filter_q: p.q } : {}),
    };
}
function applyFilters(overrides = {}) {
    router.get('/notes', filterParams(overrides), { preserveState: true, preserveScroll: true, replace: true });
}
function setTab(tab) { applyFilters({ tab }); }
function onTabChange() { setTab(tabValue.value); }
function onCategoryChange() { applyFilters({ type: categoryValue.value || null }); }
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters({ q: searchInput.value.trim() || null }), 300);
}
function clearAllFilters() {
    searchInput.value = ''; categoryValue.value = ''; tabValue.value = 'open';
    router.get('/notes', {}, { preserveState: true, preserveScroll: true, replace: true });
}

/* ---------- Composer ---------- */
function focusComposerBody() {
    nextTick(() => {
        setTimeout(() => bodyRef.value?.focus(), 60);
    });
}

function openComposer() {
    editingId.value = null;
    composer.reset();
    composer.clearErrors();
    composer.type = 'general';
    composer.transform((d) => d);
    composerOpen.value = true;
    focusComposerBody();
}

function cancelComposer() {
    if (composer.processing) return;
    composer.reset();
    composer.clearErrors();
    composer.type = 'general';
    composer.transform((d) => d);
    composerOpen.value = false;
    editingId.value = null;
}

function onComposerKeydown(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        submitComposer();
    }
}

function submitComposer() {
    if (!composer.body.trim() || composer.processing) return;

    const payload = (d) => ({
        title: d.title?.trim() || null,
        body: d.body.trim(),
        type: d.type,
        ...preservedFilterFields(),
    });

    const done = () => {
        composer.reset();
        composer.clearErrors();
        composer.type = 'general';
        composer.transform((d) => d);
        composerOpen.value = false;
        editingId.value = null;
    };

    if (editingId.value) {
        const id = editingId.value;
        composer.transform(payload).put(`/notes/${id}`, {
            preserveScroll: true,
            onSuccess: done,
        });
    } else {
        composer.transform(payload).post('/notes', {
            preserveScroll: true,
            onSuccess: done,
        });
    }
}

function startEdit(note) {
    editingId.value = note.id;
    composer.reset();
    composer.clearErrors();
    composer.title = note.title ?? '';
    composer.body = note.body;
    composer.type = note.type;
    composer.transform((d) => d);
    composerOpen.value = true;
    focusComposerBody();
}

/* Lock body scroll while composer modal is open */
watch(composerOpen, (open) => {
    if (typeof document === 'undefined') return;
    document.body.classList.toggle('modal-open', open);
    document.body.style.overflow = open ? 'hidden' : '';
});

/* ---------- Actions ---------- */
function selectNote(note) {
    selectedNote.value = selectedNote.value?.id === note.id ? null : note;
}
function togglePin(note) { router.patch(`/notes/${note.id}/pin`, filterParams(), { preserveScroll: true }); }
function toggleDone(note) { router.patch(`/notes/${note.id}/done`, filterParams(), { preserveScroll: true }); }
function askDelete(note) { deleteTarget.value = note; }
function confirmDelete() {
    if (!deleteTarget.value) return;
    const id = deleteTarget.value.id;
    deleteProcessing.value = true;
    router.delete(`/notes/${id}`, {
        data: filterParams(),
        preserveScroll: true,
        onFinish: () => {
            deleteProcessing.value = false;
            deleteTarget.value = null;
            if (selectedNote.value?.id === id) selectedNote.value = null;
        },
    });
}

/* ---------- Keep detail panel in sync when notes list refreshes ---------- */
watch(() => props.notes, (list) => {
    if (!selectedNote.value) return;
    const fresh = list.find((n) => n.id === selectedNote.value.id);
    selectedNote.value = fresh ?? null;
});

/* ---------- Keyboard shortcut Ctrl+/ focuses search ---------- */
function onGlobalKey(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === '/') {
        e.preventDefault();
        document.querySelector('.notes-search__input, .notes-search input')?.focus();
    }
}
onMounted(() => window.addEventListener('keydown', onGlobalKey));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onGlobalKey);
    if (typeof document !== 'undefined') {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }
});

/* ---------- Utils ---------- */
function typeLabel(type) { return TYPE_LABELS[type] ?? type; }
function initials(name) {
    if (!name) return '';
    return name.trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('');
}
function loadViewMode() {
    try { return localStorage.getItem('notes.viewMode') === 'grid' ? 'grid' : 'list'; }
    catch { return 'list'; }
}
function formatDateFull(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString(undefined, {
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
</script>

<style scoped>
/* ==========================================================================
   Design tokens
   ========================================================================== */
.notes-page {
    --n-radius: 0.85rem;
    --n-radius-sm: 0.55rem;
    --n-border: #e6e8ee;
    --n-border-soft: #eff1f5;
    --n-surface: #ffffff;
    --n-surface-soft: #f7f8fb;
    --n-text: #111827;
    --n-text-muted: #6b7280;
    --n-text-faint: #9ca3af;
    --n-shadow-sm: 0 1px 2px rgba(15, 23, 42, 0.04);
    --n-shadow-md: 0 6px 16px rgba(15, 23, 42, 0.06), 0 2px 4px rgba(15, 23, 42, 0.04);

    --n-buy: #f59e0b;      --n-buy-soft: #fef3c7;      --n-buy-ink: #92400e;
    --n-contact: #3b82f6;  --n-contact-soft: #dbeafe;  --n-contact-ink: #1e40af;
    --n-reminder: #8b5cf6; --n-reminder-soft: #ede9fe; --n-reminder-ink: #5b21b6;
    --n-general: #64748b;  --n-general-soft: #eef2f6;  --n-general-ink: #334155;
    --n-done: #16a34a;     --n-done-soft: #dcfce7;     --n-done-ink: #14532d;

    max-width: 1280px;
    margin: 0 auto;
    color: var(--n-text);
}

/* ==========================================================================
   Header
   ========================================================================== */
.notes-header {
    display: flex; flex-direction: column; gap: 0.85rem;
    margin-bottom: 1rem;
}
.notes-header__title h1 {
    font-size: 1.4rem; font-weight: 700; margin: 0; letter-spacing: -0.01em;
}
.notes-header__title p {
    margin: 0.15rem 0 0; color: var(--n-text-muted); font-size: 0.85rem;
}
.notes-header__actions {
    display: flex; align-items: center; gap: 0.5rem;
    flex-wrap: nowrap; width: 100%;
}

.notes-search {
    display: flex; align-items: center; gap: 0.55rem;
    flex: 1 1 auto; min-width: 0;
    background: var(--n-surface); border: 1px solid var(--n-border);
    border-radius: 999px; padding: 0.5rem 0.85rem; color: var(--n-text-muted);
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}
.notes-search:focus-within {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.14);
}
.notes-search input {
    flex: 1 1 auto; min-width: 0; border: 0; background: transparent;
    outline: none; font-size: 0.88rem; color: var(--n-text);
}
.notes-search input::placeholder { color: var(--n-text-faint); }
.notes-search__hint {
    font-size: 0.7rem; color: var(--n-text-faint);
    background: var(--n-surface-soft); border: 1px solid var(--n-border-soft);
    border-radius: 0.35rem; padding: 0.1rem 0.4rem;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
}

.notes-newbtn {
    display: inline-flex; align-items: center; gap: 0.35rem;
    min-height: 2.2rem; padding: 0.35rem 0.85rem; border-radius: 0.6rem;
    font-weight: 600; white-space: nowrap; flex-shrink: 0;
}

.notes-newbtn__label { display: inline; }
@media (max-width: 380px) {
    .notes-newbtn__label { display: none; }
}

.notes-search__hint { display: none; }
@media (min-width: 768px) {
    .notes-search__hint { display: inline; }
}

@media (min-width: 768px) {
    .notes-header {
        flex-direction: row; align-items: center; justify-content: space-between; gap: 1.25rem;
    }
    .notes-header__title h1 { font-size: 1.6rem; }
    .notes-header__actions { flex: 0 1 auto; width: auto; }
    .notes-search { flex: 0 1 22rem; }
}

/* ==========================================================================
   Stat cards
   ========================================================================== */
.notes-stats {
    display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.65rem;
    margin-bottom: 1rem;
}
@media (min-width: 576px) { .notes-stats { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 992px) { .notes-stats { grid-template-columns: repeat(5, 1fr); } }

.notes-stat {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.9rem 1rem; background: var(--n-surface);
    border: 1px solid var(--n-border); border-radius: var(--n-radius);
    text-align: left; box-shadow: var(--n-shadow-sm);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.notes-stat:hover { transform: translateY(-1px); box-shadow: var(--n-shadow-md); }
.notes-stat--active { border-color: var(--bs-primary); box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.14); }

.notes-stat__icon {
    width: 2.5rem; height: 2.5rem; border-radius: 0.65rem;
    display: inline-flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.notes-stat--indigo .notes-stat__icon { background: #eef2ff; color: #4338ca; }
.notes-stat--sky    .notes-stat__icon { background: #e0f2fe; color: #0369a1; }
.notes-stat--amber  .notes-stat__icon { background: #fef3c7; color: #b45309; }
.notes-stat--rose   .notes-stat__icon { background: #ffe4e6; color: #be123c; }
.notes-stat--green  .notes-stat__icon { background: #dcfce7; color: #15803d; }

.notes-stat__body { display: flex; flex-direction: column; min-width: 0; }
.notes-stat__label {
    font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em;
    color: var(--n-text-muted); font-weight: 600;
}
.notes-stat__value {
    font-size: 1.35rem; font-weight: 700; color: var(--n-text); line-height: 1.1;
    font-variant-numeric: tabular-nums;
}

/* ==========================================================================
   Filter bar
   ========================================================================== */
.notes-filterbar {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: 0.65rem; padding: 0.5rem 0.65rem; margin-bottom: 1rem;
    background: var(--n-surface); border: 1px solid var(--n-border-soft);
    border-radius: var(--n-radius);
}
.notes-filterbar__filters {
    display: flex; flex-wrap: wrap; gap: 0.45rem; flex: 1 1 auto;
}
.notes-select { min-width: 8.5rem; }
.notes-select .form-select {
    border-color: var(--n-border); background-color: var(--n-surface);
    font-size: 0.82rem; border-radius: 0.55rem; min-height: 2.2rem;
}

.notes-viewtoggle {
    display: inline-flex; padding: 0.15rem; background: var(--n-surface-soft);
    border: 1px solid var(--n-border-soft); border-radius: 0.55rem;
}
.notes-viewtoggle__btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2rem; height: 1.8rem; border: 0; background: transparent;
    color: var(--n-text-muted); border-radius: 0.4rem;
}
.notes-viewtoggle__btn:hover { color: var(--n-text); }
.notes-viewtoggle__btn--active {
    background: var(--n-surface); color: var(--bs-primary);
    box-shadow: var(--n-shadow-sm);
}

/* ==========================================================================
   Main layout (list + detail split)
   ========================================================================== */
.notes-main {
    display: grid; grid-template-columns: 1fr; gap: 1rem; align-items: start;
}
@media (min-width: 992px) {
    .notes-main--split { grid-template-columns: minmax(0, 1fr) minmax(20rem, 22rem); }
}

.notes-section { margin-bottom: 1.25rem; }
.notes-section__title {
    display: inline-flex; align-items: center; gap: 0.4rem;
    font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em;
    font-weight: 700; color: var(--n-text-muted); margin: 0 0 0.5rem;
}
.notes-section__count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 1.25rem; height: 1.25rem; padding: 0 0.4rem;
    background: var(--n-surface-soft); border-radius: 999px;
    color: var(--n-text-muted); font-size: 0.7rem; font-weight: 600;
}

.notes-listgroup { list-style: none; padding: 0; margin: 0; }
.notes-listgroup--list { display: flex; flex-direction: column; gap: 0.4rem; }
.notes-listgroup--grid {
    display: grid; gap: 0.75rem;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
}

/* ==========================================================================
   Note row (both list + grid)
   ========================================================================== */
.notes-row {
    position: relative; display: flex; align-items: center; gap: 0.75rem;
    padding: 0.75rem 0.9rem; background: var(--n-surface);
    border: 1px solid var(--n-border); border-radius: var(--n-radius);
    text-align: left; width: 100%;
    transition: box-shadow 0.15s ease, border-color 0.15s ease, background 0.15s ease;
    cursor: pointer;
}
.notes-row:hover {
    border-color: #d3d7df;
    box-shadow: var(--n-shadow-sm);
}
.notes-row--selected {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.14);
}
.notes-row--done .notes-row__title,
.notes-row--done .notes-row__preview {
    color: var(--n-text-muted); text-decoration: line-through;
    text-decoration-color: rgba(15, 23, 42, 0.28);
}
.notes-row--pinned { background: linear-gradient(90deg, #fffbeb 0%, #ffffff 30%); }

.notes-row__check {
    width: 1.25rem; height: 1.25rem; flex-shrink: 0;
    border-radius: 0.35rem; border: 1.5px solid var(--n-border);
    background: var(--n-surface); display: inline-flex; align-items: center; justify-content: center;
    color: transparent;
}
.notes-row__check:hover { border-color: var(--n-done); }
.notes-row--done .notes-row__check {
    background: var(--n-done); border-color: var(--n-done); color: #fff;
}

.notes-row__main { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 0.15rem; }
.notes-row__topline { display: flex; align-items: center; gap: 0.5rem; min-width: 0; }
.notes-row__title {
    font-size: 0.92rem; font-weight: 600; color: var(--n-text);
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0;
}
.notes-row__preview {
    font-size: 0.8rem; color: var(--n-text-muted); line-height: 1.4;
    overflow: hidden; text-overflow: ellipsis; display: -webkit-box;
    -webkit-line-clamp: 1; -webkit-box-orient: vertical;
}

.notes-row__side {
    display: flex; align-items: center; gap: 0.55rem; flex-shrink: 0;
    color: var(--n-text-muted);
}
.notes-row__date {
    display: inline-flex; align-items: center; gap: 0.3rem;
    font-size: 0.72rem; color: var(--n-text-muted);
    background: var(--n-surface-soft); border: 1px solid var(--n-border-soft);
    padding: 0.15rem 0.5rem; border-radius: 0.4rem; white-space: nowrap;
}

.notes-avatar {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.8rem; height: 1.8rem; border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.12); color: var(--bs-primary);
    font-size: 0.72rem; font-weight: 700; flex-shrink: 0;
}
.notes-avatar--sm { width: 1.5rem; height: 1.5rem; font-size: 0.65rem; }

.notes-row__pin {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.85rem; height: 1.85rem; border: 0; background: transparent;
    border-radius: 0.4rem; color: var(--n-text-faint);
    transition: all 0.12s ease;
}
.notes-row__pin:hover { background: var(--n-surface-soft); color: var(--n-buy); }
.notes-row__pin--active { color: var(--n-buy); }

.notes-row__menu {
    position: relative;
}
.notes-row__menubtn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.85rem; height: 1.85rem; border: 0; background: transparent;
    border-radius: 0.4rem; color: var(--n-text-muted);
}
.notes-row__menubtn:hover { background: var(--n-surface-soft); color: var(--n-text); }
.notes-row__dropdown {
    position: absolute; top: calc(100% + 4px); right: 0; z-index: 40;
    min-width: 10rem; padding: 0.3rem; background: var(--n-surface);
    border: 1px solid var(--n-border); border-radius: 0.6rem;
    box-shadow: var(--n-shadow-md);
}
.notes-row__dropdown button {
    display: flex; align-items: center; gap: 0.55rem; width: 100%;
    padding: 0.45rem 0.6rem; border: 0; background: transparent;
    text-align: left; font-size: 0.82rem; color: var(--n-text);
    border-radius: 0.4rem;
}
.notes-row__dropdown button:hover { background: var(--n-surface-soft); }
.notes-row__dropdown button.danger { color: #b91c1c; }
.notes-row__dropdown button.danger:hover { background: #fee2e2; }

/* Grid card variant */
.notes-listgroup--grid .notes-row {
    flex-direction: column; align-items: stretch; gap: 0.5rem; padding: 0.9rem;
}
.notes-listgroup--grid .notes-row__side { align-self: stretch; justify-content: space-between; }
.notes-listgroup--grid .notes-row__main { gap: 0.3rem; }
.notes-listgroup--grid .notes-row__preview { -webkit-line-clamp: 3; }
.notes-listgroup--grid .notes-row__check { position: absolute; top: 0.6rem; right: 0.6rem; }

/* ==========================================================================
   Type chips (compact)
   ========================================================================== */
.notes-typechip {
    display: inline-flex; align-items: center; gap: 0.3rem; flex: 0 0 auto;
    border: 1px solid var(--n-border); background: var(--n-surface);
    color: var(--n-text-muted); border-radius: 999px;
    padding: 0.15rem 0.55rem; font-size: 0.7rem; font-weight: 600;
    line-height: 1.3; min-height: 1.55rem;
    transition: all 0.12s ease;
}
.notes-typechip__dot {
    width: 0.4rem; height: 0.4rem; border-radius: 50%;
    background: currentColor; opacity: 0.7; flex-shrink: 0;
}
.notes-typechip--buy      { color: var(--n-buy-ink); }
.notes-typechip--contact  { color: var(--n-contact-ink); }
.notes-typechip--reminder { color: var(--n-reminder-ink); }
.notes-typechip--general  { color: var(--n-general-ink); }
.notes-typechip--done     { color: var(--n-done-ink); }

.notes-typechip--buy      .notes-typechip__dot { background: var(--n-buy); opacity: 1; }
.notes-typechip--contact  .notes-typechip__dot { background: var(--n-contact); opacity: 1; }
.notes-typechip--reminder .notes-typechip__dot { background: var(--n-reminder); opacity: 1; }
.notes-typechip--general  .notes-typechip__dot { background: var(--n-general); opacity: 1; }

.notes-typechip--active { border-color: transparent; }
.notes-typechip--active.notes-typechip--buy      { background: var(--n-buy-soft); }
.notes-typechip--active.notes-typechip--contact  { background: var(--n-contact-soft); }
.notes-typechip--active.notes-typechip--reminder { background: var(--n-reminder-soft); }
.notes-typechip--active.notes-typechip--general  { background: var(--n-general-soft); color: var(--n-general-ink); }
.notes-typechip--active.notes-typechip--done     { background: var(--n-done-soft); }

/* ==========================================================================
   Detail panel
   ========================================================================== */
.notes-detail {
    position: sticky; top: 0.75rem;
    background: var(--n-surface); border: 1px solid var(--n-border);
    border-radius: var(--n-radius); padding: 1rem 1.1rem;
    box-shadow: var(--n-shadow-md);
    display: flex; flex-direction: column; gap: 0.85rem;
    max-height: calc(100vh - 2rem); overflow-y: auto;
}
.notes-detail__header {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem;
}
.notes-detail__tags {
    display: flex; flex-wrap: wrap; gap: 0.3rem;
}
.notes-detail__title {
    font-size: 1.1rem; font-weight: 700; margin: 0; color: var(--n-text);
    line-height: 1.3; letter-spacing: -0.01em;
}
.notes-detail__title--muted { color: var(--n-text-faint); font-weight: 500; }

.notes-detail__meta {
    display: flex; flex-direction: column; gap: 0.5rem;
    padding: 0.65rem 0; border-top: 1px solid var(--n-border-soft);
    border-bottom: 1px solid var(--n-border-soft);
}
.notes-detail__metarow {
    display: flex; align-items: center; gap: 0.55rem;
    font-size: 0.82rem; color: var(--n-text);
}
.notes-detail__metarow > svg { color: var(--n-text-muted); flex-shrink: 0; }
.notes-detail__assign { display: flex; flex-direction: column; line-height: 1.2; }
.notes-detail__assign-label { font-size: 0.7rem; color: var(--n-text-muted); }
.notes-detail__assign-name  { font-size: 0.85rem; font-weight: 600; color: var(--n-text); }

.notes-detail__section-title {
    font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em;
    color: var(--n-text-muted); font-weight: 700; margin: 0 0 0.45rem;
}
.notes-detail__body {
    margin: 0; white-space: pre-wrap; overflow-wrap: anywhere; word-break: break-word;
    line-height: 1.55; font-size: 0.9rem; color: var(--n-text);
}

.notes-timeline { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.55rem; }
.notes-timeline li {
    display: grid; grid-template-columns: 0.75rem 1fr auto; align-items: center; gap: 0.5rem;
    font-size: 0.8rem; color: var(--n-text);
}
.notes-timeline__dot {
    width: 0.65rem; height: 0.65rem; border-radius: 50%;
    background: var(--n-border); border: 2px solid var(--n-surface);
    box-shadow: 0 0 0 1px var(--n-border);
}
.notes-timeline__dot--primary { background: var(--bs-primary); box-shadow: 0 0 0 1px rgba(var(--bs-primary-rgb), 0.35); }
.notes-timeline__dot--success { background: var(--n-done); box-shadow: 0 0 0 1px rgba(22, 163, 74, 0.35); }
.notes-timeline__when { color: var(--n-text-muted); font-size: 0.72rem; }

.notes-detail__footer {
    display: flex; gap: 0.5rem; padding-top: 0.5rem;
    border-top: 1px solid var(--n-border-soft);
}

/* ==========================================================================
   Composer modal (uses Bootstrap .modal / .modal-* base classes)
   ========================================================================== */
.notes-modal { z-index: 1065; }

.notes-modal__content {
    border: 0;
    border-radius: 1rem;
    border-top: 3px solid var(--n-general);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.25);
    overflow: hidden;
}
.notes-modal__content--buy      { border-top-color: var(--n-buy); }
.notes-modal__content--contact  { border-top-color: var(--n-contact); }
.notes-modal__content--reminder { border-top-color: var(--n-reminder); }
.notes-modal__content--general  { border-top-color: var(--n-general); }

.notes-modal__content .modal-header {
    border-bottom: 1px solid var(--n-border-soft);
    padding: 0.85rem 1.1rem;
}
.notes-modal__content .modal-body {
    padding: 0.85rem 1.1rem;
}
.notes-modal__content .modal-footer {
    border-top: 1px solid var(--n-border-soft);
    padding: 0.75rem 1.1rem;
}

.notes-modal__title {
    width: 100%; border: 0; outline: none; background: transparent;
    font-size: 1rem; font-weight: 600; color: var(--n-text);
    padding: 0.1rem 0 0.5rem; border-bottom: 1px solid var(--n-border-soft);
}
.notes-modal__title:focus {
    box-shadow: none; border-bottom-color: var(--bs-primary);
}
.notes-modal__title::placeholder { color: var(--n-text-faint); font-weight: 500; }

.notes-modal__body {
    width: 100%; border: 0; outline: none; box-shadow: none;
    background: transparent; resize: vertical; min-height: 7rem;
    font-size: 0.95rem; line-height: 1.55; color: var(--n-text);
    padding: 0.65rem 0;
}
.notes-modal__body:focus { outline: none; box-shadow: none; }
.notes-modal__body::placeholder { color: var(--n-text-faint); }

.notes-modal__chips {
    display: flex; flex-wrap: wrap; gap: 0.3rem; padding-top: 0.5rem;
}
.notes-modal__count {
    font-size: 0.72rem; color: var(--n-text-faint); font-variant-numeric: tabular-nums;
}

/* ==========================================================================
   Empty state
   ========================================================================== */
.notes-empty {
    display: flex; flex-direction: column; align-items: center; text-align: center;
    padding: 3rem 1rem; background: var(--n-surface);
    border: 1px dashed var(--n-border); border-radius: var(--n-radius);
    color: var(--n-text-muted);
}
.notes-empty__icon {
    display: inline-flex; align-items: center; justify-content: center;
    width: 3.5rem; height: 3.5rem; border-radius: 50%;
    background: var(--n-surface-soft); color: var(--n-text-faint); margin-bottom: 0.75rem;
}
.notes-empty h3 { font-size: 1rem; font-weight: 600; color: var(--n-text); margin: 0 0 0.3rem; }
.notes-empty p { max-width: 24rem; margin: 0 0 1rem; font-size: 0.85rem; line-height: 1.5; }

/* ==========================================================================
   Icon buttons (shared)
   ========================================================================== */
.notes-iconbtn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 2rem; height: 2rem; border: 0; background: transparent;
    color: var(--n-text-muted); border-radius: 0.5rem;
    transition: all 0.12s ease;
}
.notes-iconbtn:hover { background: var(--n-surface-soft); color: var(--n-text); }

/* ==========================================================================
   Utilities
   ========================================================================== */
.text-truncate-2 {
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
</style>
