<script setup>
const props = defineProps({
    size: {
        type: String,
        default: '32px'
    },
    src: {
        type: String,
        default: 'image/default-avatar.png'
    }
})

</script>
<template>
    <img :src="`/storage/${src}`" alt="" class="rounded-3 border shadow" :style="`height: ${size}; width: ${size};`">
</template>