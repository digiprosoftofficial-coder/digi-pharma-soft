<template>
    <TenantShellLayout page-title="Coupon">
        <Head :title="coupon ? 'Edit coupon' : 'New coupon'" />
        <h1 class="h4 mb-3">{{ coupon ? 'Edit coupon' : 'New coupon' }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Code</label>
                <input v-model="form.code" class="form-control text-uppercase" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Percent off</label>
                <input v-model.number="form.percent_off" type="number" min="0" max="100" step="0.01" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Expires at</label>
                <input v-model="form.expires_at" type="datetime-local" class="form-control" />
            </div>
            <div class="form-check mb-3">
                <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                <label class="form-check-label" for="active">Active</label>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <Link href="/promotions" class="btn btn-link">Cancel</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ coupon: { type: Object, default: null } });

function toLocal(dt) {
    if (!dt) {
        return '';
    }
    const s = String(dt).replace(' ', 'T');
    return s.length > 16 ? s.slice(0, 16) : s;
}

const form = useForm({
    code: props.coupon?.code ?? '',
    percent_off: props.coupon?.percent_off ?? 10,
    expires_at: toLocal(props.coupon?.expires_at),
    is_active: props.coupon?.is_active ?? true,
});

function submit() {
    if (props.coupon) {
        form.put(`/promotions/${props.coupon.id}`);
    } else {
        form.post('/promotions');
    }
}
</script>
