<script setup>
defineProps({
    text: {
        type: String,
        required: true
    },
    title: {
        type: String,
        default: null
    },
    type: {
        type: String,
        default: 'primary'
    },
    icon: {
        type: String,
        default: null
    }
})
</script>

<template>
    <div :class="`alert alert-${type}`" role="alert">
        <div class="d-flex gap-4">
            <span v-if="icon"><i :class="`bi bi-${icon}`"></i></span>
            <div class="d-flex flex-column gap-2">
                <h6 v-if="title" class="mb-0">{{ title }}</h6>
                <p class="mb-0"><small>{{ text }}</small></p>
            </div>
        </div>
    </div>
</template>