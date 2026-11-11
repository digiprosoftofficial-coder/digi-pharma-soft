<template>
    <PlatformShellLayout :page-title="t('platform.nav_marketing_settings')">
        <Head :title="t('platform.nav_marketing_settings')" />

        <h1 class="page-heading mb-2">{{ t('platform.marketing_settings_title') }}</h1>
        <p class="text-muted small mb-4">{{ t('platform.marketing_settings_help') }}</p>

        <form class="card border-0 shadow-sm card-body" enctype="multipart/form-data" @submit.prevent="submit">
            <h2 class="h6 mb-3">{{ t('platform.marketing_branding_section') }}</h2>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.marketing_site_name') }}</label>
                    <input v-model="form.site_name" class="form-control" required maxlength="120" />
                    <div v-if="form.errors.site_name" class="text-danger small">{{ form.errors.site_name }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.marketing_tagline') }}</label>
                    <input v-model="form.tagline" class="form-control" required maxlength="255" />
                    <div v-if="form.errors.tagline" class="text-danger small">{{ form.errors.tagline }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.marketing_product_of') }}</label>
                    <input v-model="form.product_of" class="form-control" required maxlength="255" />
                    <div v-if="form.errors.product_of" class="text-danger small">{{ form.errors.product_of }}</div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.marketing_logo') }}</label>
                    <div class="border rounded p-2 mb-2 bg-light text-center">
                        <img :src="logoPreview" alt="" style="max-height: 72px; max-width: 100%; object-fit: contain" />
                    </div>
                    <input type="file" class="form-control form-control-sm" accept="image/*" @change="onFile('logo', $event)" />
                    <div v-if="form.errors.logo" class="text-danger small">{{ form.errors.logo }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.marketing_icon') }}</label>
                    <div class="border rounded p-2 mb-2 bg-light text-center">
                        <img :src="iconPreview" alt="" style="max-height: 64px; max-width: 100%; object-fit: contain" />
                    </div>
                    <input type="file" class="form-control form-control-sm" accept="image/*" @change="onFile('icon', $event)" />
                    <div class="form-text">{{ t('platform.marketing_icon_help') }}</div>
                    <div v-if="form.errors.icon" class="text-danger small">{{ form.errors.icon }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.marketing_og_image') }}</label>
                    <div class="border rounded p-2 mb-2 bg-light text-center">
                        <img :src="ogPreview" alt="" style="max-height: 72px; max-width: 100%; object-fit: contain" />
                    </div>
                    <input type="file" class="form-control form-control-sm" accept="image/*" @change="onFile('og_image', $event)" />
                    <div class="form-text">{{ t('platform.marketing_og_image_help') }}</div>
                    <div v-if="form.errors.og_image" class="text-danger small">{{ form.errors.og_image }}</div>
                </div>
            </div>

            <h2 class="h6 mb-3">{{ t('platform.marketing_seo_section') }}</h2>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.marketing_seo_title') }}</label>
                <input v-model="form.seo_title" class="form-control" required maxlength="255" />
                <div class="form-text">{{ t('platform.marketing_seo_title_help') }}</div>
                <div v-if="form.errors.seo_title" class="text-danger small">{{ form.errors.seo_title }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.marketing_seo_description') }}</label>
                <textarea v-model="form.seo_description" class="form-control" rows="3" required maxlength="500" />
                <div class="form-text">{{ form.seo_description.length }}/500 · {{ t('platform.marketing_seo_description_help') }}</div>
                <div v-if="form.errors.seo_description" class="text-danger small">{{ form.errors.seo_description }}</div>
            </div>
            <div class="mb-4">
                <label class="form-label">{{ t('platform.marketing_seo_keywords') }}</label>
                <input v-model="form.seo_keywords" class="form-control" maxlength="500" />
                <div class="form-text">{{ t('platform.marketing_seo_keywords_help') }}</div>
                <div v-if="form.errors.seo_keywords" class="text-danger small">{{ form.errors.seo_keywords }}</div>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    marketing: { type: Object, required: true },
});

const { t } = useLocale();
const logoLocal = ref(null);
const iconLocal = ref(null);
const ogLocal = ref(null);

const form = useForm({
    site_name: props.marketing.site_name,
    tagline: props.marketing.tagline,
    product_of: props.marketing.product_of,
    seo_title: props.marketing.seo_title,
    seo_description: props.marketing.seo_description,
    seo_keywords: props.marketing.seo_keywords || '',
    logo: null,
    icon: null,
    og_image: null,
});

const logoPreview = computed(() => logoLocal.value || props.marketing.logo_url);
const iconPreview = computed(() => iconLocal.value || props.marketing.icon_url);
const ogPreview = computed(() => ogLocal.value || props.marketing.og_image_url);

function onFile(field, event) {
    const file = event.target.files?.[0] || null;
    form[field] = file;
    if (!file) {
        if (field === 'logo') logoLocal.value = null;
        if (field === 'icon') iconLocal.value = null;
        if (field === 'og_image') ogLocal.value = null;
        return;
    }
    const url = URL.createObjectURL(file);
    if (field === 'logo') logoLocal.value = url;
    if (field === 'icon') iconLocal.value = url;
    if (field === 'og_image') ogLocal.value = url;
}

function submit() {
    form.post('/platform/marketing-settings', {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>
