<template>
    <component
        :is="wrapperTag"
        class="notes-row"
        :class="[
            `notes-row--${note.type}`,
            {
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
            :aria-label="note.is_done ? 'Reopen note' : 'Mark note as done'"
            :title="note.is_done ? 'Reopen' : 'Mark as done'"
            @click.stop="$emit('toggle-done', note)"
        >
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12l5 5L20 7" />
            </svg>
        </button>

        <div class="notes-row__main">
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
        </div>

        <div class="notes-row__side">
            <span class="notes-row__date" :title="fullDate(note.updated_at)">
                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" />
                    <path d="M16 2v4M8 2v4M3 10h18" />
                </svg>
                {{ relativeDate(note.updated_at) }}
            </span>

            <span v-if="note.user?.name" class="notes-avatar" :title="note.user.name">
                {{ initials(note.user.name) }}
            </span>

            <button
                v-if="canManage"
                type="button"
                class="notes-row__pin"
                :class="{ 'notes-row__pin--active': note.is_pinned }"
                :aria-pressed="note.is_pinned"
                :aria-label="note.is_pinned ? 'Unpin note' : 'Pin note'"
                :title="note.is_pinned ? 'Unpin' : 'Pin'"
                @click.stop="$emit('toggle-pin', note)"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 17v5" />
                    <path d="M9 3h6l-1 5 3 3v3H7v-3l3-3-1-5z" />
                </svg>
            </button>

            <div v-if="canManage" ref="menuRoot" class="notes-row__menu" @click.stop>
                <button
                    type="button"
                    class="notes-row__menubtn"
                    aria-label="More actions"
                    :aria-expanded="menuOpen"
                    @click="menuOpen = !menuOpen"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <circle cx="12" cy="5" r="1" />
                        <circle cx="12" cy="12" r="1" />
                        <circle cx="12" cy="19" r="1" />
                    </svg>
                </button>
                <div v-if="menuOpen" class="notes-row__dropdown">
                    <button type="button" @click="run('edit')">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                        </svg>
                        Edit
                    </button>
                    <button type="button" @click="run('toggle-pin')">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 17v5" /><path d="M9 3h6l-1 5 3 3v3H7v-3l3-3-1-5z" />
                        </svg>
                        {{ note.is_pinned ? 'Unpin' : 'Pin' }}
                    </button>
                    <button type="button" @click="run('toggle-done')">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12l5 5L20 7" />
                        </svg>
                        {{ note.is_done ? 'Reopen' : 'Mark completed' }}
                    </button>
                    <button type="button" class="danger" @click="run('delete')">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        </svg>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </component>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    note: { type: Object, required: true },
    view: { type: String, default: 'list' },
    selected: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
});

const emit = defineEmits(['select', 'toggle-done', 'toggle-pin', 'edit', 'delete']);

const menuOpen = ref(false);
const menuRoot = ref(null);
const wrapperTag = computed(() => (props.view === 'grid' ? 'div' : 'li'));

function run(action) {
    menuOpen.value = false;
    emit(action, props.note);
}

function onDocClick(e) {
    if (!menuOpen.value) return;
    if (menuRoot.value && !menuRoot.value.contains(e.target)) {
        menuOpen.value = false;
    }
}
watch(menuOpen, (open) => {
    if (open) document.addEventListener('mousedown', onDocClick);
    else document.removeEventListener('mousedown', onDocClick);
});
onBeforeUnmount(() => document.removeEventListener('mousedown', onDocClick));
onMounted(() => { /* keep hook to avoid tree-shake removing on some setups */ });

const TYPE_LABELS = { buy: 'Buy', contact: 'Contact', reminder: 'Reminder', general: 'General' };
function typeLabel(type) { return TYPE_LABELS[type] ?? type; }

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
    if (diffSec < 60) return 'just now';
    if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
    if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h ago`;
    if (diffSec < 604800) return `${Math.floor(diffSec / 86400)}d ago`;
    return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short' });
}

function fullDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleString();
}
</script>
