<template>
    <TenantShellLayout :page-title="t('notes.title', 'Notes')">
        <Head :title="t('notes.title', 'Notes')" />

        <div class="notes-page" :class="{ 'notes-page--sheet': selectedNote && !isDesktop }">
            <!-- Header row: title + subtitle + search + new note -->
            <header class="notes-header">
                <div class="notes-header__title">
                    <h1>{{ t('notes.title', 'Notes') }}</h1>
                    <p>{{ t('notes.subtitle', 'Organize buy lists, phone numbers & important reminders') }}</p>
                </div>
                <div class="notes-header__actions">
                    <label class="notes-search">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
                        </svg>
                        <input v-model="searchInput" type="search" :placeholder="t('notes.search_placeholder', 'Search notes…')" @input="onSearchInput" />
                        <span class="notes-search__hint">Ctrl+/</span>
                    </label>
                    <button
                        v-if="canManage"
                        type="button"
                        class="btn btn-primary btn-sm notes-newbtn"
                        :aria-label="t('notes.new_note', 'New note')"
                        @click="openComposer"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        <span class="notes-newbtn__label">{{ t('notes.new_note', 'New note') }}</span>
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
                            <option value="">{{ t('notes.all_categories', 'All types') }}</option>
                            <option v-for="type in types" :key="type" :value="type">{{ typeLabel(type) }}</option>
                        </select>
                    </div>
                    <div class="notes-select notes-select--tab">
                        <select v-model="tabValue" class="form-select form-select-sm" @change="onTabChange">
                            <option value="open">{{ t('notes.tab_open', 'Open') }}</option>
                            <option value="pinned">{{ t('notes.tab_pinned', 'Pinned') }}</option>
                            <option value="done">{{ t('notes.tab_done', 'Done') }}</option>
                            <option value="all">{{ t('notes.tab_all', 'All') }}</option>
                        </select>
                    </div>
                    <div class="notes-select">
                        <select v-model="sortValue" class="form-select form-select-sm">
                            <option value="latest">{{ t('notes.sort_latest', 'Latest') }}</option>
                            <option value="oldest">{{ t('notes.sort_oldest', 'Oldest') }}</option>
                            <option value="az">{{ t('notes.sort_az', 'A → Z') }}</option>
                        </select>
                    </div>
                </div>

                <div class="notes-viewtoggle" role="group" :aria-label="t('notes.view_mode', 'View mode')">
                    <button
                        type="button"
                        class="notes-viewtoggle__btn"
                        :class="{ 'notes-viewtoggle__btn--active': viewMode === 'list' }"
                        :aria-pressed="viewMode === 'list'"
                        :title="t('notes.list_view', 'List view')"
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
                        :title="t('notes.grid_view', 'Grid view')"
                        @click="viewMode = 'grid'"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Main content: list/grid + detail panel -->
            <div class="notes-main" :class="{ 'notes-main--split': selectedNote && isDesktop }">
                <section class="notes-main__list">
                    <!-- Pinned section -->
                    <div v-if="pinnedNotes.length" class="notes-section">
                        <h2 class="notes-section__title">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 17v5" /><path d="M9 3h6l-1 5 3 3v3H7v-3l3-3-1-5z" />
                            </svg>
                            {{ t('notes.tab_pinned', 'Pinned') }}
                            <span class="notes-section__count">{{ pinnedNotes.length }}</span>
                        </h2>
                        <component
                            :is="effectiveView === 'grid' ? 'div' : 'ul'"
                            class="notes-listgroup"
                            :class="`notes-listgroup--${effectiveView}`"
                        >
                            <NoteRow
                                v-for="note in pinnedNotes"
                                :key="`p-${note.id}`"
                                :note="note"
                                :view="effectiveView"
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
                            {{ t('notes.section_notes', 'Notes') }}
                            <span class="notes-section__count">{{ otherNotes.length }}</span>
                        </h2>
                        <component
                            :is="effectiveView === 'grid' ? 'div' : 'ul'"
                            class="notes-listgroup"
                            :class="`notes-listgroup--${effectiveView}`"
                        >
                            <NoteRow
                                v-for="note in otherNotes"
                                :key="note.id"
                                :note="note"
                                :view="effectiveView"
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
                            {{ t('notes.clear_filters', 'Clear filters') }}
                        </button>
                        <button v-else-if="canManage" type="button" class="btn btn-primary btn-sm" @click="openComposer">
                            {{ t('notes.new_note', 'New note') }}
                        </button>
                    </div>
                </section>

                <!-- Detail: side panel on desktop, bottom sheet on mobile -->
                <Teleport to="body" :disabled="isDesktop">
                    <div v-if="selectedNote" class="notes-detail-host">
                        <div
                            v-if="!isDesktop"
                            class="notes-detail-backdrop"
                            @click="selectedNote = null"
                        />
                        <aside
                            class="notes-detail"
                            :class="{ 'notes-detail--sheet': !isDesktop }"
                            role="dialog"
                            aria-modal="true"
                            :aria-label="selectedNote.title || t('notes.untitled', 'Untitled note')"
                        >
                            <div v-if="!isDesktop" class="notes-detail__handle" aria-hidden="true" />

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
                                        {{ t('notes.tab_pinned', 'Pinned') }}
                                    </span>
                                    <span v-if="selectedNote.is_done" class="notes-typechip notes-typechip--done notes-typechip--active">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12l5 5L20 7" />
                                        </svg>
                                        {{ t('notes.tab_done', 'Done') }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="notes-iconbtn notes-detail__close"
                                    :aria-label="t('notes.close', 'Close')"
                                    :title="t('notes.close', 'Close')"
                                    @click="selectedNote = null"
                                >
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                                        <path d="M6 6l12 12M18 6L6 18" />
                                    </svg>
                                </button>
                            </header>

                            <div class="notes-detail__scroll">
                                <h2 v-if="selectedNote.title" class="notes-detail__title">{{ selectedNote.title }}</h2>
                                <h2 v-else class="notes-detail__title notes-detail__title--muted">{{ t('notes.untitled', 'Untitled note') }}</h2>

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
                                            <span class="notes-detail__assign-label">{{ t('notes.created_by', 'Created by') }}</span>
                                            <span class="notes-detail__assign-name">{{ selectedNote.user.name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="notes-detail__section">
                                    <h3 class="notes-detail__section-title">{{ t('notes.description', 'Description') }}</h3>
                                    <p class="notes-detail__body">{{ selectedNote.body }}</p>
                                </div>

                                <div v-if="selectedNote.updated_at !== selectedNote.created_at || selectedNote.done_at" class="notes-detail__section">
                                    <h3 class="notes-detail__section-title">{{ t('notes.activity', 'Activity') }}</h3>
                                    <ul class="notes-timeline">
                                        <li>
                                            <span class="notes-timeline__dot notes-timeline__dot--primary" />
                                            <span>{{ t('notes.note_created', 'Note created') }}</span>
                                            <span class="notes-timeline__when">{{ formatDateFull(selectedNote.created_at) }}</span>
                                        </li>
                                        <li v-if="selectedNote.updated_at !== selectedNote.created_at">
                                            <span class="notes-timeline__dot" />
                                            <span>{{ t('notes.last_updated', 'Last updated') }}</span>
                                            <span class="notes-timeline__when">{{ formatDateFull(selectedNote.updated_at) }}</span>
                                        </li>
                                        <li v-if="selectedNote.done_at">
                                            <span class="notes-timeline__dot notes-timeline__dot--success" />
                                            <span>{{ t('notes.marked_completed_at', 'Marked completed') }}</span>
                                            <span class="notes-timeline__when">{{ formatDateFull(selectedNote.done_at) }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <footer v-if="canManage" class="notes-detail__footer">
                                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" @click="startEdit(selectedNote)">
                                    {{ t('notes.edit', 'Edit') }}
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-primary btn-sm flex-fill"
                                    @click="toggleDone(selectedNote)"
                                >
                                    {{ selectedNote.is_done ? t('notes.reopen', 'Reopen') : t('notes.mark_completed', 'Mark completed') }}
                                </button>
                            </footer>
                        </aside>
                    </div>
                </Teleport>
            </div>

            <button
                v-if="canManage && !composerOpen && !(selectedNote && !isDesktop)"
                type="button"
                class="notes-fab"
                :aria-label="t('notes.new_note', 'New note')"
                @click="openComposer"
            >
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 5v14M5 12h14" />
                </svg>
            </button>
        </div>

        <!-- Composer modal (create + edit) — Bootstrap modal pattern -->
        <Teleport to="body">
            <div v-if="composerOpen" class="modal-backdrop fade show notes-modal-backdrop" />
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
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable notes-modal__dialog" role="document" @mousedown.stop>
                    <div class="modal-content notes-modal__content" :class="`notes-modal__content--${composer.type}`">
                        <div class="notes-modal__handle d-lg-none" aria-hidden="true" />
                        <div class="modal-header">
                            <h2 id="notes-composer-title" class="modal-title h5 mb-0">
                                {{ editingId ? t('notes.edit_note', 'Edit note') : t('notes.new_note', 'New note') }}
                            </h2>
                            <button
                                type="button"
                                class="notes-iconbtn notes-modal__close"
                                :aria-label="t('notes.close', 'Close')"
                                :title="t('notes.close', 'Close')"
                                :disabled="composer.processing"
                                @click="cancelComposer"
                            >
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M6 6l12 12M18 6L6 18" />
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitComposer">
                            <div class="modal-body">
                                <input
                                    ref="titleInputRef"
                                    v-model="composer.title"
                                    type="text"
                                    class="notes-modal__title"
                                    maxlength="120"
                                    :placeholder="t('notes.title_placeholder', 'Add a title (optional)')"
                                />
                                <textarea
                                    ref="bodyRef"
                                    v-model="composer.body"
                                    class="notes-modal__body"
                                    rows="6"
                                    :placeholder="t('notes.composer_placeholder', 'What came to mind? Medicine to buy, a phone number, a reminder…')"
                                    @keydown="onComposerKeydown"
                                />

                                <div class="notes-typepick">
                                    <div id="notes-typepick-label" class="notes-typepick__label">
                                        {{ t('notes.type_label', 'Note type') }}
                                    </div>
                                    <div
                                        class="notes-typepick__grid"
                                        role="radiogroup"
                                        aria-labelledby="notes-typepick-label"
                                    >
                                        <button
                                            v-for="type in types"
                                            :key="type"
                                            type="button"
                                            role="radio"
                                            class="notes-typepick__btn"
                                            :class="[`notes-typepick__btn--${type}`, { 'is-active': composer.type === type }]"
                                            :aria-checked="composer.type === type"
                                            @click.stop="setComposerType(type)"
                                        >
                                            <span class="notes-typepick__icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <template v-if="type === 'buy'">
                                                        <circle cx="9" cy="20" r="1.25" />
                                                        <circle cx="18" cy="20" r="1.25" />
                                                        <path d="M3 3h2l.4 2M7 13h9.5l2.1-8H6.2" />
                                                        <path d="M7 13 5.4 5H3" />
                                                    </template>
                                                    <template v-else-if="type === 'contact'">
                                                        <path d="M22 16.9v2.2a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 3.2 2 2 0 0 1 4 1h2.2a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.6a2 2 0 0 1-.5 2.1L7.1 8.6a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.5 2.6.6a2 2 0 0 1 1.7 2.1z" />
                                                    </template>
                                                    <template v-else-if="type === 'reminder'">
                                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
                                                        <path d="M13.7 21a2 2 0 0 1-3.4 0" />
                                                    </template>
                                                    <template v-else>
                                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                        <path d="M14 2v6h6M8 13h8M8 17h6" />
                                                    </template>
                                                </svg>
                                            </span>
                                            <span class="notes-typepick__text">
                                                <span class="notes-typepick__name">{{ typeLabel(type) }}</span>
                                                <span class="notes-typepick__hint">{{ typeHint(type) }}</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <span class="notes-modal__count me-auto">
                                    {{ t('notes.char_count', { count: composer.body.length, max: 5000 }) }}
                                </span>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    :disabled="composer.processing"
                                    @click="cancelComposer"
                                >
                                    {{ t('notes.cancel', 'Cancel') }}
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                    :disabled="composer.processing || !composer.body.trim()"
                                >
                                    <span v-if="composer.processing" class="spinner-border spinner-border-sm me-1" role="status" />
                                    {{
                                        composer.processing
                                            ? t('notes.saving', 'Saving…')
                                            : editingId
                                                ? t('notes.save_changes', 'Save changes')
                                                : t('notes.save', 'Save note')
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <ConfirmModal
            :show="deleteTarget !== null"
            :title="t('notes.confirm_delete', 'Delete this note?')"
            :confirm-label="t('notes.delete', 'Delete')"
            confirm-class="btn-danger"
            :processing="deleteProcessing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        >
            <p class="mb-0 text-muted">{{ t('notes.confirm_delete_body', 'This note will be permanently removed.') }}</p>
            <p v-if="deleteTarget?.title" class="mt-2 mb-0 fw-semibold">{{ deleteTarget.title }}</p>
            <p v-if="deleteTarget?.body" class="mb-0 text-truncate-2 small text-muted">{{ deleteTarget.body }}</p>
        </ConfirmModal>
    </TenantShellLayout>
</template>

<script setup>
import ConfirmModal from '@/Components/ConfirmModal.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
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
const { t } = useLocale();
const canManage = computed(() => can('notes.manage'));

const TYPE_LABELS = { buy: 'Buy', contact: 'Contact', reminder: 'Reminder', general: 'General' };
const TYPE_HINTS = {
    buy: 'Medicines to buy',
    contact: 'Phone or person',
    reminder: 'Don’t forget',
    general: 'Anything else',
};

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
const isDesktop = ref(typeof window === 'undefined' ? true : window.matchMedia('(min-width: 992px)').matches);
const effectiveView = computed(() => (isDesktop.value ? viewMode.value : 'list'));

function updateIsDesktop() {
    isDesktop.value = window.matchMedia('(min-width: 992px)').matches;
}

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
        return { title: t('notes.empty_filtered_title', 'No matching notes'), body: t('notes.empty_filtered_body', 'Try a different tab, type, or search term.') };
    }
    if (props.filters.tab === 'pinned') {
        return { title: t('notes.empty_pinned_title', 'No pinned notes'), body: t('notes.empty_pinned_body', 'Pin important notes so they always stay on top.') };
    }
    if (props.filters.tab === 'done') {
        return { title: t('notes.empty_done_title', 'No completed notes yet'), body: t('notes.empty_done_body', 'Notes you mark as done will show up here.') };
    }
    return { title: t('notes.empty_open_title', 'Nothing on your list'), body: t('notes.empty_open_body', 'Capture medicines to buy, a phone number, or a reminder — it stays here for the whole team.') };
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
    { key: 'all',    label: t('notes.stat_all', 'All'),       value: props.counts.all ?? 0,    tone: 'indigo', icon: StatIcon.file,  active: tabValue.value === 'all',    onClick: () => setTab('all') },
    { key: 'today',  label: t('notes.stat_today', 'Today'),   value: props.counts.today ?? 0,  tone: 'sky',    icon: StatIcon.today, active: false,                        onClick: () => setTab('all') },
    { key: 'open',   label: t('notes.stat_open', 'Open'),     value: props.counts.open ?? 0,   tone: 'amber',  icon: StatIcon.open,  active: tabValue.value === 'open',   onClick: () => setTab('open') },
    { key: 'pinned', label: t('notes.stat_pinned', 'Pinned'), value: props.counts.pinned ?? 0, tone: 'rose',   icon: StatIcon.pin,   active: tabValue.value === 'pinned', onClick: () => setTab('pinned') },
    { key: 'done',   label: t('notes.stat_completed', 'Done'), value: props.counts.done ?? 0,  tone: 'green',  icon: StatIcon.done,  active: tabValue.value === 'done',   onClick: () => setTab('done') },
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

function setComposerType(type) {
    composer.type = type;
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

/* Lock body scroll while composer or mobile detail sheet is open */
watch([composerOpen, selectedNote, isDesktop], () => {
    if (typeof document === 'undefined') return;
    const lock = composerOpen.value || (!!selectedNote.value && !isDesktop.value);
    document.body.classList.toggle('modal-open', lock);
    document.body.style.overflow = lock ? 'hidden' : '';
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
        return;
    }
    if (e.key === 'Escape' && selectedNote.value && !composerOpen.value && !deleteTarget.value) {
        selectedNote.value = null;
    }
}
onMounted(() => {
    updateIsDesktop();
    window.addEventListener('resize', updateIsDesktop);
    window.addEventListener('keydown', onGlobalKey);
});
onBeforeUnmount(() => {
    window.removeEventListener('resize', updateIsDesktop);
    window.removeEventListener('keydown', onGlobalKey);
    if (typeof document !== 'undefined') {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
    }
});

/* ---------- Utils ---------- */
function typeLabel(type) {
    return t(`notes.type_${type}`, TYPE_LABELS[type] ?? type);
}

function typeHint(type) {
    return t(`notes.type_hint_${type}`, TYPE_HINTS[type] ?? '');
}
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
   Design tokens (also on .notes-modal — Teleport to body loses .notes-page scope)
   ========================================================================== */
.notes-page,
.notes-modal,
.notes-detail {
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
}

.notes-page {
    max-width: 1280px;
    margin: 0 auto;
    color: var(--n-text);
}
@media (max-width: 991.98px) {
    .notes-page { padding-bottom: 4.25rem; }
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

.notes-search__hint { display: none; }
@media (min-width: 768px) {
    .notes-search__hint { display: inline; }
}

@media (max-width: 991.98px) {
    .notes-header { margin-bottom: 0.65rem; gap: 0.55rem; }
    .notes-header__title h1 { font-size: 1.2rem; }
    .notes-header__title p { display: none; }
    .notes-newbtn { display: none; }
}

@media (min-width: 768px) {
    .notes-header {
        flex-direction: row; align-items: center; justify-content: space-between; gap: 1.25rem;
    }
    .notes-header__title h1 { font-size: 1.6rem; }
    .notes-header__actions { flex: 0 1 auto; width: auto; }
    .notes-search { flex: 0 1 22rem; }
}
@media (min-width: 768px) and (max-width: 991.98px) {
    .notes-header__title h1 { font-size: 1.3rem; }
}

/* ==========================================================================
   Stat cards
   ========================================================================== */
.notes-stats {
    display: flex; gap: 0.45rem; overflow-x: auto;
    margin: 0 -0.15rem 0.75rem; padding: 0 0.15rem 0.35rem;
    -webkit-overflow-scrolling: touch; scrollbar-width: none;
}
.notes-stats::-webkit-scrollbar { display: none; }
@media (min-width: 992px) {
    .notes-stats {
        display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.65rem;
        margin: 0 0 1rem; padding: 0; overflow: visible;
    }
}

.notes-stat {
    display: flex; align-items: center; gap: 0.45rem;
    flex: 0 0 auto; min-height: 2.6rem;
    padding: 0.4rem 0.75rem; background: var(--n-surface);
    border: 1px solid var(--n-border); border-radius: 999px;
    text-align: left; box-shadow: var(--n-shadow-sm);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.notes-stat:hover { transform: translateY(-1px); box-shadow: var(--n-shadow-md); }
.notes-stat--active { border-color: var(--bs-primary); box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.14); }

.notes-stat__icon {
    width: 1.7rem; height: 1.7rem; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.notes-stat__icon :deep(svg) { width: 14px; height: 14px; }
@media (min-width: 992px) {
    .notes-stat {
        gap: 0.75rem; padding: 0.9rem 1rem; border-radius: var(--n-radius); min-height: 0;
    }
    .notes-stat__icon { width: 2.5rem; height: 2.5rem; border-radius: 0.65rem; }
    .notes-stat__icon :deep(svg) { width: 18px; height: 18px; }
}
.notes-stat--indigo .notes-stat__icon { background: #eef2ff; color: #4338ca; }
.notes-stat--sky    .notes-stat__icon { background: #e0f2fe; color: #0369a1; }
.notes-stat--amber  .notes-stat__icon { background: #fef3c7; color: #b45309; }
.notes-stat--rose   .notes-stat__icon { background: #ffe4e6; color: #be123c; }
.notes-stat--green  .notes-stat__icon { background: #dcfce7; color: #15803d; }

.notes-stat__body { display: flex; flex-direction: column; min-width: 0; }
.notes-stat__label {
    font-size: 0.68rem; letter-spacing: 0; text-transform: none;
    color: var(--n-text-muted); font-weight: 600; white-space: nowrap;
}
.notes-stat__value {
    font-size: 0.95rem; font-weight: 700; color: var(--n-text); line-height: 1.1;
    font-variant-numeric: tabular-nums;
}
@media (min-width: 992px) {
    .notes-stat__label {
        font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.04em;
    }
    .notes-stat__value { font-size: 1.35rem; }
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
@media (max-width: 991.98px) {
    .notes-filterbar { padding: 0.4rem 0.5rem; margin-bottom: 0.75rem; }
    .notes-select { min-width: 0; flex: 1 1 0; }
    .notes-select--tab,
    .notes-viewtoggle { display: none; }
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
    display: grid; gap: 0.85rem;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
}
@media (min-width: 1200px) {
    .notes-listgroup--grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

.notes-avatar {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.8rem; height: 1.8rem; border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.12); color: var(--bs-primary);
    font-size: 0.72rem; font-weight: 700; flex-shrink: 0;
}
.notes-avatar--sm { width: 1.5rem; height: 1.5rem; font-size: 0.65rem; }

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
.notes-detail-host { display: contents; }
.notes-detail-backdrop {
    position: fixed; inset: 0; z-index: 1054;
    background: rgba(15, 23, 42, 0.45);
}
.notes-detail {
    position: sticky; top: 0.75rem;
    background: var(--n-surface); border: 1px solid var(--n-border);
    border-radius: var(--n-radius); padding: 1rem 1.1rem;
    box-shadow: var(--n-shadow-md);
    display: flex; flex-direction: column; gap: 0.85rem;
    max-height: calc(100vh - 2rem); overflow-y: auto;
}
.notes-detail--sheet {
    position: fixed;
    left: 0; right: 0; bottom: 0;
    top: auto;
    z-index: 1055;
    width: 100%;
    max-height: min(85dvh, 85vh);
    margin: 0;
    padding: 0.35rem 1rem 0;
    border: 0;
    border-radius: 1.1rem 1.1rem 0 0;
    box-shadow: 0 -10px 30px rgba(15, 23, 42, 0.18);
    overflow: hidden;
    animation: notes-sheet-up 0.22s ease-out;
}
@keyframes notes-sheet-up {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}
.notes-detail__handle {
    width: 2.5rem;
    height: 0.28rem;
    margin: 0.2rem auto 0.45rem;
    border-radius: 999px;
    background: #cbd5e1;
    flex-shrink: 0;
}
.notes-detail__close {
    width: 2.5rem;
    height: 2.5rem;
    flex-shrink: 0;
    background: var(--n-surface-soft);
    color: var(--n-text);
}
.notes-detail__close:hover {
    background: #eef2f6;
    color: var(--n-text);
}
.notes-detail__header {
    display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem;
    flex-shrink: 0;
}
.notes-detail__tags {
    display: flex; flex-wrap: wrap; gap: 0.3rem;
    padding-top: 0.25rem;
}
.notes-detail__scroll {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    min-height: 0;
}
.notes-detail--sheet .notes-detail__scroll {
    flex: 1 1 auto;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 0.35rem;
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
    margin-top: auto;
    flex-shrink: 0;
}
.notes-detail--sheet .notes-detail__footer {
    margin: 0 -1rem 0;
    padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom));
    background: var(--n-surface);
    border-top: 1px solid var(--n-border-soft);
}
.notes-detail--sheet .notes-detail__footer .btn { min-height: 2.75rem; }

/* ==========================================================================
   Composer modal (uses Bootstrap .modal / .modal-* base classes)
   ========================================================================== */
.notes-modal-backdrop { z-index: 1060; }
.notes-modal { z-index: 1065; pointer-events: auto; }

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

.notes-modal__handle {
    display: none;
}
.notes-modal__close {
    width: 2.5rem;
    height: 2.5rem;
    flex-shrink: 0;
    background: var(--n-surface-soft);
    color: var(--n-text);
}
.notes-modal__close:hover {
    background: #eef2f6;
    color: var(--n-text);
}
.notes-modal__close:disabled {
    opacity: 0.55;
}

.notes-modal__content .modal-header {
    border-bottom: 1px solid var(--n-border-soft);
    padding: 0.85rem 1.1rem;
    align-items: center;
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
    width: 100%;
    border: 1px solid var(--n-border);
    outline: none;
    box-shadow: none;
    background: var(--n-surface-soft);
    resize: vertical;
    min-height: 8.5rem;
    border-radius: 0.75rem;
    font-size: 0.98rem;
    line-height: 1.55;
    color: var(--n-text);
    padding: 0.75rem 0.85rem;
    margin: 0.75rem 0 0;
    transition: border-color 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
}
.notes-modal__body:focus {
    outline: none;
    background: #fff;
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.14);
}
.notes-modal__body::placeholder { color: var(--n-text-faint); }

.notes-typepick {
    padding-top: 0.85rem;
    margin-top: 0.35rem;
    border-top: 1px solid var(--n-border-soft);
}
.notes-typepick__label {
    margin-bottom: 0.45rem;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: var(--n-text-faint);
}
.notes-typepick__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.45rem;
}
.notes-typepick__btn {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    width: 100%;
    min-height: 2.65rem;
    padding: 0.4rem 0.55rem;
    border: 1px solid var(--n-border);
    border-radius: 0.6rem;
    background: var(--n-surface);
    color: var(--n-text-muted);
    text-align: left;
    cursor: pointer;
    transition: border-color 0.12s ease, background 0.12s ease, color 0.12s ease;
}
.notes-typepick__btn:hover {
    border-color: #cbd5e1;
    color: var(--n-text);
}
.notes-typepick__btn:focus-visible {
    outline: 0;
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.18);
}
.notes-typepick__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.65rem;
    height: 1.65rem;
    flex-shrink: 0;
    border-radius: 0.45rem;
    background: var(--n-surface-soft);
    color: var(--n-text-muted);
}
.notes-typepick__icon svg {
    width: 14px;
    height: 14px;
}
.notes-typepick__text {
    display: flex;
    flex-direction: column;
    min-width: 0;
    line-height: 1.2;
}
.notes-typepick__name {
    font-size: 0.8rem;
    font-weight: 600;
}
.notes-typepick__hint {
    margin-top: 0.1rem;
    font-size: 0.65rem;
    color: var(--n-text-faint);
    font-weight: 500;
}

.notes-typepick__btn--buy .notes-typepick__icon      { color: var(--n-buy-ink); background: var(--n-buy-soft); }
.notes-typepick__btn--contact .notes-typepick__icon  { color: var(--n-contact-ink); background: var(--n-contact-soft); }
.notes-typepick__btn--reminder .notes-typepick__icon { color: var(--n-reminder-ink); background: var(--n-reminder-soft); }
.notes-typepick__btn--general .notes-typepick__icon  { color: var(--n-general-ink); background: var(--n-general-soft); }

.notes-typepick__btn.is-active {
    color: var(--n-text);
    background: var(--n-surface);
    border-color: #94a3b8;
    box-shadow: none;
}
.notes-typepick__btn--buy.is-active      { border-color: var(--n-buy); color: var(--n-buy-ink); }
.notes-typepick__btn--contact.is-active  { border-color: var(--n-contact); color: var(--n-contact-ink); }
.notes-typepick__btn--reminder.is-active { border-color: var(--n-reminder); color: var(--n-reminder-ink); }
.notes-typepick__btn--general.is-active  { border-color: var(--n-general); color: var(--n-general-ink); }

.notes-typepick__btn.is-active .notes-typepick__hint { color: inherit; opacity: 0.7; }

.notes-modal__count {
    font-size: 0.72rem; color: var(--n-text-faint); font-variant-numeric: tabular-nums;
}

@media (max-width: 991.98px) {
    .notes-modal {
        display: flex !important;
        align-items: flex-end;
        padding: 0;
    }
    .notes-modal__dialog {
        margin: 0;
        max-width: 100%;
        width: 100%;
        height: auto;
        min-height: 0;
        max-height: min(85dvh, 85vh);
    }
    .notes-modal__dialog.modal-dialog-centered {
        display: flex;
        align-items: flex-end;
        min-height: 0;
    }
    .notes-modal__content {
        width: 100%;
        max-height: min(85dvh, 85vh);
        min-height: 0;
        border-radius: 1.1rem 1.1rem 0 0;
        border-top-width: 3px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 -10px 30px rgba(15, 23, 42, 0.18);
        animation: notes-sheet-up 0.22s ease-out;
    }
    .notes-modal__handle {
        display: block;
        width: 2.5rem;
        height: 0.28rem;
        margin: 0.45rem auto 0.15rem;
        border-radius: 999px;
        background: #cbd5e1;
        flex-shrink: 0;
    }
    .notes-modal__content .modal-header {
        padding: 0.55rem 1rem 0.65rem;
        flex-shrink: 0;
    }
    .notes-modal__content form {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }
    .notes-modal__content .modal-body {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding: 0.75rem 1rem;
    }
    .notes-modal__body {
        flex: 1 1 auto;
        min-height: 9rem;
        font-size: 1.02rem;
        padding: 0.9rem 1rem;
        resize: none;
    }
    .notes-typepick {
        flex: 0 0 auto;
        padding-top: 0.7rem;
        margin-top: 0.5rem;
    }
    .notes-typepick__grid {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.4rem;
        overflow-x: auto;
        padding-bottom: 0.15rem;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .notes-typepick__grid::-webkit-scrollbar { display: none; }
    .notes-typepick__btn {
        flex: 1 1 0;
        min-width: 0;
        min-height: 2.5rem;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 0.25rem;
        padding: 0.4rem 0.3rem;
        text-align: center;
    }
    .notes-typepick__icon {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 0.4rem;
    }
    .notes-typepick__icon svg {
        width: 13px;
        height: 13px;
    }
    .notes-typepick__name {
        font-size: 0.68rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .notes-typepick__hint { display: none; }
    .notes-typepick__btn.is-active {
        background: var(--n-surface-soft);
    }
    .notes-modal__content .modal-footer {
        flex-shrink: 0;
        margin: 0;
        padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom));
        background: var(--n-surface);
    }
    .notes-modal__content .modal-footer .btn { min-height: 2.75rem; }
}

.notes-fab {
    display: none;
}
@media (max-width: 991.98px) {
    .notes-fab {
        display: inline-flex; align-items: center; justify-content: center;
        position: fixed; z-index: 1040;
        right: 1rem;
        bottom: calc(4.75rem + env(safe-area-inset-bottom) + 0.75rem);
        width: 3.5rem; height: 3.5rem; border: 0; border-radius: 50%;
        background: var(--bs-primary); color: #fff;
        box-shadow: 0 8px 20px rgba(var(--bs-primary-rgb), 0.4);
    }
    .notes-fab:focus-visible {
        outline: 0; box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.35);
    }
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
@media (max-width: 991.98px) {
    .notes-iconbtn { width: 2.75rem; height: 2.75rem; }
}

/* ==========================================================================
   Utilities
   ========================================================================== */
.text-truncate-2 {
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
</style>
