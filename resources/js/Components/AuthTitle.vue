<script setup>
defineProps({
    title: {
        type: String,
    },
    description: {
        type: String
    }
})
</script>
<template>
    <div class="mb-8">
        <h2 class="fw-normal" style="text-transform: Capitalize;">{{ title }}</h2>
        <p class="text-muted">{{ description }}</p>
    </div>
</template>