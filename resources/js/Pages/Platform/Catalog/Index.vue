<template>
    <PlatformShellLayout :page-title="t('platform.catalog_title')">
        <Head :title="t('platform.nav_catalog')" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted small mb-0">{{ t('platform.catalog_sub') }}</p>
            <Link href="/platform/catalog-templates/create" class="btn btn-primary btn-sm">{{ t('platform.new_catalog') }}</Link>
        </div>
        <div class="row g-3">
            <div v-for="tpl in templates" :key="tpl.id" class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h2 class="h6 mb-0">{{ tpl.name }}</h2>
                            <span class="badge" :class="tpl.is_published ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ tpl.is_published ? t('platform.catalog_published') : t('platform.catalog_draft') }}
                            </span>
                        </div>
                        <p class="small text-muted mb-2"><code>{{ tpl.slug }}</code></p>
                        <p class="small mb-3">{{ t('platform.catalog_items_count', { count: tpl.items_count }) }}</p>
                        <Link :href="`/platform/catalog-templates/${tpl.id}`" class="btn btn-sm btn-outline-primary me-1">
                            {{ t('platform.catalog_manage') }}
                        </Link>
                        <Link :href="`/platform/catalog-templates/${tpl.id}/edit`" class="btn btn-sm btn-outline-secondary">
                            {{ t('common.edit') }}
                        </Link>
                    </div>
                </div>
            </div>
            <div v-if="!templates.length" class="col-12 text-muted text-center py-4">{{ t('common.no_results') }}</div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ templates: { type: Array, required: true } });

const { t } = useLocale();
</script>
