<script setup>
const props = defineProps({
    title: ''
})
</script>
<template>
<h6 class="py-2 d-block fw-bold text-body-secondary" style="font-size: 1rem;">
    <slot>
        {{ props.title }}
    </slot>
</h6>
</template>
