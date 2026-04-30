<script setup>
defineProps({
    id: {
        type: String,
        required: true
    },
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        required: true
    }
})
</script>
<template>
    <div class="border rounded p-3 h-100 bg-body-tertiary">
        <label :for="id" class="d-flex gap-3 align-items-center justify-content-between">
            <section class="w-100">
                {{ title }}
            </section>
            <section>
                <div class="form-check form-switch" :title="title">
                    <slot/>
                </div>
            </section>
        </label>  
        <span class="text-muted d-block pt-5" style="font-size: small;">{{ description }}</span>
    </div>
</template>