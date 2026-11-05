<template>
    <PlatformShellLayout :page-title="t('platform.notifications_title')">
        <Head :title="t('platform.nav_notifications')" />
        <div class="mb-3">
            <button class="btn btn-sm btn-outline-primary" @click="markAll">{{ t('platform.mark_all_read') }}</button>
        </div>
        <div class="list-group shadow-sm">
            <div v-for="n in notifications.data" :key="n.id" class="list-group-item" :class="{ 'list-group-item-light': !n.read_at }">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="fw-semibold">{{ n.data?.title || n.type }}</div>
                        <div class="small text-muted">{{ n.data?.body }}</div>
                    </div>
                    <button v-if="!n.read_at" class="btn btn-sm btn-link" @click="markRead(n)">Read</button>
                </div>
            </div>
            <div v-if="!notifications.data?.length" class="list-group-item text-muted text-center">{{ t('common.no_results') }}</div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, router } from '@inertiajs/vue3';

defineProps({ notifications: { type: Object, required: true } });
const { t } = useLocale();
function markRead(n) { router.post(`/platform/notifications/${n.id}/read`); }
function markAll() { router.post('/platform/notifications/read-all'); }
</script>
