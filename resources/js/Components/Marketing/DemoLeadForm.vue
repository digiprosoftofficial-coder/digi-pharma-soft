<template>
    <form class="sp-form" @submit.prevent="submit">
        <div v-if="successMessage" class="sp-alert-success" role="status">{{ successMessage }}</div>

        <div class="sp-form-grid two">
            <div class="sp-field">
                <label for="sp-pharmacy-name">{{ t('platform.pharmacy_name') }}</label>
                <input id="sp-pharmacy-name" v-model="form.pharmacy_name" type="text" required autocomplete="organization" />
                <div v-if="form.errors.pharmacy_name" class="sp-field-error">{{ form.errors.pharmacy_name }}</div>
            </div>
            <div class="sp-field">
                <label for="sp-owner-name">{{ t('platform.owner_name') }}</label>
                <input id="sp-owner-name" v-model="form.owner_name" type="text" required autocomplete="name" />
                <div v-if="form.errors.owner_name" class="sp-field-error">{{ form.errors.owner_name }}</div>
            </div>
            <div class="sp-field">
                <label for="sp-mobile">{{ t('platform.mobile') }}</label>
                <input id="sp-mobile" v-model="form.mobile" type="tel" required autocomplete="tel" />
                <div v-if="form.errors.mobile" class="sp-field-error">{{ form.errors.mobile }}</div>
            </div>
            <div class="sp-field">
                <label for="sp-branch-count">{{ t('platform.branch_count') }}</label>
                <input id="sp-branch-count" v-model.number="form.branch_count" type="number" min="1" max="500" required />
                <div v-if="form.errors.branch_count" class="sp-field-error">{{ form.errors.branch_count }}</div>
            </div>
        </div>

        <div class="sp-field">
            <label for="sp-preferred-at">{{ t('platform.preferred_at') }} <span class="text-muted">({{ t('platform.optional') }})</span></label>
            <input id="sp-preferred-at" v-model="form.preferred_at" type="datetime-local" />
            <div v-if="form.errors.preferred_at" class="sp-field-error">{{ form.errors.preferred_at }}</div>
        </div>

        <div class="sp-field">
            <label for="sp-message">{{ t('platform.message') }} <span class="text-muted">({{ t('platform.optional') }})</span></label>
            <textarea id="sp-message" v-model="form.message" rows="3" />
            <div v-if="form.errors.message" class="sp-field-error">{{ form.errors.message }}</div>
        </div>

        <button class="sp-btn sp-btn-primary" type="submit" :disabled="form.processing">
            {{ t('platform.submit_demo') }}
        </button>
    </form>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { t } = useLocale();
const page = usePage();

const form = useForm({
    pharmacy_name: '',
    owner_name: '',
    mobile: '',
    branch_count: 1,
    preferred_at: '',
    message: '',
});

const successMessage = computed(() => page.props.flash?.success || '');

function submit() {
    form.post('/demo', {
        preserveScroll: true,
        onSuccess: () => form.reset('message', 'preferred_at'),
    });
}
</script>
