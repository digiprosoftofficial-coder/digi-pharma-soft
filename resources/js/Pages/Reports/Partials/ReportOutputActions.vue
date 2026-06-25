<template>
    <div class="btn-group btn-group-sm">
        <a :href="outputUrl('print')" target="_blank" rel="noopener" class="btn btn-outline-secondary">Print</a>
        <a :href="outputUrl('pdf')" class="btn btn-outline-secondary">PDF</a>
        <a :href="outputUrl('xlsx')" class="btn btn-outline-secondary">Excel</a>
        <a :href="outputUrl('csv')" class="btn btn-outline-secondary">CSV</a>
    </div>
</template>

<script setup>
const props = defineProps({
    exportPath: { type: String, required: true },
    params: { type: Object, default: () => ({}) },
});

function outputUrl(format) {
    const query = new URLSearchParams(cleanParams({ ...props.params, format }));

    return `${props.exportPath}?${query.toString()}`;
}

function cleanParams(params) {
    return Object.fromEntries(Object.entries(params).filter(([, value]) => value !== null && value !== ''));
}
</script>
