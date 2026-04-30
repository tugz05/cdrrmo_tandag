<script setup>
import { Head } from '@inertiajs/vue3';
import Content from '../Partials/Content.vue';

defineProps({
    post: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <Head :title="post.title || 'Article'" />
    <Content :post="post" />
</template>