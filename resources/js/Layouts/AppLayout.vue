<template>
    <div class="min-vh-100 d-flex flex-column">
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container-fluid">
                <Link class="navbar-brand fw-semibold" :href="homeHref">{{ brand }}</Link>
                <ul v-if="isPlatformNav" class="navbar-nav mx-lg-3 gap-lg-1">
                    <li class="nav-item">
                        <Link href="/platform/dashboard" class="nav-link rounded px-3" :class="navLinkClass('/platform/dashboard')">
                            Overview
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link href="/platform/tenants" class="nav-link rounded px-3" :class="navLinkClass('/platform/tenants')">
                            Pharmacies
                        </Link>
                    </li>
                </ul>
                <div class="navbar-nav ms-auto">
                    <span v-if="tenant" class="navbar-text text-white-50 me-3">{{ tenant.name }}</span>
                    <Link href="/logout" method="post" as="button" class="btn btn-outline-light btn-sm">Log out</Link>
                </div>
            </div>
        </nav>
        <main class="flex-grow-1 py-4">
            <div class="container">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { useTheme } from '@/composables/useTheme';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

useTheme();
const page = usePage();

const tenant = computed(() => page.props.tenant);
const isPlatformNav = computed(() => page.props.auth?.user?.uses_platform_dashboard === true);

const brand = computed(() =>
    page.props.auth?.user?.uses_platform_dashboard ? 'Platform' : 'Pharmacy',
);

const homeHref = computed(() =>
    page.props.auth?.user?.uses_platform_dashboard ? '/platform/dashboard' : '/dashboard',
);

function navLinkClass(prefix) {
    const url = page.url || '';
    return url.startsWith(prefix) ? 'active bg-white bg-opacity-10' : '';
}
</script>
