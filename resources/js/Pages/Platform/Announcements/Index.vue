<template>
    <PlatformShellLayout :page-title="t('platform.announcements_title')">
        <Head :title="t('platform.nav_announcements')" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted small mb-0">{{ t('platform.announcements_sub') }}</p>
            <Link href="/platform/announcements/create" class="btn btn-primary btn-sm">{{ t('platform.new_announcement') }}</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('platform.announcement_title') }}</th>
                        <th>{{ t('platform.announcement_severity') }}</th>
                        <th>{{ t('platform.announcement_schedule') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in announcements" :key="a.id">
                        <td>{{ a.title }}</td>
                        <td><span class="badge text-bg-secondary text-capitalize">{{ a.severity }}</span></td>
                        <td class="small">
                            {{ formatWhen(a.starts_at) }}
                            <span v-if="a.ends_at"> → {{ formatWhen(a.ends_at) }}</span>
                        </td>
                        <td>
                            <span v-if="a.is_live" class="badge text-bg-success">{{ t('platform.announcement_live') }}</span>
                            <span v-else-if="a.is_active" class="badge text-bg-secondary">{{ t('platform.announcement_scheduled') }}</span>
                            <span v-else class="badge text-bg-light text-dark">{{ t('common.inactive') }}</span>
                        </td>
                        <td class="text-end">
                            <Link :href="`/platform/announcements/${a.id}/edit`" class="btn btn-sm btn-outline-primary me-1">
                                {{ t('common.edit') }}
                            </Link>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="destroy(a)">
                                {{ t('common.delete') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!announcements.length">
                        <td colspan="5" class="text-muted text-center py-4">{{ t('common.no_results') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { formatHumanDateTime as formatWhen } from '@/utils/dates';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ announcements: { type: Array, required: true } });

const { t } = useLocale();

function destroy(announcement) {
    if (!confirm(t('platform.announcement_delete_confirm', { title: announcement.title }))) {
        return;
    }

    router.delete(`/platform/announcements/${announcement.id}`);
}
</script>
