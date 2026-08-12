<template>
    <component
        :is="wrapperTag"
        class="notes-row"
        :class="[
            `notes-row--${note.type}`,
            {
                'notes-row--grid': view === 'grid',
                'notes-row--done': note.is_done,
                'notes-row--pinned': note.is_pinned && !note.is_done,
                'notes-row--selected': selected,
            },
        ]"
        @click="$emit('select', note)"
    >
        <button
            v-if="canManage"
            type="button"
            class="notes-row__check"
            :class="{ 'notes-row__check--done': note.is_done }"
            :aria-label="note.is_done ? t('notes.reopen', 'Reopen') : t('notes.mark_completed', 'Mark completed')"
            :title="note.is_done ? t('notes.reopen', 'Reopen') : t('notes.mark_completed', 'Mark completed')"
            @click.stop="$emit('toggle-done', note)"
        >
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12l5 5L20 7" />
            </svg>
        </button>

        <div class="notes-row__body">
            <div class="notes-row__topline">
                <span v-if="note.title" class="notes-row__title">{{ note.title }}</span>
                <span v-else class="notes-row__title">{{ firstLine(note.body) }}</span>
                <span
                    class="notes-typechip"
                    :class="[`notes-typechip--${note.type}`, 'notes-typechip--active']"
                >
                    <span class="notes-typechip__dot" />
                    {{ typeLabel(note.type) }}
                </span>
            </div>

            <p v-if="note.title || view === 'grid'" class="notes-row__preview">
                {{ note.body }}
            </p>

            <div class="notes-row__meta">
                <div class="notes-row__meta-info">
                    <span class="notes-row__date" :title="fullDate(note.updated_at)">
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                        {{ relativeDate(note.updated_at) }}
                    </span>

                    <span v-if="note.user?.name" class="notes-avatar" :title="note.user.name">
                        {{ initials(note.user.name) }}
                    </span>
                </div>

                <div v-if="canManage" class="notes-row__actions">
                    <button
                        type="button"
                        class="notes-row__iconbtn notes-row__iconbtn--pin"
                        :class="{ 'is-active': note.is_pinned }"
                        :aria-pressed="note.is_pinned"
                        :aria-label="note.is_pinned ? t('notes.unpin', 'Unpin') : t('notes.pin', 'Pin')"
                        :title="note.is_pinned ? t('notes.unpin', 'Unpin') : t('notes.pin', 'Pin')"
                        @click.stop="$emit('toggle-pin', note)"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 17v5" />
                            <path d="M9 3h6l-1 5 3 3v3H7v-3l3-3-1-5z" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="notes-row__iconbtn notes-row__iconbtn--edit"
                        :aria-label="t('notes.edit', 'Edit')"
                        :title="t('notes.edit', 'Edit')"
                        @click.stop="$emit('edit', note)"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20h9" />
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                        </svg>
                    </button>

                    <button
                        type="button"
                        class="notes-row__iconbtn notes-row__iconbtn--danger"
                        :aria-label="t('notes.delete', 'Delete')"
                        :title="t('notes.delete', 'Delete')"
                        @click.stop="$emit('delete', note)"
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18" />
                            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </component>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { computed } from 'vue';

const props = defineProps({
    note: { type: Object, required: true },
    view: { type: String, default: 'list' },
    selected: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
});

defineEmits(['select', 'toggle-done', 'toggle-pin', 'edit', 'delete']);
const { t } = useLocale();

const wrapperTag = computed(() => (props.view === 'grid' ? 'div' : 'li'));

const TYPE_LABELS = { buy: 'Buy', contact: 'Contact', reminder: 'Reminder', general: 'General' };

function typeLabel(type) {
    return t(`notes.type_${type}`, TYPE_LABELS[type] ?? type);
}

function initials(name) {
    if (!name) return '';
    return name.trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('');
}

function firstLine(body) {
    if (!body) return '';
    const line = body.split('\n')[0].trim();
    return line.length > 70 ? `${line.slice(0, 70)}…` : line;
}

function relativeDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    const diffSec = Math.round((Date.now() - d.getTime()) / 1000);
    if (Number.isNaN(diffSec)) return '';
    if (diffSec < 60) return t('notes.just_now', 'just now');
    if (diffSec < 3600) return t('notes.minutes_ago', { count: Math.floor(diffSec / 60) }, `${Math.floor(diffSec / 60)}m ago`);
    if (diffSec < 86400) return t('notes.hours_ago', { count: Math.floor(diffSec / 3600) }, `${Math.floor(diffSec / 3600)}h ago`);
    if (diffSec < 604800) return t('notes.days_ago', { count: Math.floor(diffSec / 86400) }, `${Math.floor(diffSec / 86400)}d ago`);
    return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short' });
}

function fullDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString();
}
</script>

<style scoped>
.notes-row {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    width: 100%;
    padding: 0.85rem 0.95rem;
    background: #fff;
    border: 1px solid var(--n-border, #e6e8ee);
    border-radius: 0.85rem;
    text-align: left;
    cursor: pointer;
    transition: box-shadow 0.15s ease, border-color 0.15s ease, background 0.15s ease;
}

.notes-row:hover {
    border-color: #d3d7df;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}

.notes-row--selected {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 3px rgba(var(--bs-primary-rgb), 0.14);
}

.notes-row--pinned {
    background: linear-gradient(90deg, #fffbeb 0%, #ffffff 42%);
}

.notes-row--done .notes-row__title,
.notes-row--done .notes-row__preview {
    color: #6b7280;
    text-decoration: line-through;
    text-decoration-color: rgba(15, 23, 42, 0.28);
}

.notes-row__check,
.notes-row__iconbtn {
    appearance: none;
    -webkit-appearance: none;
    margin: 0;
    padding: 0;
    box-shadow: none;
    cursor: pointer;
}

.notes-row__check {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.7rem;
    height: 1.7rem;
    margin-top: 0.05rem;
    flex-shrink: 0;
    border: 1.5px solid #86efac;
    border-radius: 50%;
    background: #f0fdf4;
    color: #16a34a;
}

.notes-row__check:hover {
    border-color: #16a34a;
    background: #dcfce7;
    color: #15803d;
}

.notes-row__check--done,
.notes-row--done .notes-row__check {
    background: var(--n-done, #16a34a);
    border-color: var(--n-done, #16a34a);
    color: #fff;
}

.notes-row__body {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
    flex: 1 1 auto;
}

.notes-row__topline {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 0;
}

.notes-row__title {
    min-width: 0;
    overflow: hidden;
    color: #111827;
    font-size: 0.94rem;
    font-weight: 650;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notes-row__preview {
    display: -webkit-box;
    overflow: hidden;
    margin: 0;
    color: #6b7280;
    font-size: 0.8rem;
    line-height: 1.45;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.notes-row__meta {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    min-width: 0;
}

.notes-row__meta-info {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    min-width: 0;
}

.notes-row__date {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    padding: 0.12rem 0.45rem;
    border: 1px solid var(--n-border-soft, #eff1f5);
    border-radius: 999px;
    background: #f7f8fb;
    color: #6b7280;
    font-size: 0.7rem;
    white-space: nowrap;
}

.notes-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.55rem;
    height: 1.55rem;
    flex-shrink: 0;
    border-radius: 50%;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
    font-size: 0.62rem;
    font-weight: 700;
}

.notes-row__actions {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    margin-left: auto;
    flex-shrink: 0;
}

.notes-row__iconbtn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.15rem;
    height: 2.15rem;
    border: 0;
    border-radius: 0.55rem;
    background: #f3f4f6;
    color: #6b7280;
}

.notes-row__iconbtn--pin {
    background: #fef3c7;
    color: #d97706;
}

.notes-row__iconbtn--pin:hover,
.notes-row__iconbtn--pin.is-active {
    background: #fde68a;
    color: #b45309;
}

.notes-row__iconbtn--edit {
    background: #dbeafe;
    color: #2563eb;
}

.notes-row__iconbtn--edit:hover {
    background: #bfdbfe;
    color: #1d4ed8;
}

.notes-row__iconbtn--danger {
    background: #fee2e2;
    color: #dc2626;
}

.notes-row__iconbtn--danger:hover {
    background: #fecaca;
    color: #b91c1c;
}

.notes-typechip {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
    flex: 0 0 auto;
    max-width: 100%;
    padding: 0.12rem 0.5rem;
    border: 0;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 700;
    line-height: 1.3;
    white-space: nowrap;
}

.notes-typechip__dot {
    width: 0.38rem;
    height: 0.38rem;
    flex-shrink: 0;
    border-radius: 50%;
}

.notes-typechip--buy { color: #92400e; background: #fef3c7; }
.notes-typechip--buy .notes-typechip__dot { background: #f59e0b; }
.notes-typechip--contact { color: #1e40af; background: #dbeafe; }
.notes-typechip--contact .notes-typechip__dot { background: #3b82f6; }
.notes-typechip--reminder { color: #5b21b6; background: #ede9fe; }
.notes-typechip--reminder .notes-typechip__dot { background: #8b5cf6; }
.notes-typechip--general { color: #334155; background: #eef2f6; }
.notes-typechip--general .notes-typechip__dot { background: #64748b; }

.notes-row--grid {
    flex-direction: column;
    min-height: 100%;
    gap: 0.65rem;
    padding: 0.95rem;
}

.notes-row--grid .notes-row__check {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    z-index: 1;
    margin-top: 0;
}

.notes-row--grid .notes-row__body {
    width: 100%;
    padding-right: 2.35rem;
    gap: 0.5rem;
    flex: 1 1 auto;
}

.notes-row--grid .notes-row__topline {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.4rem;
}

.notes-row--grid .notes-row__title {
    width: 100%;
    white-space: normal;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow: hidden;
    text-overflow: unset;
    line-height: 1.35;
}

.notes-row--grid .notes-row__preview {
    -webkit-line-clamp: 3;
    min-height: 3.5em;
    flex: 1 1 auto;
}

.notes-row--grid .notes-row__meta {
    flex-direction: column;
    align-items: stretch;
    gap: 0.55rem;
    margin-top: auto;
    padding-top: 0.55rem;
    border-top: 1px solid #eff1f5;
}

.notes-row--grid .notes-row__meta-info {
    width: 100%;
}

.notes-row--grid .notes-row__actions {
    width: 100%;
    margin-left: 0;
    justify-content: stretch;
    gap: 0.4rem;
}

.notes-row--grid .notes-row__iconbtn {
    flex: 1 1 0;
    width: auto;
    min-width: 0;
    height: 2.35rem;
}

.notes-row--grid .notes-avatar {
    margin-left: auto;
}

.notes-row--buy { border-left: 3px solid #f59e0b; }
.notes-row--contact { border-left: 3px solid #3b82f6; }
.notes-row--reminder { border-left: 3px solid #8b5cf6; }
.notes-row--general { border-left: 3px solid #94a3b8; }

@media (max-width: 991.98px) {
    .notes-row {
        padding: 0.8rem 0.8rem 0.75rem 0.75rem;
        gap: 0.7rem;
    }
    .notes-row__check {
        width: 2rem;
        height: 2rem;
        margin-top: 0.15rem;
    }
    .notes-row__topline {
        flex-wrap: wrap;
        align-items: flex-start;
        row-gap: 0.35rem;
    }
    .notes-row__title {
        flex: 1 1 8rem;
        font-size: 0.92rem;
        white-space: normal;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        text-overflow: unset;
    }
    .notes-row__preview {
        -webkit-line-clamp: 2;
        font-size: 0.8rem;
    }
    .notes-row__meta {
        flex-direction: column;
        align-items: stretch;
        gap: 0.55rem;
        margin-top: 0.15rem;
        padding-top: 0.55rem;
        border-top: 1px solid #eff1f5;
    }
    .notes-row__meta-info {
        min-height: 1.6rem;
    }
    .notes-row__actions {
        width: 100%;
        margin-left: 0;
        justify-content: stretch;
        gap: 0.45rem;
    }
    .notes-row__iconbtn {
        flex: 1 1 0;
        width: auto;
        min-width: 0;
        height: 2.65rem;
        border-radius: 0.7rem;
    }
    .notes-row__date {
        font-size: 0.72rem;
    }
}
</style>
