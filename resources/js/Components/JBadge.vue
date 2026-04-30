<script setup>
defineProps({
    text: {
        type: String,
    }
})
</script>
<template>
    <span class="bg-body-secondary rounded-pill border p-1 px-2 text-nowrap fw-light" 
        style="font-size: 0.7rem;">
        {{ text }}
    </span>
</template>