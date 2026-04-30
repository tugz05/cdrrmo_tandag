<script setup>
import { ref, onMounted } from 'vue';
const props = defineProps({
    id: {
        type: String,
        default: 'offcanvasDefault'
    },
    end: {
        type: Boolean,
        default: false
    },
    scrolling: {
        type: Boolean,
        default: false
    },
    noPadding: {
        type: Boolean,
        default: false
    },
    backdrop: { type: Boolean, default: false}
})

const direction = ref('')

onMounted(() => {
    if (props.end) {
        direction.value = 'end'
    } else {
        direction.value = 'start'
    }
})

</script>
<template>
    <div :class="`offcanvas offcanvas-${direction}`" :data-bs-scroll="scrolling" :data-bs-backdrop="backdrop" tabindex="-1"
        :id="id" :aria-labelledby="`${id}Label`">
        <slot name="header"/>
        <div class="offcanvas-body" :class="{'p-0' : noPadding}">
            <!-- <div class="sticky-top top-0" style="height: 30px; pointer-events:none; background: linear-gradient(180deg, var(--bs-offcanvas-bg) 0%, #00000000 100%)"></div> -->
            <slot/>
            <!-- <div class="sticky-bottom bottom-0" style="height: 30px; pointer-events:none; background: linear-gradient(0deg, var(--bs-offcanvas-bg) 0%, #00000000 100%)"></div> -->
        </div>
    </div>
</template>