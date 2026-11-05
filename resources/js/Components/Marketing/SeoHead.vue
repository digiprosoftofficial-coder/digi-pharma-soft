<template>
    <Head :title="title">
        <meta head-key="description" name="description" :content="description" />
        <meta head-key="og:title" property="og:title" :content="title" />
        <meta head-key="og:description" property="og:description" :content="description" />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:url" property="og:url" :content="canonical" />
        <meta head-key="og:image" property="og:image" :content="ogImage" />
        <meta head-key="og:site_name" property="og:site_name" content="SoftoPharma" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="title" />
        <meta head-key="twitter:description" name="twitter:description" :content="description" />
        <meta head-key="twitter:image" name="twitter:image" :content="ogImage" />
        <link head-key="canonical" rel="canonical" :href="canonical" />
    </Head>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: 'SoftoPharma – Pharmacy Management Software | DigiProSoft',
    },
    description: {
        type: String,
        default:
            'Manage pharmacy sales, medicine inventory, batch expiry, purchases and accounts with SoftoPharma, a pharmacy management solution by DigiProSoft.',
    },
    path: { type: String, default: '/' },
    supportEmail: { type: String, default: '' },
    supportPhone: { type: String, default: '' },
});

const origin = typeof window !== 'undefined' ? window.location.origin : '';

const canonical = computed(() => {
    const path = props.path.startsWith('/') ? props.path : `/${props.path}`;
    return `${origin}${path}`;
});

const ogImage = computed(() => `${origin}/icons/icon-512.png`);

const schemaPayloads = computed(() => [
    {
        id: 'sp-org-jsonld',
        data: {
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: 'DigiProSoft',
            url: origin || undefined,
            brand: { '@type': 'Brand', name: 'SoftoPharma' },
            email: props.supportEmail || undefined,
            telephone: props.supportPhone || undefined,
        },
    },
    {
        id: 'sp-software-jsonld',
        data: {
            '@context': 'https://schema.org',
            '@type': 'SoftwareApplication',
            name: 'SoftoPharma',
            applicationCategory: 'BusinessApplication',
            operatingSystem: 'Web',
            description: props.description,
            offers: {
                '@type': 'Offer',
                priceCurrency: 'BDT',
                availability: 'https://schema.org/InStock',
            },
            provider: { '@type': 'Organization', name: 'DigiProSoft' },
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
