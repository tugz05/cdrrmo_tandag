<script setup lang="ts">
import JCard from '@/Components/JCard.vue';
defineProps({
    title: String,
    count: Number
})

</script>

<template>
    <div class="col">
        <JCard>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">{{ title }}</span>
                <h4 class="fw-bold m-0">{{ count }}</h4>
            </div>
        </JCard>
    </div>
</template>
