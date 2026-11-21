<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.9"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <template v-if="name === 'overview'">
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
        </template>
        <template v-else-if="name === 'leads'">
            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </template>
        <template v-else-if="name === 'demos'">
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <line x1="16" y1="2" x2="16" y2="6" />
            <line x1="8" y1="2" x2="8" y2="6" />
            <line x1="3" y1="10" x2="21" y2="10" />
        </template>
        <template v-else-if="name === 'payments'">
            <rect x="2" y="5" width="20" height="14" rx="2" />
            <line x1="2" y1="10" x2="22" y2="10" />
        </template>
        <template v-else-if="name === 'pharmacies'">
            <path d="M3 21h18" />
            <path d="M5 21V7l7-4 7 4v14" />
            <path d="M9 21v-6h6v6" />
        </template>
        <template v-else-if="name === 'plans'">
            <polygon points="12 2 2 7 12 12 22 7 12 2" />
            <polyline points="2 17 12 22 22 17" />
            <polyline points="2 12 12 17 22 12" />
        </template>
        <template v-else-if="name === 'resellers'">
            <circle cx="9" cy="8" r="3.5" />
            <path d="M2 20a7 7 0 0 1 14 0" />
            <path d="M16 11h6" />
            <path d="M19 8v6" />
        </template>
        <template v-else-if="name === 'master'">
            <path d="M10.5 2.5 3 7v10l7.5 4.5L18 17V7L10.5 2.5Z" />
            <path d="M10.5 12 3 7.5" />
            <path d="M10.5 12 18 7.5" />
            <path d="M10.5 12v9.5" />
        </template>
        <template v-else-if="name === 'suggestions'">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v4l2.5 1.5" />
        </template>
        <template v-else-if="name === 'templates'">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <path d="M14 2v6h6" />
            <path d="M8 13h8" />
            <path d="M8 17h6" />
        </template>
        <template v-else-if="name === 'product-types'">
            <rect x="3" y="3" width="7" height="7" rx="1.5" />
            <rect x="14" y="3" width="7" height="7" rx="1.5" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" />
            <path d="M17.5 14.5v6" />
            <path d="M14.5 17.5h6" />
        </template>
        <template v-else-if="name === 'announcements'">
            <path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1Z" />
            <path d="M16 9a4 4 0 0 1 0 6" />
            <path d="M18.5 7a7 7 0 0 1 0 10" />
        </template>
        <template v-else-if="name === 'billing'">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <path d="M14 2v6h6" />
            <path d="M8 13h2" />
            <path d="M8 17h8" />
            <path d="M16 13h.01" />
        </template>
        <template v-else-if="name === 'notifications'">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
        </template>
        <template v-else-if="name === 'admins'">
            <path d="M12 2 4 5v6c0 5 3.4 9.4 8 11 4.6-1.6 8-6 8-11V5l-8-3Z" />
            <path d="M9 12l2 2 4-4" />
        </template>
        <template v-else-if="name === 'audit'">
            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" />
            <rect x="9" y="3" width="6" height="4" rx="1" />
            <path d="M9 12h6" />
            <path d="M9 16h4" />
        </template>
        <template v-else-if="name === 'health'">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
        </template>
        <template v-else-if="name === 'marketing'">
            <circle cx="12" cy="12" r="9" />
            <path d="M2 12h20" />
            <path d="M12 2a15 15 0 0 1 0 20" />
            <path d="M12 2a15 15 0 0 0 0 20" />
        </template>
        <template v-else>
            <circle cx="12" cy="12" r="3" />
            <path d="M12 1v2" />
            <path d="M12 21v2" />
            <path d="M4.2 4.2l1.4 1.4" />
            <path d="M18.4 18.4l1.4 1.4" />
            <path d="M1 12h2" />
            <path d="M21 12h2" />
            <path d="M4.2 19.8l1.4-1.4" />
            <path d="M18.4 5.6l1.4-1.4" />
        </template>
    </svg>
</template>

<script setup>
defineProps({
    name: { type: String, required: true },
});
</script>
