<template>
    <TenantShellLayout page-title="Supplier">
        <Head :title="supplier ? 'Edit supplier' : 'New supplier'" />
        <h1 class="h4 mb-3">{{ supplier ? 'Edit supplier' : 'New supplier' }}</h1>
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
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <Link href="/suppliers" class="btn btn-link">Cancel</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ supplier: { type: Object, default: null } });

const form = useForm({
    name: props.supplier?.name ?? '',
    phone: props.supplier?.phone ?? '',
    email: props.supplier?.email ?? '',
});

function submit() {
    if (props.supplier) {
        form.put(`/suppliers/${props.supplier.id}`);
    } else {
        form.post('/suppliers');
    }
}
</script>
