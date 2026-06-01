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

/**
 * Tune object-fit / position from intrinsic pixels so low-res files are not upscaled as blurry covers,
 * and very wide or tall assets avoid awkward crops in the fixed frame.
 */
function onCoverImgLoad(event) {
    const img = event.target;
    if (!(img instanceof HTMLImageElement)) {
        return;
    }
    const media = img.closest('.posts-index__media');
    if (!media) {
        return;
    }
    const nw = img.naturalWidth;
    const nh = img.naturalHeight;
    if (nw < 2 || nh < 2) {
        return;
    }
    const cw = media.clientWidth;
    const ch = media.clientHeight;
    const dpr = typeof window !== 'undefined' ? window.devicePixelRatio || 1 : 1;
    const needW = cw * dpr;
    const needH = ch * dpr;
    /** Source smaller than frame (in device pixels): show full image with letterboxing instead of upscaled cover. */
    if (nw < needW * 0.9 || nh < needH * 0.9) {
        img.classList.add('posts-index__img--contain');
    }
    if (img.classList.contains('posts-index__img--contain')) {
        return;
    }
    const r = nw / nh;
    if (r > 1.75) {
        img.style.objectPosition = 'center 22%';
    } else if (r < 0.72) {
        img.style.objectPosition = 'center top';
    } else {
        img.style.objectPosition = 'center center';
    }
}

const emptyHeadline = computed(() => {
    if (props.active_tab === 'Safety Tips') {
        return 'No safety tips yet';
    }
    if (props.active_tab === 'Emergency Preparedness') {
        return 'No preparedness content yet';
    }
    return 'No news posts yet';
});

const emptyMessage = computed(() => {
    if (props.active_tab === 'Safety Tips') {
        return 'Create a safety tip to publish guidance for residents. Posts need a featured image before they can appear in the public API.';
    }
    if (props.active_tab === 'Emergency Preparedness') {
        return 'Add articles on kits, household plans, and evacuation so the community stays informed.';
    }
    return 'Publish advisories and stories to keep the public informed. Add a featured image so posts can go live externally.';
});
</script>

<template>
    <Head title="Posts" />

    <div class="posts-index">
        <div class="posts-index__inner">
            <JHeaderTitle compact title="Posts" :breadcrumb-items="[{ title: 'Posts' }]" />

            <section class="posts-index__panel card border-0 overflow-hidden" aria-labelledby="posts-library-heading">
                <div class="posts-index__panel-head">
                    <div class="row g-3 g-lg-4 align-items-center">
                        <div class="col-xl-8">
                            <p class="posts-index__eyebrow text-uppercase mb-1">Content library</p>
                            <h2 id="posts-library-heading" class="posts-index__title h4 fw-bold text-body mb-1">
                                News, safety tips &amp; preparedness
                            </h2>
                            <p class="posts-index__lede text-body-secondary mb-0">
                                Curate public-facing content by category. Only posts with a featured image can go live in
                                the public API.
                            </p>
                        </div>
                        <div class="col-xl-4 text-xl-end">
                            <JButton primary icon="plus-lg" text="Create post" class="posts-index__create" @click="create()" />
                        </div>
                    </div>
                </div>

                <div class="posts-index__panel-divider" role="presentation" />

                <nav class="posts-index__panel-nav px-3 px-md-4 py-2" aria-label="Post categories">
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
                        <p class="posts-index__context small text-body-secondary mb-0 flex-shrink-0 d-none d-lg-block">
                            Showing
                            <strong class="text-body">{{ activeFilterMeta.label }}</strong>
                            <span class="text-body-secondary"> — </span>
                            {{ activeFilterMeta.description }}
                        </p>
                    </div>
                </nav>
            </section>

            <div class="posts-index__body">
                <div v-if="!news.length" class="posts-index__empty-well">
                    <div class="posts-index__empty-card">
                        <div class="posts-index__empty-icon" aria-hidden="true">
                            <i class="bi bi-journal-richtext" />
                        </div>
                        <h3 class="posts-index__empty-title h5 fw-bold text-body mb-2">
                            {{ emptyHeadline }}
                        </h3>
                        <p class="posts-index__empty-text text-body-secondary mb-4">
                            {{ emptyMessage }}
                        </p>
                        <JButton primary icon="plus-lg" text="Create your first post" @click="create()" />
                    </div>
                </div>

                <div v-else class="posts-index__grid" role="list">
                    <article
                        v-for="newsItem in news"
                        :key="newsItem.id"
                        class="posts-index__grid-item card posts-index__card border-0 overflow-hidden"
                        role="listitem"
                    >
                            <div class="posts-index__media">
                                <template v-if="coverUrl(newsItem)">
                                    <div class="posts-index__media-frame">
                                        <img
                                            :src="coverUrl(newsItem)"
                                            class="posts-index__img"
                                            alt=""
                                            loading="lazy"
                                            decoding="async"
                                            sizes="(max-width: 576px) 100vw, 420px"
                                            @load="onCoverImgLoad"
                                        />
                                    </div>
                                </template>
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
                                            class="posts-index__kebab border-0 bg-body"
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
                            <div class="card-body d-flex flex-column pt-3 pb-3 px-3 px-sm-4">
                                <h3 class="posts-index__card-title h6 fw-bold mb-2">
                                    {{ newsItem.title }}
                                </h3>
                                <div class="mt-auto d-flex align-items-center justify-content-between gap-2 pt-2 border-top border-opacity-25">
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
    </div>

    <JModal sm>
        <form id="post-form" method="post" :action="route('posts.store')" @submit.prevent="store()">
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
    flex: 1 1 auto;
    min-height: 0;
    width: 100%;
    display: flex;
    flex-direction: column;
}

.posts-index__inner {
    width: 100%;
    max-width: 1680px;
    margin-inline: auto;
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    gap: 0.875rem;
}

.posts-index__panel {
    border-radius: 0.875rem;
    background: var(--cdrrmo-surface-raised, #fff);
    box-shadow:
        0 1px 0 rgba(255, 255, 255, 0.7) inset,
        0 4px 28px rgba(8, 47, 73, 0.08),
        0 0 0 1px color-mix(in srgb, var(--cdrrmo-600, #0284c7) 8%, transparent);
}

.posts-index__panel-head {
    padding: 1.125rem 1.25rem 1rem;
}

@media (min-width: 992px) {
    .posts-index__panel-head {
        padding: 1.35rem 1.75rem 1.15rem;
    }
}

.posts-index__eyebrow {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    color: var(--cdrrmo-primary, #0284c7);
}

.posts-index__title {
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.posts-index__lede {
    font-size: 0.9rem;
    line-height: 1.55;
    max-width: 42rem;
}

.posts-index__panel-divider {
    height: 1px;
    background: linear-gradient(
        90deg,
        transparent,
        color-mix(in srgb, var(--cdrrmo-600, #0284c7) 14%, transparent) 12%,
        color-mix(in srgb, var(--cdrrmo-600, #0284c7) 14%, transparent) 88%,
        transparent
    );
}

.posts-index__panel-nav {
    background: color-mix(in srgb, var(--cdrrmo-50, #f0f9ff) 55%, #fff);
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
    background: #fff;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 14%, transparent);
    padding-inline: 1rem;
    white-space: nowrap;
}

.posts-index__tab:hover {
    color: var(--cdrrmo-ink, #0c4a6e);
    background: var(--cdrrmo-50, #f0f9ff);
}

.posts-index__tab--active {
    color: #fff !important;
    background: var(--cdrrmo-primary, #0284c7) !important;
    border-color: var(--cdrrmo-primary, #0284c7) !important;
    box-shadow: 0 2px 12px color-mix(in srgb, var(--cdrrmo-primary, #0284c7) 38%, transparent);
}

.posts-index__context {
    max-width: 22rem;
    line-height: 1.45;
}

.posts-index__body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.posts-index__grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1.15rem;
    align-items: stretch;
    margin: 0;
    padding: 0;
}

.posts-index__grid-item {
    flex: 0 1 26rem;
    width: min(26rem, 100%);
    max-width: 100%;
    min-width: 0;
}

.posts-index__empty-well {
    flex: 1 1 auto;
    min-height: min(22rem, 52vh);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem 2.5rem;
    border-radius: 0.875rem;
    background: linear-gradient(165deg, #fff 0%, color-mix(in srgb, var(--cdrrmo-100, #e0f2fe) 45%, #fff) 100%);
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 10%, transparent);
    box-shadow: 0 4px 24px rgba(8, 47, 73, 0.06);
}

.posts-index__empty-card {
    text-align: center;
    max-width: 26rem;
    padding: 0.5rem 1rem 1rem;
}

.posts-index__empty-icon {
    width: 4rem;
    height: 4rem;
    margin: 0 auto 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 1rem;
    background: linear-gradient(145deg, var(--cdrrmo-100, #e0f2fe), #fff);
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 12%, transparent);
    color: var(--cdrrmo-primary, #0284c7);
    font-size: 1.65rem;
    box-shadow: 0 2px 10px rgba(8, 47, 73, 0.06);
}

.posts-index__empty-title {
    letter-spacing: -0.02em;
}

.posts-index__empty-text {
    font-size: 0.9rem;
    line-height: 1.55;
}

.posts-index__card {
    border-radius: 0.75rem;
    background: var(--cdrrmo-surface-raised, #fff);
    box-shadow:
        0 2px 8px rgba(8, 47, 73, 0.06),
        0 0 0 1px color-mix(in srgb, var(--cdrrmo-600, #0284c7) 6%, transparent);
    transition: box-shadow 0.2s ease, transform 0.2s ease;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.posts-index__card:hover {
    box-shadow:
        0 12px 32px rgba(8, 47, 73, 0.1),
        0 0 0 1px color-mix(in srgb, var(--cdrrmo-600, #0284c7) 10%, transparent) !important;
    transform: translateY(-3px);
}

.posts-index__media {
    position: relative;
    flex-shrink: 0;
    width: 100%;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    isolation: isolate;
    background: linear-gradient(135deg, var(--cdrrmo-100, #e0f2fe) 0%, var(--cdrrmo-200, #bae6fd) 100%);
}
.posts-index__media-frame {
    position: absolute;
    inset: 0;
    overflow: hidden;
    z-index: 0;
}

.posts-index__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    margin: 0;
    object-fit: cover;
    object-position: center center;
    display: block;
    backface-visibility: hidden;
    transform: translateZ(0);
}

.posts-index__img--contain {
    object-fit: contain;
    object-position: center center;
    padding: 0.45rem;
    background: linear-gradient(180deg, var(--cdrrmo-50, #f0f9ff), var(--cdrrmo-100, #e0f2fe));
}

.posts-index__placeholder {
    position: absolute;
    inset: 0;
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
    z-index: 1;
    background: linear-gradient(to top, rgba(8, 47, 73, 0.38) 0%, transparent 52%);
}

.posts-index__media-top {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    right: 0.75rem;
    z-index: 2;
}

.posts-index__status {
    position: absolute;
    bottom: 0.75rem;
    left: 0.75rem;
    z-index: 2;
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
    box-shadow: 0 1px 4px rgba(8, 47, 73, 0.08);
}

.posts-index__card .card-body {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

.posts-index__card-title {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.45;
    color: var(--cdrrmo-ink, #0c4a6e);
}
</style>
