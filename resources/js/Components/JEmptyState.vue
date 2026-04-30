<script setup>
defineProps({
    text: {
        type: String,
        default: 'No Data Found!'
    },
    sm: {
        type: Boolean,
        default: false
    },
    md: {
        type: Boolean,
        default: false
    }

})
</script>
<template>
    <div class="text-center">
        <h3 v-if="sm" class="text-center"><i class="bi bi-emoji-frown-fill opacity-25"></i></h3>
        <h2 v-else-if="md" class="text-center"><i class="bi bi-emoji-frown-fill opacity-25"></i></h2>
        <h1 v-else class="text-center"><i class="bi bi-emoji-frown-fill opacity-25"></i></h1>
        <p class="mx-auto text-muted mt-4" style="max-width:500px; font-size: smaller;">{{ text }}</p>
    </div>
</template>