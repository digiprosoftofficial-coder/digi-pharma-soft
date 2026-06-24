<template>
    <div class="row g-3 mb-4">
        <div v-for="card in cards" :key="card.label" class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm card-body h-100">
                <div class="text-muted small">{{ card.label }}</div>
                <div class="h4 mb-0">{{ card.money ? formatMoney(card.value) : formatNumber(card.value) }}</div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useMoney } from '@/composables/useMoney';

defineProps({
    cards: { type: Array, required: true },
});

const { formatMoney } = useMoney();

function formatNumber(value) {
    return new Intl.NumberFormat().format(Number(value || 0));
}
</script>
