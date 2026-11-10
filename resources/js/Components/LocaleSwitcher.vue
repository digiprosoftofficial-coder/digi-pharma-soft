<template>
    <div
        class="locale-toggle"
        :class="{ 'locale-toggle--fluid': fluid }"
        role="group"
        :aria-label="t('common.language')"
    >
        <button
            v-for="loc in options"
            :key="loc.code"
            type="button"
            class="locale-toggle__btn"
            :class="{ 'is-active': current === loc.code }"
            :aria-pressed="current === loc.code ? 'true' : 'false'"
            @click="select(loc.code)"
        >
            {{ shortLabel(loc) }}
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useLocale } from '@/composables/useLocale';

const props = defineProps({
    fluid: { type: Boolean, default: false },
    /** When set, acts as a form control (v-model) instead of posting /locale. */
    modelValue: { type: String, default: undefined },
});

const emit = defineEmits(['update:modelValue']);

const { locale, locales, t, setLocale } = useLocale();

const options = computed(() => {
    const list = locales();
    return list.length ? list : [
        { code: 'en', label: 'English' },
        { code: 'bn', label: 'বাংলা' },
    ];
});

const current = computed(() => {
    if (props.modelValue !== undefined) {
        return props.modelValue;
    }
    return locale();
});

function shortLabel(loc) {
    if (loc.code === 'bn') {
        return 'বাং';
    }
    if (loc.code === 'en') {
        return 'EN';
    }
    return String(loc.label || loc.code).slice(0, 3).toUpperCase();
}

function select(code) {
    if (props.modelValue !== undefined) {
        if (code !== props.modelValue) {
            emit('update:modelValue', code);
        }
        return;
    }

    if (code !== locale()) {
        setLocale(code);
    }
}
</script>

<style scoped>
.locale-toggle {
    display: inline-flex;
    align-items: stretch;
    gap: 0.12rem;
    padding: 0.16rem;
    border-radius: 0.45rem;
    background: var(--sp-sage, rgba(18, 61, 50, 0.08));
    border: 1px solid var(--sp-line, rgba(18, 61, 50, 0.14));
    vertical-align: middle;
}

.locale-toggle__btn {
    appearance: none;
    border: 0;
    margin: 0;
    min-width: 2.55rem;
    padding: 0.32rem 0.7rem;
    border-radius: 0.32rem;
    background: transparent;
    color: var(--sp-muted, #5a655e);
    font-family: var(--sp-font, inherit);
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    line-height: 1.2;
    cursor: pointer;
    transition:
        background-color 0.18s ease,
        color 0.18s ease,
        box-shadow 0.18s ease;
}

.locale-toggle__btn:hover:not(.is-active) {
    color: var(--sp-forest, #123d32);
    background: rgba(255, 255, 255, 0.55);
}

.locale-toggle__btn:focus-visible {
    outline: 2px solid var(--sp-brand, #287a57);
    outline-offset: 1px;
}

.locale-toggle__btn.is-active {
    background: var(--sp-forest, #123d32);
    color: #fff;
    box-shadow: 0 1px 2px rgba(18, 61, 50, 0.2);
}

.locale-toggle--fluid {
    display: flex;
    width: 100%;
}

.locale-toggle--fluid .locale-toggle__btn {
    flex: 1;
}
</style>
