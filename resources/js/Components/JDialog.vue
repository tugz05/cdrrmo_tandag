<script setup>
import JModal from '@/Components/JModal.vue'
import JButton from '@/Components/JButton.vue'
import { defaultConfirmModalId, confirmDialog  } from '@/Helpers/JHelper';

const props = defineProps({
    modalId: defaultConfirmModalId(),
})


const confirm = () => {
    confirmDialog('', 'confirm-modal', true)
}
</script>
<template>
    <JModal id="confirm-modal" sm centered> 
        <p>Lorem ipsum dolor sit amet.</p>
        <div class="d-flex gap-3 justify-content-between mt-4">
            <div id="confirm-dialog-btn-left" class="w-100">
                <JButton secondary icon="x-circle" text="No" class="w-100" data-bs-dismiss="modal" :data-bs-target="modalId" aria-label="Close"/>
            </div>
            <div id="confirm-dialog-btn-right" class="w-100">
                <JButton @click="confirm()" primary icon="check-circle" text="Yes" class="w-100"/>
            </div>
        </div>
    </JModal>
</template>