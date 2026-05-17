<template>
    <TenantShellLayout page-title="Account">
        <Head :title="account ? 'Edit account' : 'New account'" />
        <h1 class="h4 mb-3">{{ account ? 'Edit account' : 'New account' }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Code</label>
                <input v-model="form.code" class="form-control" required :disabled="!!account" />
            </div>
            <div class="mb-2">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Type</label>
                <select v-model="form.type" class="form-select" required>
                    <option value="asset">asset</option>
                    <option value="liability">liability</option>
                    <option value="income">income</option>
                    <option value="expense">expense</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <Link href="/accounts" class="btn btn-link">Back</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ account: { type: Object, default: null } });

const form = useForm({
    code: props.account?.code ?? '',
    name: props.account?.name ?? '',
    type: props.account?.type ?? 'asset',
});

function submit() {
    if (props.account) {
        form.put('/accounts/' + props.account.id);
    } else {
        form.post('/accounts');
    }
}
</script>
