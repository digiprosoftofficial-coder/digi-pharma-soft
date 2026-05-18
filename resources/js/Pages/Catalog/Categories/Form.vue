<template>
    <TenantShellLayout :page-title="category ? 'Edit category' : 'New category'">
        <Head :title="category ? 'Edit category' : 'New category'" />
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
                <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug (optional)</label>
                <input v-model="form.slug" class="form-control" placeholder="auto-generated from name" />
                <div v-if="form.errors.slug" class="text-danger small">{{ form.errors.slug }}</div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <Link href="/categories" class="btn btn-link">Cancel</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ category: { type: Object, default: null } });

const form = useForm({
    name: props.category?.name ?? '',
    slug: props.category?.slug ?? '',
});

function submit() {
    if (props.category) {
        form.put(`/categories/${props.category.id}`);
    } else {
        form.post('/categories');
    }
}
</script>
