<template>
    <TenantShellLayout :page-title="manufacturer ? 'Edit manufacturer' : 'New manufacturer'">
        <Head :title="manufacturer ? 'Edit manufacturer' : 'New manufacturer'" />
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
                <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <Link href="/manufacturers" class="btn btn-link">Cancel</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ manufacturer: { type: Object, default: null } });

const form = useForm({ name: props.manufacturer?.name ?? '' });

function submit() {
    if (props.manufacturer) {
        form.put(`/manufacturers/${props.manufacturer.id}`);
    } else {
        form.post('/manufacturers');
    }
}
</script>
