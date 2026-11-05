<template>
    <PlatformShellLayout :page-title="lead ? t('common.edit') : t('platform.new_lead')">
        <Head :title="lead ? 'Edit lead' : 'New lead'" />
        <form class="card border-0 shadow-sm card-body" style="max-width: 720px" @submit.prevent="submit">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">{{ t('platform.pharmacy_name') }}</label><input v-model="form.pharmacy_name" class="form-control" required /></div>
                <div class="col-md-6"><label class="form-label">{{ t('platform.owner_name') }}</label><input v-model="form.owner_name" class="form-control" required /></div>
                <div class="col-md-4"><label class="form-label">{{ t('platform.mobile') }}</label><input v-model="form.phone" class="form-control" required /></div>
                <div class="col-md-4"><label class="form-label">WhatsApp</label><input v-model="form.whatsapp" class="form-control" /></div>
                <div class="col-md-4"><label class="form-label">Email</label><input v-model="form.email" type="email" class="form-control" /></div>
                <div class="col-md-4"><label class="form-label">{{ t('platform.branch_count') }}</label><input v-model.number="form.branch_count" type="number" min="1" class="form-control" /></div>
                <div class="col-md-4"><label class="form-label">{{ t('platform.source') }}</label>
                    <select v-model="form.source" class="form-select"><option v-for="s in sources" :key="s" :value="s">{{ s }}</option></select>
                </div>
                <div class="col-md-4"><label class="form-label">{{ t('platform.interested_plan') }}</label>
                    <select v-model="form.interested_plan_id" class="form-select"><option :value="null">—</option><option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option></select>
                </div>
                <div class="col-md-4"><label class="form-label">{{ t('common.status') }}</label>
                    <select v-model="form.status" class="form-select"><option v-for="s in statuses" :key="s" :value="s">{{ s }}</option></select>
                </div>
                <div class="col-md-4"><label class="form-label">{{ t('platform.assigned_to') }}</label>
                    <select v-model="form.assigned_to" class="form-select"><option :value="null">—</option><option v-for="u in salesStaff" :key="u.id" :value="u.id">{{ u.name }}</option></select>
                </div>
                <div class="col-md-4"><label class="form-label">{{ t('platform.follow_up_at') }}</label><input v-model="form.follow_up_at" type="datetime-local" class="form-control" /></div>
                <div class="col-12"><label class="form-label">{{ t('platform.address') }}</label><textarea v-model="form.address" class="form-control" rows="2" /></div>
                <div class="col-12"><label class="form-label">{{ t('platform.notes') }}</label><textarea v-model="form.notes" class="form-control" rows="3" /></div>
            </div>
            <button class="btn btn-primary mt-3" :disabled="form.processing">{{ t('common.save') }}</button>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    lead: { type: Object, default: null },
    statuses: { type: Array, required: true },
    sources: { type: Array, required: true },
    salesStaff: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
});

const { t } = useLocale();
const form = useForm({
    pharmacy_name: props.lead?.pharmacy_name || '',
    owner_name: props.lead?.owner_name || '',
    phone: props.lead?.phone || '',
    whatsapp: props.lead?.whatsapp || '',
    email: props.lead?.email || '',
    branch_count: props.lead?.branch_count || 1,
    address: props.lead?.address || '',
    source: props.lead?.source || 'other',
    interested_plan_id: props.lead?.interested_plan_id || null,
    status: props.lead?.status || 'new',
    notes: props.lead?.notes || '',
    follow_up_at: props.lead?.follow_up_at ? props.lead.follow_up_at.slice(0, 16) : '',
    assigned_to: props.lead?.assigned_to || null,
});

function submit() {
    if (props.lead) form.put(`/platform/leads/${props.lead.id}`);
    else form.post('/platform/leads');
}
</script>
