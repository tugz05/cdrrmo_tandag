<script setup>
const props = defineProps({
    title: ''
})
</script>
<template>
<h6 class="py-2 d-block opacity-50 fw-bold" style="font-size: 0.8rem; letter-spacing: 0.07rem; text-transform:uppercase;">
    <slot>
        {{ props.title }}
    </slot>
</h6>
</template>
