<template>
    <div v-if="links?.length > 3" class="card-footer bg-white">
        <nav class="d-flex flex-wrap gap-1 justify-content-center">
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.url || '#'"
                class="btn btn-sm"
                :class="link.active ? 'btn-primary' : 'btn-outline-secondary'"
                :disabled="!link.url"
                v-html="link.label"
            />
        </nav>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, default: () => [] },
});
</script>
