<template>
    <PlatformShellLayout :page-title="demo.pharmacy_name">
        <Head :title="demo.pharmacy_name" />
        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2" style="max-width: 720px">
            <div>
                <h1 class="h5 mb-1">{{ demo.pharmacy_name }}</h1>
                <p class="small text-muted mb-0">{{ demo.owner_name }} · {{ demo.mobile }} · {{ demo.status }}</p>
                <p class="small text-muted mb-0">{{ t('platform.requested_at') }}: {{ formatWhen(demo.created_at) }}</p>
            </div>
            <button
                v-if="canDelete"
                type="button"
                class="btn btn-sm btn-outline-danger"
                @click="destroyDemo"
            >
                {{ t('common.delete') }}
            </button>
        </div>
        <div class="card border-0 shadow-sm card-body mb-3" style="max-width: 720px">
            <h2 class="h6">{{ t('platform.lead_details') }}</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-4">{{ t('platform.email') }}</dt>
                <dd class="col-sm-8">{{ demo.lead?.email || '—' }}</dd>
                <dt class="col-sm-4">{{ t('platform.address') }}</dt>
                <dd class="col-sm-8">{{ demo.lead?.address || '—' }}</dd>
                <dt class="col-sm-4">{{ t('platform.message') }}</dt>
                <dd class="col-sm-8">{{ demo.message || '—' }}</dd>
                <dt class="col-sm-4">{{ t('platform.requested_at') }}</dt>
                <dd class="col-sm-8">{{ formatWhen(demo.created_at) }}</dd>
            </dl>
        </div>
        <div class="card border-0 shadow-sm card-body" style="max-width: 720px">
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
                        <option
                            v-if="currentAssigneeOutsideList"
                            :value="demo.assigned_to"
                            disabled
                        >
                            {{ assigneeName }}
                        </option>
                        <option v-for="u in salesStaff" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                    <div v-if="!canAssignToAdmins" class="form-text">{{ t('platform.assigned_to_sales_hint') }}</div>
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
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    demo: { type: Object, required: true },
    statuses: { type: Array, required: true },
    salesStaff: { type: Array, default: () => [] },
    canAssignToAdmins: { type: Boolean, default: false },
});

const { t } = useLocale();
const page = usePage();
const canDelete = computed(() => {
    const user = page.props.auth?.user;
    return Boolean(user?.is_platform_super_admin || (user?.roles || []).includes('super admin'));
});
const assigneeName = computed(() => {
    const match = (props.salesStaff || []).find((u) => Number(u.id) === Number(props.demo.assigned_to));
    return match?.name || props.demo.assignee?.name || '—';
});
const currentAssigneeOutsideList = computed(() => {
    const id = props.demo.assigned_to;
    if (!id) {
        return false;
    }
    return !(props.salesStaff || []).some((u) => Number(u.id) === Number(id));
});
const form = useForm({
    status: props.demo.status,
    scheduled_at: props.demo.scheduled_at ? props.demo.scheduled_at.slice(0, 16) : '',
    assigned_to: props.demo.assigned_to,
    meeting_notes: props.demo.meeting_notes || '',
});

function formatWhen(value) {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString();
}

function save() {
    form.put(`/platform/demos/${props.demo.id}`);
}

function destroyDemo() {
    if (!confirm(t('platform.demo_delete_confirm', { name: props.demo.pharmacy_name }))) {
        return;
    }
    router.delete(`/platform/demos/${props.demo.id}`);
}
</script>
