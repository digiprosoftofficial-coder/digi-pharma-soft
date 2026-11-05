<template>
    <PlatformShellLayout :page-title="demo.pharmacy_name">
        <Head :title="demo.pharmacy_name" />
        <div class="card border-0 shadow-sm card-body" style="max-width: 720px">
            <h1 class="h5 mb-3">{{ demo.pharmacy_name }}</h1>
            <p class="small text-muted">{{ demo.owner_name }} · {{ demo.mobile }} · {{ demo.status }}</p>
            <p>{{ demo.message }}</p>
            <form class="row g-3" @submit.prevent="save">
                <div class="col-md-6">
                    <label class="form-label">{{ t('common.status') }}</label>
                    <select v-model="form.status" class="form-select">
                        <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.scheduled_at') }}</label>
                    <input v-model="form.scheduled_at" type="datetime-local" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.assigned_to') }}</label>
                    <select v-model="form.assigned_to" class="form-select">
                        <option :value="null">—</option>
                        <option v-for="u in salesStaff" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ t('platform.meeting_notes') }}</label>
                    <textarea v-model="form.meeting_notes" class="form-control" rows="4" />
                </div>
                <div class="col-12"><button class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button></div>
            </form>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    demo: { type: Object, required: true },
    statuses: { type: Array, required: true },
    salesStaff: { type: Array, default: () => [] },
});

const { t } = useLocale();
const form = useForm({
    status: props.demo.status,
    scheduled_at: props.demo.scheduled_at ? props.demo.scheduled_at.slice(0, 16) : '',
    assigned_to: props.demo.assigned_to,
    meeting_notes: props.demo.meeting_notes || '',
});

function save() {
    form.put(`/platform/demos/${props.demo.id}`);
}
</script>
