<template>
    <div class="sp-product-frame" :aria-label="label">
        <div class="sp-product-chrome">
            <span class="dot" /><span class="dot" /><span class="dot" />
            <span class="ms-2">{{ label }}</span>
        </div>
        <img
            v-if="src"
            class="sp-shot-img"
            :src="src"
            :alt="alt || label"
            loading="lazy"
            width="1200"
            height="760"
        />
        <div v-else class="sp-product-body">
            <div class="sp-product-rail" aria-hidden="true">
                <i /><i /><i /><i /><i />
            </div>
            <div class="sp-product-main">
                <p class="sp-ui-label">{{ label }}</p>
                <div class="sp-ui-row">
                    <div class="sp-ui-bar wide" />
                    <div class="sp-ui-bar mid" />
                    <div class="sp-ui-bar accent" />
                </div>
                <div v-if="variant === 'cards'" class="sp-ui-cards">
                    <div class="sp-ui-card"><div class="sp-ui-bar mid" /><div class="sp-ui-bar wide" /></div>
                    <div class="sp-ui-card"><div class="sp-ui-bar mid" /><div class="sp-ui-bar accent" /></div>
                    <div class="sp-ui-card"><div class="sp-ui-bar mid" /><div class="sp-ui-bar wide" /></div>
                </div>
                <div v-else class="sp-ui-table">
                    <div v-for="n in 5" :key="n" class="sp-ui-table-row">
                        <span /><span /><span /><span />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    src: { type: String, default: '' },
    alt: { type: String, default: '' },
    variant: { type: String, default: 'table' },
});
</script>
