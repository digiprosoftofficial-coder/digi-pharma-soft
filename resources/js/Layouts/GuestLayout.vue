<template>
    <div class="auth-shell min-vh-100 d-flex flex-column">
        <div class="auth-shell__body flex-grow-1 d-flex">
            <aside class="auth-brand d-none d-lg-flex flex-column text-white">
                <div class="auth-brand__overlay"></div>
                <div class="auth-brand__content position-relative d-flex flex-column h-100 p-4 p-xl-5">
                    <div class="d-flex align-items-center gap-3 mb-auto">
                        <span class="auth-brand__logo" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path d="M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z" />
                                <path d="M12 11v4M12 8h.01" />
                            </svg>
                        </span>
                        <div>
                            <div class="fw-bold fs-5 lh-sm">{{ brandName }}</div>
                            <div class="small opacity-75">{{ brandTagline }}</div>
                        </div>
                    </div>

                    <div class="my-5">
                        <h1 class="display-6 fw-semibold mb-3">
                            {{ t('auth.hero_title') }}
                            <span class="auth-brand__accent">{{ t('auth.hero_title_accent') }}</span>
                        </h1>
                        <p class="lead opacity-75 mb-0" style="font-size: 1.05rem">
                            {{ t('auth.hero_description') }}
                        </p>
                    </div>

                    <ul class="list-unstyled d-flex flex-column gap-3 mb-5">
                        <li v-for="feature in features" :key="feature.titleKey" class="d-flex gap-3">
                            <span class="auth-brand__feature-icon flex-shrink-0" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path :d="feature.icon" />
                                </svg>
                            </span>
                            <div>
                                <div class="fw-semibold">{{ t(feature.titleKey) }}</div>
                                <div class="small opacity-75">{{ t(feature.helpKey) }}</div>
                            </div>
                        </li>
                    </ul>

                    <div class="auth-brand__trust rounded-3 p-3 mt-auto">
                        <div class="d-flex align-items-center gap-2 small">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z" />
                            </svg>
                            <span>{{ t('auth.trust_line') }}</span>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="auth-main flex-grow-1 d-flex flex-column">
                <div class="auth-main__top d-flex align-items-center justify-content-between gap-2 px-3 px-md-4 pt-3">
                    <div class="d-lg-none d-flex align-items-center gap-2 text-primary">
                        <span class="auth-main__mobile-logo" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path d="M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z" />
                                <path d="M12 11v4M12 8h.01" />
                            </svg>
                        </span>
                        <span class="fw-semibold small">{{ brandName }}</span>
                    </div>
                    <div class="ms-auto">
                        <LocaleSwitcher />
                    </div>
                </div>

                <div class="auth-main__center flex-grow-1 d-flex align-items-center justify-content-center px-3 px-md-4 py-4">
                    <div class="auth-card card border-0 shadow-sm w-100">
                        <div class="card-body p-4 p-md-5">
                            <slot />
                        </div>
                    </div>
                </div>

                <footer class="auth-main__footer text-center small text-muted px-3 pb-3">
                    <span class="d-inline-flex align-items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary" aria-hidden="true">
                            <path d="M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z" />
                        </svg>
                        <span v-html="t('auth.ssl_notice', { bits: 256 })"></span>
                    </span>
                </footer>
            </main>
        </div>
    </div>
</template>

<script setup>
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import { useLocale } from '@/composables/useLocale';
import { useTheme } from '@/composables/useTheme';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

useTheme();

const { t } = useLocale();
const page = usePage();

const brandName = computed(() => page.props.tenant?.name || t('auth.product_name'));
const brandTagline = computed(() => t('auth.product_tagline'));

const features = [
    {
        titleKey: 'auth.feature_inventory_title',
        helpKey: 'auth.feature_inventory_help',
        icon: 'M20 7H4v10h16V7zM4 7l8 5 8-5',
    },
    {
        titleKey: 'auth.feature_sales_title',
        helpKey: 'auth.feature_sales_help',
        icon: 'M6 2h12v4H6zM4 8h16v12H4zM9 12h6',
    },
    {
        titleKey: 'auth.feature_reports_title',
        helpKey: 'auth.feature_reports_help',
        icon: 'M4 19V5M10 19V9M16 19v-6M22 19H2',
    },
    {
        titleKey: 'auth.feature_secure_title',
        helpKey: 'auth.feature_secure_help',
        icon: 'M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z',
    },
];
</script>

<style scoped>
.auth-shell {
    background: #f5f7fb;
}

.auth-shell__body {
    min-height: 100vh;
}

.auth-brand {
    position: relative;
    width: min(42vw, 32rem);
    flex-shrink: 0;
    overflow: hidden;
    background:
        radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.18), transparent 42%),
        linear-gradient(160deg, rgba(var(--bs-primary-rgb), 0.92) 0%, rgba(var(--bs-primary-rgb), 0.72) 48%, #0b1f17 100%),
        #0f766e;
}

.auth-brand__overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg, rgba(8, 28, 22, 0.15), rgba(8, 28, 22, 0.55)),
        repeating-linear-gradient(
            -18deg,
            rgba(255, 255, 255, 0.04) 0,
            rgba(255, 255, 255, 0.04) 2px,
            transparent 2px,
            transparent 14px
        );
    pointer-events: none;
}

.auth-brand__logo,
.auth-main__mobile-logo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.9rem;
    background: rgba(255, 255, 255, 0.16);
    color: #fff;
}

.auth-main__mobile-logo {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.7rem;
    background: rgba(var(--bs-primary-rgb), 0.12);
    color: var(--bs-primary);
}

.auth-brand__accent {
    color: color-mix(in srgb, #fff 70%, var(--bs-primary) 30%);
}

.auth-brand__feature-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.4rem;
    height: 2.4rem;
    border-radius: 0.75rem;
    background: rgba(255, 255, 255, 0.14);
}

.auth-brand__trust {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(10px);
}

.auth-main {
    min-width: 0;
}

.auth-card {
    max-width: 28rem;
    border-radius: 1rem;
}
</style>
