<script setup>
import JButton from './JButton.vue'

defineProps({
    text: {
        type: String,
        default: 'Create'
    }
})
</script>
<template>
    <div class="d-flex align-items-center justify-content-between">
        <div class="w-100">
            <hr>
        </div>
        <div class="px-5">
            <JButton lg primary outline 
                @click="$emit('handleCreate')"
                class="text-nowrap"
                :text="text" 
                icon="plus-lg"
            />
        </div>
        <div class="w-100">
            <hr>
        </div>
    </div>
</template>