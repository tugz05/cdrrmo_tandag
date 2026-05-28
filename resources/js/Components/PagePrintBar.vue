<script setup>
defineProps({
    label: {
        type: String,
        default: 'Print this page',
    },
});

function printPage() {
    window.print();
}
</script>

<template>
    <div class="no-print d-flex flex-wrap align-items-center gap-2 mb-3">
        <button type="button" class="btn btn-outline-secondary btn-sm" @click="printPage">
            <i class="bi bi-printer me-1" aria-hidden="true" />
            {{ label }}
        </button>
        <slot />
    </div>
</template>
