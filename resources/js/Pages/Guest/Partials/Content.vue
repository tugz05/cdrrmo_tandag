<script setup>
import { computed } from 'vue';

const props = defineProps({
    post: {
        type: Object,
        default: () => ({}),
    },
});

const featuredSrc = computed(() => {
    const path = props.post?.bg_image;
    if (!path || typeof path !== 'string') {
        return null;
    }
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    return path.startsWith('/') ? path : `/${path}`;
});
</script>

<template>
    <article class="guest-post-view container-fluid px-3 px-sm-4 py-4 py-md-5">
        <div class="guest-post-view__inner mx-auto">
            <figure v-if="featuredSrc" class="guest-post-view__hero mb-3 mb-md-4">
                <img
                    :src="featuredSrc"
                    alt=""
                    class="guest-post-view__hero-img rounded-3 shadow-sm"
                    loading="lazy"
                    decoding="async"
                />
            </figure>

            <h1 class="guest-post-view__title mb-3 mb-md-4 fw-bold text-break">
                {{ post.title }}
            </h1>

            <div class="guest-post-view__body text-body" v-html="post.content" />
        </div>
    </article>
</template>

<style scoped>
.guest-post-view {
    /* Comfortable reading width on large screens; full width on phones */
    --guest-post-max: min(100%, 40rem);
}

@media (min-width: 768px) {
    .guest-post-view {
        --guest-post-max: min(100%, 44rem);
    }
}

@media (min-width: 1200px) {
    .guest-post-view {
        --guest-post-max: min(100%, 48rem);
    }
}

.guest-post-view__inner {
    max-width: var(--guest-post-max);
}

.guest-post-view__title {
    font-size: clamp(1.35rem, 4.2vw + 0.5rem, 2rem);
    line-height: 1.25;
    overflow-wrap: anywhere;
    hyphens: auto;
}

.guest-post-view__hero {
    margin-inline: 0;
}

.guest-post-view__hero-img {
    display: block;
    width: 100%;
    height: auto;
    max-height: min(52vh, 28rem);
    object-fit: cover;
}

.guest-post-view__body {
    font-size: 1rem;
    line-height: 1.65;
    overflow-wrap: anywhere;
}

@media (min-width: 576px) {
    .guest-post-view__body {
        font-size: 1.0625rem;
    }
}

/* Rich text from Summernote / HTML — keep media inside the viewport */
.guest-post-view__body :deep(img),
.guest-post-view__body :deep(svg),
.guest-post-view__body :deep(video) {
    max-width: 100%;
    height: auto !important;
}

.guest-post-view__body :deep(iframe) {
    max-width: 100%;
}

.guest-post-view__body :deep(table) {
    width: 100%;
    max-width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    word-wrap: break-word;
    margin-bottom: 1rem;
}

.guest-post-view__body :deep(table) td,
.guest-post-view__body :deep(table) th {
    padding: 0.35rem 0.5rem;
    vertical-align: top;
}

.guest-post-view__body :deep(pre),
.guest-post-view__body :deep(code) {
    overflow-x: auto;
    max-width: 100%;
    white-space: pre-wrap;
    word-break: break-word;
}

.guest-post-view__body :deep(p) {
    margin-bottom: 1rem;
}

.guest-post-view__body :deep(ul),
.guest-post-view__body :deep(ol) {
    padding-left: 1.25rem;
    margin-bottom: 1rem;
}

.guest-post-view__body :deep(h1),
.guest-post-view__body :deep(h2),
.guest-post-view__body :deep(h3),
.guest-post-view__body :deep(h4) {
    margin-top: 1.25rem;
    margin-bottom: 0.75rem;
    line-height: 1.3;
    overflow-wrap: anywhere;
}

.guest-post-view__body :deep(a) {
    word-break: break-word;
}
</style>
