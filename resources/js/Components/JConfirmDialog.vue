<script setup>
import JModal from './JModal.vue';
</script>
<template>
    <JModal id="confirm-modal" sm centered z-index="2000">
        <template #footer>
            <div class="d-flex gap-3 w-100">
                <button class="btn-cancel btn btn-default w-50 text-nowrap">Cancel</button>
                <!-- <button class="btn-cancel btn btn-link link-secondary text-decoration-none w-50 text-nowrap">Cancel</button> -->
                <button class="btn-ok btn btn-danger w-50 text-nowrap text-light">Confirm</button>
            </div>
        </template>
    </JModal>
</template>