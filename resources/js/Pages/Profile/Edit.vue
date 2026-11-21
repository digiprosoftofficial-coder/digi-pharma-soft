<template>
    <component :is="shell" :page-title="t('common.profile_settings')">
        <Head :title="t('common.profile_settings')" />

        <h1 class="page-heading d-none d-lg-block mb-3">{{ t('common.profile_settings') }}</h1>

        <form class="card border-0 shadow-sm card-body profile-form" @submit.prevent="submit">
            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                <div class="profile-avatar-preview">
                    <img v-if="previewUrl" :src="previewUrl" alt="" />
                    <span v-else>{{ initial }}</span>
                </div>
                <div class="min-w-0">
                    <label class="form-label mb-1" for="profile-avatar">{{ t('common.profile_photo') }}</label>
                    <input
                        id="profile-avatar"
                        ref="avatarInput"
                        type="file"
                        class="form-control form-control-sm"
                        accept="image/jpeg,image/png,image/webp,image/*"
                        @change="onAvatarSelected"
                    />
                    <div class="form-text">{{ t('common.profile_photo_hint') }}</div>
                    <div v-if="form.errors.avatar" class="text-danger small">{{ form.errors.avatar }}</div>
                    <button
                        v-if="previewUrl || profile.avatar_url"
                        type="button"
                        class="btn btn-link btn-sm px-0"
                        @click="removeAvatar"
                    >
                        {{ t('common.profile_photo_remove') }}
                    </button>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="profile-name">{{ t('common.profile_name') }}</label>
                <input id="profile-name" v-model="form.name" class="form-control" required maxlength="255" />
                <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="profile-email">{{ t('auth.email', 'Email') }}</label>
                <input id="profile-email" v-model="form.email" type="email" class="form-control" required maxlength="255" />
                <div v-if="form.errors.email" class="text-danger small">{{ form.errors.email }}</div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    {{ form.processing ? t('common.saving', 'Saving…') : t('common.save', 'Save') }}
                </button>
            </div>
        </form>
    </component>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    profile: { type: Object, required: true },
    usesPlatformShell: { type: Boolean, default: false },
});

const { t } = useLocale();
const shell = computed(() => (props.usesPlatformShell ? PlatformShellLayout : TenantShellLayout));
const avatarInput = ref(null);
const localPreview = ref('');

const form = useForm({
    name: props.profile.name || '',
    email: props.profile.email || '',
    avatar: null,
    remove_avatar: false,
});

const previewUrl = computed(() => localPreview.value || (!form.remove_avatar ? props.profile.avatar_url : '') || '');
const initial = computed(() => {
    const name = String(form.name || props.profile.name || '').trim();
    return name ? name.charAt(0).toUpperCase() : 'U';
});

function revokePreview() {
    if (localPreview.value) {
        URL.revokeObjectURL(localPreview.value);
        localPreview.value = '';
    }
}

function onAvatarSelected(event) {
    const file = event?.target?.files?.[0] ?? null;
    revokePreview();
    form.avatar = file;
    form.remove_avatar = false;
    if (file) {
        localPreview.value = URL.createObjectURL(file);
    }
}

function removeAvatar() {
    revokePreview();
    form.avatar = null;
    form.remove_avatar = true;
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
}

function submit() {
    form.post('/profile', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            revokePreview();
            form.avatar = null;
            form.remove_avatar = false;
            if (avatarInput.value) {
                avatarInput.value.value = '';
            }
        },
    });
}

onBeforeUnmount(() => {
    revokePreview();
});
</script>

<style scoped>
.profile-form {
    max-width: 36rem;
}

.profile-avatar-preview {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 4.5rem;
    height: 4.5rem;
    border-radius: 999px;
    overflow: hidden;
    background: color-mix(in srgb, var(--bs-primary) 55%, #ffffff 45%);
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
    flex: 0 0 auto;
}

.profile-avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
</style>
