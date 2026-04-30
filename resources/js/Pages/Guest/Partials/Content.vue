<script setup>
const props = defineProps({
    post: {
        type: Object,
        default: () => ({}),
    },
});
</script>

<template>
    <div class="mx-auto p-5" style="max-width: 500px;">
        <h1>{{ post.title }}</h1>
        <div>
            <span v-html="post.content"></span>
        </div>
    </div>
</template>