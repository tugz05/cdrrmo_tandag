<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePost } from '@/Composables/cdrrmo';
import { Head } from '@inertiajs/vue3';
import { watch } from 'vue';
import PostSection from './Partials/PostSection.vue'
defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    post: {
        type: Object,
        default: () => ({})
    },
    types: {
        type: Array,
        default: () => ([])
    }
})

const { form, edit, publish, editTitle, updateTitle, store } = usePost(props.post);

watch(
    () => props.post,
    (p) => {
        if (!p?.id) {
            return;
        }
        form.is_published = p.is_published == 1 || p.is_published === true;
        if (p.title != null) {
            form.title = p.title;
        }
    },
    { deep: true }
);
</script>

<template>
    <Head title="Edit Post" />
    <JHeaderTitle title="Edit Post" :breadcrumb-items="[{title: 'Posts', route: 'posts.index'}, {title: 'Edit Post' }]"/>


    <PostSection
        @on-submit="store"
        @edit-title="editTitle"
        @update-title="updateTitle"
        @publish="publish"
        :form="form"
        :existing-bg-image="post.bg_image"
        :post-types="types"
    />

</template>
