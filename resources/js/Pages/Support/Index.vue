<template>
    <TenantShellLayout page-title="Support">
        <Head title="Support" />
        <h1 class="h4 mb-3">Help &amp; support</h1>
        <div class="card border-0 shadow-sm card-body mb-3">
            <h2 class="h6">FAQ</h2>
            <ul class="small mb-0">
                <li>Use <strong>New sale</strong> in the header for POS checkout.</li>
                <li>Record stock intake under <strong>Purchases → New purchase</strong>.</li>
                <li>Returns increase batch quantity when lines are saved.</li>
            </ul>
        </div>
        <div class="card border-0 shadow-sm card-body">
            <h2 class="h6">Contact</h2>
            <p v-if="support?.email" class="small mb-1">
                Email:
                <a :href="`mailto:${support.email}`" class="text-decoration-none">{{ support.email }}</a>
            </p>
            <p v-if="support?.phone" class="small mb-1">Phone: {{ support.phone }}</p>
            <p v-if="!support?.email && !support?.phone" class="small text-muted mb-0">
                Support contact details are not configured yet. Ask your platform administrator.
            </p>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    support: { type: Object, default: () => ({ email: null, phone: null }) },
});
</script>
