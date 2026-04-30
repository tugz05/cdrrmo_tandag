<script setup>
defineProps({
    color: {
        type: String,
        default: 'var(--bs-body-bg)'
    },
    fadeDirection: {
        type: String,
        default: 'top'
    },
    height: {
        type: String,
        default: '50'
    }
})
</script>
<template>
    <div :style="`position: sticky; z-index:1; pointer-events: none; background-image: linear-gradient(to ${fadeDirection}, ${color}, #00000000); height: ${height}px;`"
    ></div>
</template>