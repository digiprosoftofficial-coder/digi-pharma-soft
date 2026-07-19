<template>
    <nav class="tenant-bottom-nav d-lg-none" aria-label="Primary mobile navigation">
        <template v-for="item in items" :key="item.key">
            <Link
                v-if="item.type === 'link'"
                :href="item.href"
                class="tenant-bottom-nav__item"
                :class="{ 'tenant-bottom-nav__item--active': item.active }"
            >
                <span class="tenant-bottom-nav__icon">
                    <TenantNavIcon :name="item.icon" />
                </span>
                <span class="tenant-bottom-nav__label">{{ item.label }}</span>
            </Link>

            <Link
                v-else-if="item.type === 'primary'"
                :href="item.href"
                class="tenant-bottom-nav__item tenant-bottom-nav__item--primary"
                :class="{ 'tenant-bottom-nav__item--active': item.active }"
            >
                <span class="tenant-bottom-nav__fab">
                    <TenantNavIcon :name="item.icon" />
                </span>
                <span class="tenant-bottom-nav__label">{{ item.label }}</span>
            </Link>

            <button
                v-else
                type="button"
                class="tenant-bottom-nav__item"
                @click="$emit('open-more')"
            >
                <span class="tenant-bottom-nav__icon">
                    <TenantNavIcon :name="item.icon" />
                </span>
                <span class="tenant-bottom-nav__label">{{ item.label }}</span>
            </button>
        </template>
    </nav>
</template>

<script setup>
import TenantNavIcon from '@/Components/Tenant/TenantNavIcon.vue';
import { useLocale } from '@/composables/useLocale';
import { usePermissions } from '@/composables/usePermissions';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineEmits(['open-more']);

const { t } = useLocale();
const { can } = usePermissions();
const page = usePage();

const path = computed(() => (page.url || '').split('?')[0]);

function isExact(target) {
    return path.value === target;
}

function startsWith(target) {
    return path.value === target || path.value.startsWith(target + '/');
}

const items = computed(() => {
    const list = [];

    list.push({
        key: 'home',
        type: 'link',
        href: '/dashboard',
        icon: 'dashboard',
        label: t('tenant_nav.home'),
        active: isExact('/dashboard'),
    });

    if (can('sales.view')) {
        list.push({
            key: 'sales',
            type: 'link',
            href: '/sales',
            icon: 'sales',
            label: t('tenant_nav.sales'),
            active: startsWith('/sales'),
        });
    }

    if (can('pos.access')) {
        list.push({
            key: 'pos',
            type: 'primary',
            href: '/pos',
            icon: 'plus',
            label: t('tenant_nav.new_sale'),
            active: startsWith('/pos'),
        });
    }

    if (can('products.view')) {
        list.push({
            key: 'products',
            type: 'link',
            href: '/products',
            icon: 'catalog',
            label: t('tenant_nav.products'),
            active: startsWith('/products'),
        });
    }

    list.push({
        key: 'more',
        type: 'button',
        icon: 'list',
        label: t('tenant_nav.more'),
    });

    return list;
});
</script>

<style scoped>
.tenant-bottom-nav {
    position: fixed;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 1035;
    display: flex;
    align-items: stretch;
    justify-content: space-around;
    gap: 0.15rem;
    padding: 0.25rem 0.35rem calc(0.25rem + env(safe-area-inset-bottom, 0px));
    background: #ffffff;
    border-top: 1px solid var(--bs-border-color, #e2e8f0);
    box-shadow: 0 -0.35rem 1rem rgba(15, 23, 42, 0.08);
}

.tenant-bottom-nav__item {
    display: flex;
    flex: 1 1 0;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.15rem;
    min-width: 0;
    padding: 0.35rem 0.15rem;
    border: 0;
    background: transparent;
    color: #64748b;
    text-decoration: none;
    font-size: 0.7rem;
    line-height: 1.1;
    transition: color 0.15s ease;
}

.tenant-bottom-nav__item--active {
    color: var(--bs-primary, #2563eb);
    font-weight: 600;
}

.tenant-bottom-nav__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.tenant-bottom-nav__icon :deep(.tenant-nav-icon) {
    width: 22px;
    height: 22px;
}

.tenant-bottom-nav__label {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tenant-bottom-nav__item--primary {
    color: var(--bs-primary, #2563eb);
    font-weight: 600;
}

.tenant-bottom-nav__fab {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3.1rem;
    height: 3.1rem;
    margin-top: -1.4rem;
    margin-bottom: 0.1rem;
    border-radius: 50%;
    color: #ffffff;
    background: var(--bs-primary, #2563eb);
    box-shadow: 0 0.4rem 0.9rem rgba(37, 99, 235, 0.45);
    border: 3px solid #ffffff;
}

.tenant-bottom-nav__fab :deep(.tenant-nav-icon) {
    width: 26px;
    height: 26px;
}
</style>
