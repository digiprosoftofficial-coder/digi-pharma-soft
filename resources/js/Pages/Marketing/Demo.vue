<template>
    <MarketingLayout>
        <Head :title="t('platform.book_demo')" />
        <div class="container py-5" style="max-width: 640px">
            <h1 class="page-heading mb-2">{{ t('platform.book_demo') }}</h1>
            <p class="text-muted mb-4">{{ t('platform.demo_form_sub') }}</p>
            <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
                <div class="mb-3">
                    <label class="form-label">{{ t('platform.pharmacy_name') }}</label>
                    <input v-model="form.pharmacy_name" class="form-control" required />
                    <div v-if="form.errors.pharmacy_name" class="text-danger small">{{ form.errors.pharmacy_name }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ t('platform.owner_name') }}</label>
                    <input v-model="form.owner_name" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ t('platform.mobile') }}</label>
                    <input v-model="form.mobile" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ t('platform.branch_count') }}</label>
                    <input v-model.number="form.branch_count" type="number" min="1" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ t('platform.preferred_at') }}</label>
                    <input v-model="form.preferred_at" type="datetime-local" class="form-control" />
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ t('platform.message') }}</label>
                    <textarea v-model="form.message" class="form-control" rows="3" />
                </div>
                <button class="btn btn-primary" :disabled="form.processing">{{ t('platform.submit_demo') }}</button>
            </form>
        </div>
    </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ support: { type: Object, default: () => ({}) } });

const { t } = useLocale();
const form = useForm({
    pharmacy_name: '',
    owner_name: '',
    mobile: '',
    branch_count: 1,
    preferred_at: '',
    message: '',
});

function submit() {
    form.post('/demo');
}
</script>
