<template>
    <div class="marketing-shell min-vh-100 d-flex flex-column">
        <header class="sp-nav">
            <div class="sp-container sp-nav-inner">
                <Link href="/" class="sp-brand" aria-label="SoftoPharma home">
                    <span class="sp-brand-name">SoftoPharma</span>
                    <span class="sp-brand-sub">{{ t('platform.brand_product_of') }}</span>
                </Link>

                <nav class="sp-nav-links" aria-label="Primary">
                    <a href="/#home">{{ t('platform.nav_home') }}</a>
                    <a href="/#features">{{ t('platform.nav_features') }}</a>
                    <a href="/#solutions">{{ t('platform.nav_solutions') }}</a>
                    <Link href="/pricing">{{ t('platform.nav_pricing') }}</Link>
                    <a href="/#faq">{{ t('platform.nav_faq') }}</a>
                    <a href="/#contact">{{ t('platform.nav_contact') }}</a>
                </nav>

                <div class="sp-nav-actions">
                    <LocaleSwitcher />
                    <Link href="/login" class="sp-btn sp-btn-secondary" style="min-height: 2.4rem; padding: 0.4rem 0.9rem;">
                        {{ t('auth.login') || 'Login' }}
                    </Link>
                    <Link href="/demo" class="sp-btn sp-btn-primary" style="min-height: 2.4rem; padding: 0.4rem 0.9rem;">
                        {{ t('platform.book_demo') }}
                    </Link>
                </div>

                <button
                    type="button"
                    class="sp-nav-toggle"
                    :aria-expanded="menuOpen ? 'true' : 'false'"
                    aria-controls="sp-mobile-menu"
                    @click="menuOpen = !menuOpen"
                >
                    <span class="visually-hidden">Menu</span>
                    <svg width="18" height="14" viewBox="0 0 18 14" fill="none" aria-hidden="true">
                        <path d="M0 1h18M0 7h18M0 13h18" stroke="currentColor" stroke-width="2" />
                    </svg>
                </button>
            </div>

            <div id="sp-mobile-menu" class="sp-mobile-panel" :class="{ 'is-open': menuOpen }">
                <div class="sp-container">
                    <a href="/#home" @click="menuOpen = false">{{ t('platform.nav_home') }}</a>
                    <a href="/#features" @click="menuOpen = false">{{ t('platform.nav_features') }}</a>
                    <a href="/#solutions" @click="menuOpen = false">{{ t('platform.nav_solutions') }}</a>
                    <Link href="/pricing" @click="menuOpen = false">{{ t('platform.nav_pricing') }}</Link>
                    <a href="/#faq" @click="menuOpen = false">{{ t('platform.nav_faq') }}</a>
                    <a href="/#contact" @click="menuOpen = false">{{ t('platform.nav_contact') }}</a>
                    <div class="sp-mobile-cta">
                        <LocaleSwitcher />
                        <Link href="/login" class="sp-btn sp-btn-secondary" @click="menuOpen = false">
                            {{ t('auth.login') || 'Login' }}
                        </Link>
                        <Link href="/demo" class="sp-btn sp-btn-primary" @click="menuOpen = false">
                            {{ t('platform.book_demo') }}
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-grow-1">
            <slot />
        </main>

        <footer id="contact" class="sp-footer">
            <div class="sp-container sp-footer-grid">
                <div>
                    <div class="sp-brand">
                        <span class="sp-brand-name">SoftoPharma</span>
                        <span class="sp-brand-sub">{{ t('platform.brand_product_of') }}</span>
                    </div>
                    <p class="sp-footer-tag">{{ t('platform.brand_tagline') }}</p>
                    <p class="sp-footer-tag" style="margin-top: 0.85rem;">{{ t('platform.company_blurb') }}</p>
                    <p v-if="support.email || support.phone" class="sp-footer-tag" style="margin-top: 1rem;">
                        <template v-if="support.phone">
                            <a :href="`tel:${support.phone}`">{{ support.phone }}</a>
                            <br />
                        </template>
                        <template v-if="support.email">
                            <a :href="`mailto:${support.email}`">{{ support.email }}</a>
                        </template>
                    </p>
                </div>

                <div>
                    <h4>{{ t('platform.footer_product') }}</h4>
                    <ul>
                        <li><a href="/#features">{{ t('platform.nav_features') }}</a></li>
                        <li><a href="/#solutions">{{ t('platform.nav_solutions') }}</a></li>
                        <li><Link href="/pricing">{{ t('platform.nav_pricing') }}</Link></li>
                        <li><Link href="/demo">{{ t('platform.book_demo') }}</Link></li>
                    </ul>
                </div>

                <div>
                    <h4>{{ t('platform.footer_company') }}</h4>
                    <ul>
                        <li><a href="/#trust">{{ t('platform.nav_about') }}</a></li>
                        <li><a href="/#faq">{{ t('platform.nav_faq') }}</a></li>
                        <li><a href="/#contact">{{ t('platform.nav_contact') }}</a></li>
                        <li><Link href="/login">{{ t('auth.login') || 'Login' }}</Link></li>
                    </ul>
                </div>

                <div>
                    <h4>{{ t('platform.footer_legal') }}</h4>
                    <ul>
                        <li><Link href="/privacy">{{ t('platform.privacy_policy') }}</Link></li>
                        <li><Link href="/terms">{{ t('platform.terms_conditions') }}</Link></li>
                        <li><Link href="/get-started">{{ t('platform.get_started') }}</Link></li>
                    </ul>
                </div>
            </div>

            <div class="sp-container sp-footer-bottom">
                <span>© {{ year }} SoftoPharma · {{ t('platform.developed_by') }} DigiProSoft</span>
                <span>{{ t('platform.marketing_footer') }}</span>
            </div>
        </footer>
    </div>
</template>

<script setup>
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import { useLocale } from '@/composables/useLocale';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    support: {
        type: Object,
        default: () => ({ email: null, phone: null }),
    },
});

const { t } = useLocale();
const menuOpen = ref(false);
const year = computed(() => new Date().getFullYear());
</script>
