<template>
    <div class="marketing-shell min-vh-100 d-flex flex-column bg-light">
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
            <div class="container">
                <Link href="/" class="navbar-brand fw-bold text-primary">DigiProSoft</Link>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <Link href="/pricing" class="btn btn-link text-decoration-none">{{ t('platform.nav_pricing') }}</Link>
                    <Link href="/demo" class="btn btn-link text-decoration-none">{{ t('platform.nav_demo') }}</Link>
                    <Link href="/login" class="btn btn-outline-primary btn-sm">{{ t('auth.login') || 'Login' }}</Link>
                    <Link href="/get-started" class="btn btn-primary btn-sm">{{ t('platform.get_started') }}</Link>
                </div>
            </div>
        </nav>
        <main class="flex-grow-1">
            <slot />
        </main>
        <footer class="border-top bg-white py-4 mt-auto">
            <div class="container small text-muted d-flex justify-content-between flex-wrap gap-2">
                <span>© {{ new Date().getFullYear() }} DigiProSoft Pharmacy SaaS</span>
                <span>{{ t('platform.marketing_footer') }}</span>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { Link } from '@inertiajs/vue3';

const { t } = useLocale();
</script>
