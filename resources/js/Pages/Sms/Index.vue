<template>
    <TenantShellLayout page-title="Send SMS">
        <Head title="SMS" />
        <h1 class="h4 mb-3">Send SMS</h1>
        <div class="card border-0 shadow-sm card-body">
            <p class="text-muted mb-2">SMS sending is not configured for this environment yet.</p>
            <p class="small mb-0">When a provider is chosen, messages will be queued from here with audit logs.</p>
            <button type="button" class="btn btn-secondary btn-sm mt-3" disabled>Send (coming soon)</button>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>
