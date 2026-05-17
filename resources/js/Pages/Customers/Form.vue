<template>
    <TenantShellLayout page-title="Customer">
        <Head :title="customer ? 'Edit customer' : 'New customer'" />
        <h1 class="h4 mb-3">{{ customer ? 'Edit customer' : 'New customer' }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Phone</label>
                <input v-model="form.phone" class="form-control" />
            </div>
            <div class="mb-2">
                <label class="form-label">Email</label>
                <input v-model="form.email" type="email" class="form-control" />
            </div>
            <div v-if="customer" class="alert alert-light border small">
                <div>Balance due: <strong>{{ customer.balance_due }}</strong></div>
                <div>Loyalty points: <strong>{{ customer.loyalty_points }}</strong></div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <Link href="/customers" class="btn btn-link">Cancel</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ customer: { type: Object, default: null } });

const form = useForm({
    name: props.customer?.name ?? '',
    phone: props.customer?.phone ?? '',
    email: props.customer?.email ?? '',
});

function submit() {
    if (props.customer) {
        form.put(`/customers/${props.customer.id}`);
    } else {
        form.post('/customers');
    }
}
</script>
