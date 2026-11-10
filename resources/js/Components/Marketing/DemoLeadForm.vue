<template>
    <form class="sp-form" :class="{ 'sp-form--modal': compact }" @submit.prevent="submit">
        <div v-if="successMessage" class="sp-alert-success" role="status">{{ successMessage }}</div>

        <div class="sp-field">
            <label for="sp-owner-name">
                <span class="sp-req">*</span> {{ t('platform.owner_name') }}
            </label>
            <input
                id="sp-owner-name"
                v-model="form.owner_name"
                type="text"
                required
                autocomplete="name"
                :placeholder="t('platform.owner_name_placeholder')"
            />
            <div v-if="form.errors.owner_name" class="sp-field-error">{{ form.errors.owner_name }}</div>
        </div>

        <div class="sp-form-grid two">
            <div class="sp-field">
                <label for="sp-pharmacy-name">
                    <span class="sp-req">*</span> {{ t('platform.pharmacy_name') }}
                </label>
                <input
                    id="sp-pharmacy-name"
                    v-model="form.pharmacy_name"
                    type="text"
                    required
                    autocomplete="organization"
                    :placeholder="t('platform.pharmacy_name_placeholder')"
                />
                <div v-if="form.errors.pharmacy_name" class="sp-field-error">{{ form.errors.pharmacy_name }}</div>
            </div>
            <div class="sp-field">
                <label for="sp-mobile">
                    <span class="sp-req">*</span> {{ t('platform.mobile') }}
                </label>
                <input
                    id="sp-mobile"
                    v-model="form.mobile"
                    type="tel"
                    required
                    autocomplete="tel"
                    :placeholder="t('platform.mobile_placeholder')"
                />
                <div v-if="form.errors.mobile" class="sp-field-error">{{ form.errors.mobile }}</div>
            </div>
        </div>

        <div class="sp-field">
            <label for="sp-email">
                <span class="sp-req">*</span> {{ t('platform.email') }}
            </label>
            <input
                id="sp-email"
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                :placeholder="t('platform.email_placeholder')"
            />
            <div v-if="form.errors.email" class="sp-field-error">{{ form.errors.email }}</div>
        </div>

        <div class="sp-field">
            <label for="sp-address">
                <span class="sp-req">*</span> {{ t('platform.address') }}
            </label>
            <input
                id="sp-address"
                v-model="form.address"
                type="text"
                required
                autocomplete="street-address"
                :placeholder="t('platform.address_placeholder')"
            />
            <div v-if="form.errors.address" class="sp-field-error">{{ form.errors.address }}</div>
        </div>

        <div class="sp-field">
            <label for="sp-message">{{ t('platform.message') }}</label>
            <textarea
                id="sp-message"
                v-model="form.message"
                rows="3"
                :placeholder="t('platform.message_placeholder')"
            />
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

defineProps({
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['success']);

const { t } = useLocale();
const page = usePage();

const form = useForm({
    owner_name: '',
    pharmacy_name: '',
    mobile: '',
    email: '',
    address: '',
    message: '',
    branch_count: 1,
});

const successMessage = computed(() => page.props.flash?.success || '');

function submit() {
    form.post('/demo', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('success');
        },
    });
}
</script>
