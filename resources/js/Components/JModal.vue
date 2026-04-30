<script setup>
import { computed, onMounted, ref, Teleport } from 'vue';

const props = defineProps({
    title: String,
    id: {
        type: String,
        default: 'default-modal'
    },
    centered: {
        type: Boolean,
        defualt: false
    },
    sm: {
        type: Boolean,
        default: false
    }, 
    xl: {
        type: Boolean,
        default: false
    },
    fullscreen: {
        type: Boolean,
        default: false
    },
    nodismiss: {
        type: Boolean,
        default: false
    },
    zIndex: {
        type: String,
        default: '1055'
    }
})

const modalDialogClasses = computed(() => ({
    'modal-dialog-centered': props.centered,
    'modal-sm': props.sm,
    'modal-xl': props.xl,
    'modal-fullscreen': props.fullscreen,
})) 
</script>

<template>
    <Teleport to="body">
        <div class="modal fade" :id="props.id" tabindex="-1" :aria-labelledby="`modal-${props.id}`" aria-hidden="true" :style="`z-index: ${zIndex}`">
            <div class="modal-dialog" :class="modalDialogClasses">
                <div class="modal-content">
                    <div class="modal-header border-bottom-0">
                        <slot name="header">
                            <h5 class="modal-title fw-normal" :id="`modal-${props.id}`">{{ props.title }}</h5>
                            <button v-if="!nodismiss" type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </slot>
                    </div>
                    <div class="modal-body">
                        <slot></slot>
                    </div>
                    <div class="modal-footer gap-1 border-top-0">
                        <div v-if="$slots.footerstart || $slots.footerend" class="d-flex justify-content-between w-100">
                            <div class="d-flex gap-3">
                                <slot name="footerstart"></slot>
                            </div>
                            <div class="d-flex gap-3">
                                <slot name="footerend"></slot>
                            </div>
                        </div>
                        <slot name="footer"></slot>
                        <!-- <slot name="footer">
                            <div v-if="$slots.footerstart">
                                <slot name="footerstart"></slot>
                            </div>
                        </slot> -->
                    </div>
                </div>
            </div>
        </div>
    </Teleport>    
</template>