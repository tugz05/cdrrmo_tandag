<script setup>
const props = defineProps({
    title: String,
    description: String
})
</script>
<template>
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-normal" id="offcanvasScrollingLabel">
            <slot/>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
</template>