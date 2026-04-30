<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'link-primary-emphasis text-decoration-none'
        : 'link-secondary text-decoration-none'
);
</script>

<template>
    <Link :href="href" :class="classes" preserve-scroll>
        <slot />
    </Link>
</template>
