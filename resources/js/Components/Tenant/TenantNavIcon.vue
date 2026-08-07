<template>
    <svg
        class="tenant-nav-icon"
        xmlns="http://www.w3.org/2000/svg"
        width="18"
        height="18"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path v-if="name === 'dashboard'" d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" />
        <g v-else-if="name === 'sales'">
            <path d="M6 6h12l-1.5 9H7.5L6 6z" />
            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
        </g>
        <g v-else-if="name === 'purchases'">
            <path d="M5 7h14l-1 12H6L5 7z" />
            <path d="M9 7V5h6v2" />
        </g>
        <g v-else-if="name === 'catalog'">
            <path d="M4 7h16v12H4z" />
            <path d="M8 7V5h8v2" />
            <path d="M9 11h6M9 15h4" />
        </g>
        <g v-else-if="name === 'inventory'">
            <path d="M4 8h16v11H4z" />
            <path d="M8 8V5h8v3" />
            <path d="M12 12v3" />
        </g>
        <path v-else-if="name === 'accounts'" d="M4 5h16v14H4z M8 9h8M8 13h5" />
        <path v-else-if="name === 'people'" d="M16 19v-1a4 4 0 0 0-8 0v1M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
        <g v-else-if="name === 'marketing'">
            <path d="M12 3 4 7v6c0 4 3.5 7 8 8 4.5-1 8-4 8-8V7z" />
        </g>
        <path v-else-if="name === 'settings'" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.2a1.7 1.7 0 0 0-1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.2a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.2a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8 1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.2a1.7 1.7 0 0 0-1.5 1z" />
        <path v-else-if="name === 'list'" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
        <path v-else-if="name === 'plus'" d="M12 5v14M5 12h14" />
        <path v-else-if="name === 'package'" d="M12 3 3 8l9 5 9-5-9-5z M3 8v8l9 5 9-5V8" />
        <path v-else-if="name === 'return'" d="M4 7h12l-3-3M20 17H8l3 3" />
        <path v-else-if="name === 'bill'" d="M6 4h12v16H6z M9 8h6M9 12h6M9 16h4" />
        <path v-else-if="name === 'category'" d="M4 6h7v7H4zM13 6h7v4h-7zM13 13h7v7h-7zM4 16h7v4H4z" />
        <path v-else-if="name === 'tag'" d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82zM7 7h.01" />
        <path v-else-if="name === 'factory'" d="M3 21V9l6-3v6l6-3v12" />
        <path v-else-if="name === 'upload'" d="M12 16V4M8 8l4-4 4 4M4 20h16" />
        <path v-else-if="name === 'transfer'" d="M7 7h11l-3-3M17 17H6l3 3" />
        <path v-else-if="name === 'users'" d="M16 19v-1a4 4 0 0 0-8 0v1M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
        <path v-else-if="name === 'supplier'" d="M3 9h18v10H3z M7 9V6h10v3" />
        <path v-else-if="name === 'customer'" d="M20 19v-1a6 6 0 0 0-12 0v1M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
        <path v-else-if="name === 'employee'" d="M16 19v-1a4 4 0 0 0-8 0v1M12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
        <path v-else-if="name === 'promo'" d="M4 9v6l8 4 8-4V9l-8-4z" />
        <path v-else-if="name === 'sms'" d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
        <path v-else-if="name === 'notes'" d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z M14 3v6h6 M8 13h8M8 17h5" />
        <path v-else-if="name === 'report'" d="M4 20V4M8 20v-8M12 20V8M16 20v-4M20 20V12" />
        <path v-else-if="name === 'support'" d="M12 3 4 7v6c0 4.4 3.6 8 8 8h0a4 4 0 0 0 4-4v-1" />
        <path v-else-if="name === 'globe'" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20" />
        <circle v-else cx="12" cy="12" r="2" />
    </svg>
</template>

<script setup>
defineProps({
    name: { type: String, required: true },
});
</script>
