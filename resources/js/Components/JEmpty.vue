<script setup>
defineProps({
    title: {
        type: String,
        default: 'No Data.'
    }
})
</script>
<template>
    <div class="text-center py-5">
        <img src=".././Assets/empty.svg" alt="Empty" style="width:100%; max-width:128px;">
        <h5 class="mt-4 text-muted">{{ title }}</h5>
    </div>
</template>