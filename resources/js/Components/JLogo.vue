<script setup>
const props = defineProps({
    labeled: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: '120px'
    }
})
</script>
<template>
    <!-- <img v-if="labeled" id="logo-labeled" class="mb-4" src="../Assets/gts-logo.svg" alt="" style="width:25%"> -->
    <img id="logo" src="../Assets/cdrrmo-logo.png" alt="" :style="`width:auto; height:${props.size}`">
</template>