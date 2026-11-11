<template>
    <Head :title="resolvedTitle">
        <meta head-key="description" name="description" :content="resolvedDescription" />
        <meta v-if="keywords" head-key="keywords" name="keywords" :content="keywords" />
        <meta head-key="og:title" property="og:title" :content="resolvedTitle" />
        <meta head-key="og:description" property="og:description" :content="resolvedDescription" />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:url" property="og:url" :content="canonical" />
        <meta head-key="og:image" property="og:image" :content="ogImage" />
        <meta head-key="og:site_name" property="og:site_name" :content="siteName" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="resolvedTitle" />
        <meta head-key="twitter:description" name="twitter:description" :content="resolvedDescription" />
        <meta head-key="twitter:image" name="twitter:image" :content="ogImage" />
        <link head-key="canonical" rel="canonical" :href="canonical" />
    </Head>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    path: { type: String, default: '/' },
    supportEmail: { type: String, default: '' },
    supportPhone: { type: String, default: '' },
});

const page = usePage();
const branding = computed(() => page.props.branding || {});
const siteName = computed(() => branding.value.site_name || 'SoftoPharma');
const keywords = computed(() => branding.value.seo_keywords || '');
const resolvedTitle = computed(() => props.title || branding.value.seo_title || `${siteName.value} – Pharmacy Management Software`);
const resolvedDescription = computed(
    () =>
        props.description
        || branding.value.seo_description
        || 'Manage pharmacy sales, medicine inventory, batch expiry, purchases and accounts with SoftoPharma.',
);

const origin = typeof window !== 'undefined' ? window.location.origin : '';

const canonical = computed(() => {
    const path = props.path.startsWith('/') ? props.path : `/${props.path}`;
    return `${origin}${path}`;
});

const ogImage = computed(() => {
    const path = branding.value.og_image_url || '/brand/softopharma-logo.png';
    if (path.startsWith('http')) {
        return path;
    }
    return `${origin}${path}`;
});

const schemaPayloads = computed(() => [
    {
        id: 'sp-org-jsonld',
        data: {
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: 'DigiProSoft',
            url: origin || undefined,
            logo: ogImage.value || undefined,
            brand: { '@type': 'Brand', name: siteName.value },
            email: props.supportEmail || undefined,
            telephone: props.supportPhone || undefined,
        },
    },
    {
        id: 'sp-software-jsonld',
        data: {
            '@context': 'https://schema.org',
            '@type': 'SoftwareApplication',
            name: siteName.value,
            applicationCategory: 'BusinessApplication',
            operatingSystem: 'Web',
            description: resolvedDescription.value,
            image: ogImage.value || undefined,
            offers: {
                '@type': 'Offer',
                priceCurrency: 'BDT',
                availability: 'https://schema.org/InStock',
            },
            provider: { '@type': 'Organization', name: 'DigiProSoft' },
            keywords: keywords.value || undefined,
        },
    },
]);

function syncJsonLd() {
    if (typeof document === 'undefined') {
        return;
    }

    for (const item of schemaPayloads.value) {
        let el = document.getElementById(item.id);
        if (!el) {
            el = document.createElement('script');
            el.type = 'application/ld+json';
            el.id = item.id;
            document.head.appendChild(el);
        }
        el.textContent = JSON.stringify(item.data);
    }
}

function removeJsonLd() {
    if (typeof document === 'undefined') {
        return;
    }
    for (const item of schemaPayloads.value) {
        document.getElementById(item.id)?.remove();
    }
}

onMounted(syncJsonLd);
watch(schemaPayloads, syncJsonLd, { deep: true });
onBeforeUnmount(removeJsonLd);
</script>
