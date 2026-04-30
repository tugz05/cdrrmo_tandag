<script setup>
defineProps({
    text: {
        type: String,
        default: null
    },
    icon: {
        type: String,
        default: null
    },
    light: {
        type: Boolean,
        default: false
    },
    primary: {
        type: Boolean,
        default: false
    },
    type: {
        type: String,
        default: 'light' // light, primary, secondary, success, danger
    },
    dismissible: {
        type: Boolean,
        default: false
    },
    plain: {
        type: Boolean,
        default: false
    }
})
</script>

<template>
    <div :class="`${dismissible ? 'alert-dismissible fade show' : ''} ${plain === true ? 'alert alert-light border-0 bg-body-tertiary' : 'alert alert-' + type}`" role="alert">
        <button v-if="dismissible" type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <i v-if="icon" :class="`me-2 bi bi-${icon}`"></i>
        <span v-if="text !== null" v-html="text"></span>
        <slot></slot>
    </div>
</template>