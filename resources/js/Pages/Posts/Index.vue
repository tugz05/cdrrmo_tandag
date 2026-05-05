<script setup>
import { computed } from 'vue';
import JButton from '@/Components/JButton.vue';
import JModal from '@/Components/JModal.vue';
import JDropdownItem from '@/Components/JDropdownItem.vue';
import JDropdownMenu from '@/Components/JDropdownMenu.vue';
import JHeaderTitle from '@/Components/JHeaderTitle.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { usePost } from '@/Composables/usePost';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import JModalButtonClose from '@/Components/JModalButtonClose.vue';
import { timeAgo } from '@/Helpers/JTimeAgo';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    news: {
        type: Array,
        default: () => [],
    },
    post_types: {
        type: Array,
        default: () => [],
    },
    active_tab: {
        type: String,
        default: null,
    },
});

const filterTabs = [
    { type: null, label: 'News', icon: 'bi-newspaper', description: 'Advisories and office updates' },
    {
        type: 'Safety Tips',
        label: 'Safety tips',
        icon: 'bi-shield-check',
        description: 'Guidance for the public',
    },
    {
        type: 'Emergency Preparedness',
        label: 'Preparedness',
        icon: 'bi-backpack2',
        description: 'Readiness and planning',
    },
];

const activeFilterMeta = computed(() => {
    const t = filterTabs.find((f) => f.type === props.active_tab);
    return t ?? filterTabs[0];
});

function filterPost(type = null) {
    router.get(route('posts.index', type));
}

const { form, create, store, destroy } = usePost();

function coverUrl(post) {
    const raw = post?.bg_image;
    if (!raw) {
        return null;
    }
    const s = String(raw).trim();
    if (s.startsWith('http://') || s.startsWith('https://')) {
        return s;
    }
    return s.startsWith('/') ? s : `/${s}`;
}

function typeBadgeClass(type) {
    if (type === 'Safety Tips') {
        return 'posts-index__type posts-index__type--safety';
    }
    if (type === 'Emergency Preparedness') {
        return 'posts-index__type posts-index__type--prep';
    }
    return 'posts-index__type posts-index__type--news';
}

const emptyMessage = computed(() => {
    if (props.active_tab === 'Safety Tips') {
        return 'No safety tips in this category yet. Create one to publish guidance for residents.';
    }
    if (props.active_tab === 'Emergency Preparedness') {
        return 'No preparedness posts yet. Add articles on kits, plans, and evacuation.';
    }
    return 'No news posts yet. Publish stories to keep the public informed.';
});
</script>

<template>
    <Head title="Posts" />

    <div class="posts-index">
        <JHeaderTitle title="Posts" :breadcrumb-items="[{ title: 'Posts' }]" />

        <header class="posts-index__hero card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-8">
                        <p class="text-uppercase small fw-semibold text-primary mb-2 letter-spacing">
                            Content library
                        </p>
                        <h2 class="h4 fw-bold text-body mb-2">News, safety tips &amp; preparedness</h2>
                        <p class="text-body-secondary mb-0 small mb-lg-0">
                            Curate public-facing content by category. Only posts with a featured image can go live
                            in the public API.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <JButton primary icon="plus-lg" text="Create post" class="posts-index__create" @click="create()" />
                    </div>
                </div>
            </div>
        </header>

        <div
            class="posts-index__toolbar card border-0 shadow-sm mb-4"
            role="navigation"
            aria-label="Post categories"
        >
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center gap-3">
                    <div class="posts-index__tabs flex-grow-1 overflow-auto">
                        <div class="btn-group posts-index__btn-group" role="group" aria-label="Filter by type">
                            <button
                                v-for="tab in filterTabs"
                                :key="tab.label"
                                type="button"
                                class="btn posts-index__tab"
                                :class="{ 'posts-index__tab--active': active_tab === tab.type }"
                                @click="filterPost(tab.type)"
                            >
                                <i :class="['bi', tab.icon, 'me-2']" aria-hidden="true" />
                                <span class="posts-index__tab-label">{{ tab.label }}</span>
                            </button>
                        </div>
                    </div>
                    <p class="posts-index__context small text-body-secondary mb-0 flex-shrink-0 d-none d-md-block">
                        Showing
                        <strong class="text-body">{{ activeFilterMeta.label }}</strong>
                        — {{ activeFilterMeta.description }}
                    </p>
                </div>
            </div>
        </div>

        <JEmptyState v-if="!news.length" md :text="emptyMessage" class="posts-index__empty py-5" />

        <div v-else class="row g-4">
            <div v-for="newsItem in news" :key="newsItem.id" class="col-12 col-md-6 col-xl-4">
                <article class="card posts-index__card h-100 border-0 shadow-sm overflow-hidden">
                    <div class="posts-index__media">
                        <img
                            v-if="coverUrl(newsItem)"
                            :src="coverUrl(newsItem)"
                            class="posts-index__img"
                            alt=""
                            loading="lazy"
                        />
                        <div v-else class="posts-index__placeholder" aria-hidden="true">
                            <i class="bi bi-image posts-index__placeholder-icon" />
                        </div>
                        <div class="posts-index__media-overlay" />
                        <div class="posts-index__media-top d-flex justify-content-between align-items-start gap-2">
                            <span :class="['badge rounded-pill fw-semibold', typeBadgeClass(newsItem.type)]">
                                {{ newsItem.type }}
                            </span>
                            <div class="dropdown">
                                <JButton
                                    default
                                    outline
                                    sm
                                    icon="three-dots-vertical"
                                    class="posts-index__kebab border-0 bg-body shadow-sm"
                                    data-bs-toggle="dropdown"
                                    :aria-label="`Actions for ${newsItem.title}`"
                                />
                                <JDropdownMenu class="dropdown-menu-end border-0 shadow" style="min-width: 9rem">
                                    <JDropdownItem icon="trash3" is-danger text="Delete" @click="destroy(newsItem)" />
                                </JDropdownMenu>
                            </div>
                        </div>
                        <span
                            class="badge rounded-pill posts-index__status"
                            :class="newsItem.is_published ? 'text-bg-success' : 'text-bg-secondary'"
                        >
                            {{ newsItem.is_published ? 'Published' : 'Draft' }}
                        </span>
                    </div>
                    <div class="card-body d-flex flex-column pt-4 pb-3">
                        <h3 class="posts-index__title h6 fw-bold mb-3">
                            {{ newsItem.title }}
                        </h3>
                        <div class="mt-auto d-flex align-items-center justify-content-between gap-2 pt-2 border-top">
                            <time
                                class="small text-body-secondary text-nowrap"
                                :datetime="newsItem.created_at"
                                :title="newsItem.created_at"
                            >
                                {{ timeAgo(newsItem.created_at) }}
                            </time>
                            <JButton
                                :href="route('posts.edit', newsItem.id)"
                                primary
                                outline
                                sm
                                class="text-nowrap"
                            >
                                Open
                                <i class="bi bi-arrow-up-right ms-1" aria-hidden="true" />
                            </JButton>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <JModal sm>
        <form id="post-form" @submit.prevent="store()">
            <JFloatingInput v-model="form.title" label="Title" type="textarea" required />
            <div class="mb-3">
                <label class="form-label small fw-semibold mb-1">Featured image</label>
                <input
                    type="file"
                    class="form-control form-control-sm"
                    accept="image/jpeg,image/png,image/webp,image/jpg"
                    @change="form.bg_image = $event.target.files?.[0] ?? null"
                />
                <p class="small text-muted mb-0 mt-1">
                    Upload before publishing. Posts appear in the public API only when published with an image.
                </p>
                <p v-if="form.errors?.bg_image" class="text-danger small mb-0 mt-1">{{ form.errors.bg_image }}</p>
            </div>
            <JFloatingInput v-model="form.type" label="Type" type="select" required>
                <template #option>
                    <option value="" selected hidden>Select</option>
                    <option value="News">News</option>
                    <option value="Safety Tips">Safety Tips</option>
                    <option value="Emergency Preparedness">Emergency Preparedness</option>
                </template>
            </JFloatingInput>
        </form>
        <template #footerend>
            <JModalButtonClose />
            <JButton type="submit" primary form="post-form" :processing="form.processing" text="Save" />
        </template>
    </JModal>
</template>

<style scoped>
.posts-index {
    max-width: 1400px;
}

.posts-index__hero .letter-spacing {
    letter-spacing: 0.08em;
    font-size: 0.7rem;
}

.posts-index__tabs {
    -webkit-overflow-scrolling: touch;
}

.posts-index__btn-group {
    flex-wrap: nowrap;
}

.posts-index__tab {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--cdrrmo-ink-muted, #64748b);
    background: var(--cdrrmo-surface, #f0f9ff);
    border: 1px solid var(--cdrrmo-border, rgba(2, 132, 199, 0.12));
    padding-inline: 1rem;
    white-space: nowrap;
}

.posts-index__tab:hover {
    color: var(--cdrrmo-ink, #0c4a6e);
    background: #fff;
}

.posts-index__tab--active {
    color: #fff !important;
    background: var(--cdrrmo-primary, #0284c7) !important;
    border-color: var(--cdrrmo-primary, #0284c7) !important;
    box-shadow: 0 2px 8px color-mix(in srgb, var(--cdrrmo-primary, #0284c7) 35%, transparent);
}

.posts-index__context {
    max-width: 22rem;
}

.posts-index__card {
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.posts-index__card:hover {
    box-shadow: 0 0.5rem 1.25rem rgba(12, 74, 110, 0.12) !important;
    transform: translateY(-2px);
}

.posts-index__media {
    position: relative;
    aspect-ratio: 16 / 10;
    background: linear-gradient(135deg, var(--cdrrmo-100, #e0f2fe) 0%, var(--cdrrmo-200, #bae6fd) 100%);
}

.posts-index__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.posts-index__placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(145deg, var(--cdrrmo-100, #e0f2fe), var(--cdrrmo-50, #f0f9ff));
}

.posts-index__placeholder-icon {
    font-size: 2.5rem;
    color: var(--cdrrmo-300, #7dd3fc);
    opacity: 0.65;
}

.posts-index__media-overlay {
    pointer-events: none;
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(8, 47, 73, 0.55) 0%, transparent 45%);
}

.posts-index__media-top {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    right: 0.75rem;
    z-index: 1;
}

.posts-index__status {
    position: absolute;
    bottom: 0.75rem;
    left: 0.75rem;
    z-index: 1;
    font-weight: 600;
    font-size: 0.7rem;
    letter-spacing: 0.02em;
}

.posts-index__type {
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border: 1px solid transparent;
}

.posts-index__type--news {
    background: rgba(255, 255, 255, 0.95);
    color: var(--cdrrmo-800, #075985);
    border-color: rgba(255, 255, 255, 0.5);
}

.posts-index__type--safety {
    background: rgba(255, 255, 255, 0.95);
    color: #0f766e;
    border-color: rgba(255, 255, 255, 0.5);
}

.posts-index__type--prep {
    background: rgba(255, 255, 255, 0.95);
    color: #92400e;
    border-color: rgba(255, 255, 255, 0.5);
}

.posts-index__kebab {
    --bs-btn-padding-x: 0.35rem;
    --bs-btn-padding-y: 0.25rem;
}

.posts-index__title {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.45;
    color: var(--cdrrmo-ink, #0c4a6e);
}

.posts-index__empty :deep(p) {
    font-size: 0.9rem;
    line-height: 1.5;
}
</style>
