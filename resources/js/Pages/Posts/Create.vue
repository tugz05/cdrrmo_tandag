<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePost } from '@/Composables/cdrrmo';
import { Head, } from '@inertiajs/vue3';
import PostSection from './Partials/PostSection.vue'
defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    types: {
        type: Array,
        default: () => ([])
    }
})

console.log(props.types)

const  { form, store } = usePost();

</script>

<template>
    <Head title="Create" />

    <JHeaderTitle title="Create" :breadcrumb-items="[{title: 'Posts', route: 'posts.index'}, {title: 'Create' }]"/>


    <PostSection @on-submit="store" :form="form" :post-types="types" />

</template>
