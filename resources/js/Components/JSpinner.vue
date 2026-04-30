<script setup>
defineProps({
    sm: {
        type: Boolean,
        defualt: false
    }
})
</script>

<template>
    <div class="text-center ">
        <div class="spinner-border opacity-50" :class="{'spinner-border-sm': sm}" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</template>