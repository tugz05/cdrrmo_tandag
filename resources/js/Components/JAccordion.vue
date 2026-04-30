<script setup>
defineProps({
    id: {
        type: String,
        default: 'j-accordion'
    },
    flush: {
        type: Boolean,
        default: false
    }
});
</script>                                       
<template>
    <div
        class="accordion"
        :class="{'accordion-flush' : flush}"
        :id="id"
    >
        <slot/>
    </div>
</template>
