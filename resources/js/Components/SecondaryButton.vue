<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
    text: {
        type: String,
        defualt: null
    }
});
</script>

<template>
    <button
        :type="type"
        class="btn btn-secondary"
    >
        <slot />
    </button>
</template>
