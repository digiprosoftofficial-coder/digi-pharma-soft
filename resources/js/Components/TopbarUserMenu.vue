<template>
    <div class="topbar-user-menu dropdown">
        <button
            type="button"
            class="topbar-user-menu__btn"
            :class="chromeClass"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-label="t('common.profile')"
            :title="userName"
            @click.stop="toggle"
        >
            <span class="topbar-user-menu__avatar" aria-hidden="true">
                <img v-if="avatarUrl" :src="avatarUrl" alt="" class="topbar-user-menu__avatar-img" />
                <span v-else class="topbar-user-menu__avatar-initial">{{ initial }}</span>
            </span>
        </button>
        <div v-if="open" class="topbar-user-menu__dropdown dropdown-menu dropdown-menu-end show shadow">
            <div class="topbar-user-menu__meta px-3 py-2">
                <div class="fw-semibold text-truncate">{{ userName }}</div>
                <div class="topbar-user-menu__email small text-truncate">{{ userEmail }}</div>
            </div>
            <Link href="/profile" class="dropdown-item topbar-user-menu__item" @click="open = false">
                {{ t('common.profile_settings') }}
            </Link>
            <div class="topbar-user-menu__divider"></div>
            <Link href="/logout" method="post" as="button" class="dropdown-item topbar-user-menu__item" @click="open = false">
                {{ t('common.logout') }}
            </Link>
        </div>
    </div>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

defineProps({
    chromeClass: { type: String, default: '' },
});

const { t } = useLocale();
const page = usePage();
const open = ref(false);

const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const userEmail = computed(() => page.props.auth?.user?.email ?? '');
const avatarUrl = computed(() => page.props.auth?.user?.avatar_url || '');
const initial = computed(() => {
    const name = String(userName.value || '').trim();
    if (!name) {
        return 'U';
    }
    return name.charAt(0).toUpperCase();
});

watch(
    () => page.url,
    () => {
        open.value = false;
    },
);

function toggle() {
    open.value = !open.value;
}

function onDocumentClick(event) {
    if (!event.target?.closest?.('.topbar-user-menu')) {
        open.value = false;
    }
}

function onEscape(event) {
    if (event.key === 'Escape') {
        open.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('keydown', onEscape);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('keydown', onEscape);
});
</script>

<style scoped>
.topbar-user-menu {
    --topbar-menu-bg: #163f34;
    --topbar-menu-bg: color-mix(in srgb, var(--bs-primary) 34%, #0a1a16 66%);
    --topbar-menu-fg: rgba(255, 255, 255, 0.94);
    --topbar-menu-muted: rgba(255, 255, 255, 0.58);
    --topbar-menu-border: rgba(255, 255, 255, 0.14);
    --topbar-menu-hover: rgba(255, 255, 255, 0.1);
    position: relative;
}

.topbar-user-menu__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    padding: 0;
    border: 1px solid var(--topbar-menu-border);
    border-radius: 999px;
    background: transparent;
    color: inherit;
    line-height: 1;
}

.topbar-user-menu__btn:hover,
.topbar-user-menu__btn:focus {
    background: var(--topbar-menu-hover);
    border-color: rgba(255, 255, 255, 0.28);
    outline: 0;
}

.topbar-user-menu__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 999px;
    overflow: hidden;
    flex: 0 0 auto;
    background: color-mix(in srgb, var(--bs-primary) 55%, #ffffff 45%);
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
}

.topbar-user-menu__avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.topbar-user-menu__dropdown {
    position: absolute;
    top: calc(100% + 0.4rem);
    right: 0;
    z-index: 1050;
    display: block;
    min-width: 14rem;
    padding: 0.35rem 0;
    border: 1px solid var(--topbar-menu-border) !important;
    border-radius: 0.65rem;
    background: var(--topbar-menu-bg) !important;
    color: var(--topbar-menu-fg);
}

.topbar-user-menu__meta {
    border-bottom: 1px solid var(--topbar-menu-border);
    color: var(--topbar-menu-fg);
}

.topbar-user-menu__email {
    color: var(--topbar-menu-muted) !important;
}

.topbar-user-menu__divider {
    height: 0;
    margin: 0.35rem 0;
    border-top: 1px solid var(--topbar-menu-border);
}

.topbar-user-menu__item {
    color: var(--topbar-menu-fg) !important;
    background: transparent;
}

.topbar-user-menu__item:hover,
.topbar-user-menu__item:focus {
    color: #fff !important;
    background: var(--topbar-menu-hover) !important;
}
</style>
