<template>
    <PlatformShellLayout :page-title="announcement ? t('common.edit') : t('platform.new_announcement')">
        <Head :title="announcement ? t('common.edit') : t('platform.new_announcement')" />
        <Link href="/platform/announcements" class="small text-decoration-none">← {{ t('platform.nav_announcements') }}</Link>
        <h1 class="h4 mt-2 mb-3">{{ announcement ? t('common.edit') : t('platform.new_announcement') }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">{{ t('platform.announcement_title') }}</label>
                <input v-model="form.title" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('platform.announcement_body') }}</label>
                <textarea v-model="form.body" class="form-control" rows="4" required maxlength="5000" />
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.announcement_severity') }}</label>
                    <select v-model="form.severity" class="form-select" required>
                        <option value="info">{{ t('platform.announcement_severity_info') }}</option>
                        <option value="warning">{{ t('platform.announcement_severity_warning') }}</option>
                        <option value="danger">{{ t('platform.announcement_severity_danger') }}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.announcement_starts') }}</label>
                    <input v-model="form.starts_at" type="datetime-local" class="form-control" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.announcement_ends') }}</label>
                    <input v-model="form.ends_at" type="datetime-local" class="form-control" />
                </div>
            </div>
            <div class="form-check mb-3">
                <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                <label class="form-check-label" for="active">{{ t('common.active') }}</label>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/platform/announcements" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    announcement: { type: Object, default: null },
});

const { t } = useLocale();

const nowLocal = () => {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());

    return d.toISOString().slice(0, 16);
};

const form = useForm({
    title: props.announcement?.title ?? '',
    body: props.announcement?.body ?? '',
    severity: props.announcement?.severity ?? 'info',
    starts_at: props.announcement?.starts_at ?? nowLocal(),
    ends_at: props.announcement?.ends_at ?? '',
    is_active: props.announcement?.is_active ?? true,
});

function submit() {
    if (props.announcement) {
        form.put(`/platform/announcements/${props.announcement.id}`);
    } else {
        form.post('/platform/announcements');
    }
}
</script>
