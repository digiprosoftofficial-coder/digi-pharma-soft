<template>
    <TenantShellLayout :page-title="productType ? 'Edit product type' : 'New product type'">
        <Head :title="productType ? 'Edit product type' : 'New product type'" />
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
                <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug (optional)</label>
                <input v-model="form.slug" class="form-control" placeholder="auto-generated from name" />
                <div class="form-text">Stored on products as the type code (e.g. tablet, syrup).</div>
                <div v-if="form.errors.slug" class="text-danger small">{{ form.errors.slug }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Sort order</label>
                <input v-model.number="form.sort_order" type="number" min="0" class="form-control" />
                <div v-if="form.errors.sort_order" class="text-danger small">{{ form.errors.sort_order }}</div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <Link href="/product-types" class="btn btn-link">Cancel</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ productType: { type: Object, default: null } });

const form = useForm({
    name: props.productType?.name ?? '',
    slug: props.productType?.slug ?? '',
    sort_order: props.productType?.sort_order ?? 0,
});

function submit() {
    if (props.productType) {
        form.put(`/product-types/${props.productType.id}`);
    } else {
        form.post('/product-types');
    }
}
</script>
