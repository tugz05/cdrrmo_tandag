<script setup>
import JButton from '@/Components/JButton.vue';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import JModal from '@/Components/JModal.vue';
import JModalButtonClose from '@/Components/JModalButtonClose.vue';
import { timeAgo } from '@/Helpers/JTimeAgo';
import { computed, ref, watch } from 'vue';

/** Must match id used in `usePost` composable for Summernote init */
const SUMMERNOTE_ID = 'summernote-default-id';

const props = defineProps({
    form: {
        type: Object,
        default: () => ({}),
    },
    postTypes: {
        type: Array,
        default: () => [],
    },
    /** Raw `bg_image` from the server (path or URL) for preview when no new file is selected */
    existingBgImage: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['onSubmit', 'editTitle', 'updateTitle', 'publish']);

const fileInputRef = ref(null);
const fileObjectUrl = ref(null);
const isDragActive = ref(false);
let dragDepth = 0;

function publicImageUrl(path) {
    if (!path) {
        return null;
    }
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }
    return path.startsWith('/') ? path : `/${path}`;
}

const remotePreview = computed(() => publicImageUrl(props.existingBgImage));

watch(
    () => props.form.bg_image,
    (file) => {
        if (fileObjectUrl.value) {
            URL.revokeObjectURL(fileObjectUrl.value);
            fileObjectUrl.value = null;
        }
        if (file instanceof File) {
            fileObjectUrl.value = URL.createObjectURL(file);
        }
    }
);

const featuredPreviewSrc = computed(() => fileObjectUrl.value || remotePreview.value);

const hasPreview = computed(() => Boolean(featuredPreviewSrc.value));

const hasPendingUpload = computed(() => props.form.bg_image instanceof File);

function openFeaturedFilePicker() {
    fileInputRef.value?.click();
}

function onFeaturedDragEnter(e) {
    e.preventDefault();
    dragDepth += 1;
    isDragActive.value = true;
}

function onFeaturedDragLeave(e) {
    e.preventDefault();
    dragDepth = Math.max(0, dragDepth - 1);
    if (dragDepth === 0) {
        isDragActive.value = false;
    }
}

function onFeaturedDragOver(e) {
    e.preventDefault();
    if (e.dataTransfer) {
        e.dataTransfer.dropEffect = 'copy';
    }
}

function onFeaturedDrop(e) {
    e.preventDefault();
    dragDepth = 0;
    isDragActive.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file && /^image\/(jpeg|png|webp|jpg)$/i.test(file.type)) {
        props.form.bg_image = file;
    }
}

function onFeaturedFileChange(event) {
    const file = event.target.files?.[0] ?? null;
    props.form.bg_image = file;
}

function discardPendingFeaturedUpload() {
    if (fileObjectUrl.value) {
        URL.revokeObjectURL(fileObjectUrl.value);
        fileObjectUrl.value = null;
    }
    props.form.bg_image = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function handlePublish() {
    emit('publish');
}

function handleEditTitle(e) {
    e.preventDefault();
    emit('editTitle');
}
</script>

<template>
    <div class="post-edit">
        <form class="post-edit__form" @submit.prevent="emit('onSubmit')">
            <input v-model="form.id" type="hidden" name="post_id" />

            <div class="row g-4 justify-content-center">
                <div class="col-12 col-xl-8">
                    <div class="post-edit__card">
                        <div class="post-edit__meta">
                            <div class="post-edit__meta-line">
                                <span class="text-muted small">
                                    {{ timeAgo(form.created_at) }}
                                    <i class="bi bi-dot mx-1" aria-hidden="true" />
                                </span>
                                <span class="post-edit__type-pill"># {{ form.type || 'Post' }}</span>
                            </div>
                        </div>

                        <div class="post-edit__title-block">
                            <h1 class="post-edit__title">
                                {{ form.title }}
                            </h1>
                            <div class="post-edit__title-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm rounded-pill px-3"
                                    @click="handleEditTitle"
                                >
                                    <i class="bi bi-pencil-square me-1" aria-hidden="true" />
                                    Edit title
                                </button>
                            </div>
                        </div>

                        <section class="post-edit__featured-panel" aria-labelledby="featured-image-heading">
                            <div class="post-edit__featured-head">
                                <div class="post-edit__featured-head-main">
                                    <span class="post-edit__featured-icon" aria-hidden="true">
                                        <i class="bi bi-image" />
                                    </span>
                                    <div>
                                        <h2 id="featured-image-heading" class="post-edit__featured-title">
                                            Featured image
                                        </h2>
                                        <p class="post-edit__featured-lede">
                                            Card cover for the mobile app and public listings. Required before you can
                                            publish.
                                        </p>
                                    </div>
                                </div>
                                <span class="post-edit__featured-badge">Required to publish</span>
                            </div>

                            <input
                                ref="fileInputRef"
                                type="file"
                                class="visually-hidden"
                                accept="image/jpeg,image/png,image/webp,image/jpg"
                                tabindex="-1"
                                @change="onFeaturedFileChange"
                            />

                            <div
                                class="post-edit__featured-dropzone"
                                :class="{
                                    'post-edit__featured-dropzone--drag': isDragActive,
                                    'post-edit__featured-dropzone--filled': hasPreview,
                                }"
                                role="button"
                                tabindex="0"
                                @click="openFeaturedFilePicker"
                                @keydown.enter.prevent="openFeaturedFilePicker"
                                @keydown.space.prevent="openFeaturedFilePicker"
                                @dragenter="onFeaturedDragEnter"
                                @dragleave="onFeaturedDragLeave"
                                @dragover="onFeaturedDragOver"
                                @drop="onFeaturedDrop"
                            >
                                <div class="post-edit__featured-aspect">
                                    <template v-if="hasPreview">
                                        <img
                                            :src="featuredPreviewSrc"
                                            alt=""
                                            class="post-edit__featured-img"
                                        />
                                        <div class="post-edit__featured-img-overlay">
                                            <span class="post-edit__featured-overlay-inner">
                                                <i class="bi bi-arrow-repeat me-2" aria-hidden="true" />
                                                Replace image
                                            </span>
                                        </div>
                                    </template>
                                    <div v-else class="post-edit__featured-empty">
                                        <i class="bi bi-cloud-arrow-up post-edit__featured-empty-icon" aria-hidden="true" />
                                        <p class="post-edit__featured-empty-title">Drag &amp; drop an image here</p>
                                        <p class="post-edit__featured-empty-sub">or click to browse from your device</p>
                                    </div>
                                </div>
                            </div>

                            <div class="post-edit__featured-toolbar">
                                <div class="post-edit__featured-actions">
                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm px-3"
                                        @click.stop="openFeaturedFilePicker"
                                    >
                                        <i class="bi bi-upload me-1" aria-hidden="true" />
                                        {{ hasPreview ? 'Replace image' : 'Upload image' }}
                                    </button>
                                    <button
                                        v-if="hasPendingUpload"
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        @click.stop="discardPendingFeaturedUpload"
                                    >
                                        Discard new upload
                                    </button>
                                </div>
                                <p class="post-edit__featured-specs mb-0">
                                    JPEG, PNG, or WebP · max 5&nbsp;MB · recommended <strong>1200 × 630</strong> px (social
                                    card ratio)
                                </p>
                            </div>

                            <p v-if="form.errors?.bg_image" class="post-edit__featured-error text-danger small mb-0 mt-2">
                                <i class="bi bi-exclamation-circle me-1" aria-hidden="true" />
                                {{ form.errors.bg_image }}
                            </p>
                        </section>

                        <p class="post-edit__hint small text-muted mb-3">
                            Use the toolbar below for headings, lists, links, and images. Save when you are ready—your
                            session stays on this page.
                        </p>

                        <div class="post-edit__editor-wrap">
                            <textarea :id="SUMMERNOTE_ID" class="post-edit__summernote-target" />
                        </div>

                        <div class="post-edit__save-bar">
                            <JButton
                                primary
                                lg
                                type="submit"
                                class="post-edit__save-btn"
                                icon="check-lg"
                                :processing="form.processing && !form.is_publishing"
                                processing-text="Saving…"
                                text="Save changes"
                            />
                            <p class="post-edit__save-note small text-muted mb-0 mt-2">
                                Saves body content. Publication status is controlled separately (sidebar).
                            </p>
                        </div>
                    </div>
                </div>

                <aside class="col-12 col-xl-4">
                    <div class="post-edit__aside card border-0 shadow-sm">
                        <div class="card-body">
                            <h2 class="h6 fw-bold text-uppercase letter-spacing mb-2">Publication</h2>
                            <p class="small text-muted mb-3">
                                Draft posts stay in the admin area only. Published posts appear on public listings for this
                                type (news, safety tips, or preparedness).
                            </p>

                            <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                                <span class="small text-muted">Status:</span>
                                <span
                                    class="badge rounded-pill px-3 py-2"
                                    :class="form.is_published ? 'text-bg-primary' : 'text-bg-secondary'"
                                >
                                    {{ form.is_published ? 'Published' : 'Draft' }}
                                </span>
                            </div>

                            <p v-if="form.errors?.bg_image" class="small text-danger mb-3">
                                {{ form.errors.bg_image }}
                            </p>

                            <JButton
                                v-if="!form.is_published"
                                type="button"
                                primary
                                class="w-100 py-2 fw-bold"
                                icon="globe2"
                                :disabled="form.is_publishing"
                                :processing="form.is_publishing"
                                processing-text="Updating…"
                                text="Publish to public site"
                                @click="handlePublish"
                            />
                            <JButton
                                v-else
                                type="button"
                                warning
                                outline
                                class="w-100 py-2 fw-bold"
                                icon="eye-slash"
                                :disabled="form.is_publishing"
                                :processing="form.is_publishing"
                                processing-text="Updating…"
                                text="Unpublish (set to draft)"
                                @click="handlePublish"
                            />
                            <p class="small text-muted mt-2 mb-0">
                                {{
                                    form.is_published
                                        ? 'Visitors who browse your public posts will no longer see this item.'
                                        : 'Turn on when the content is ready for residents to read.'
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="post-edit__aside card border-0 shadow-sm mt-3">
                        <div class="card-body">
                            <h2 class="h6 fw-bold text-uppercase letter-spacing mb-2">Post type</h2>
                            <p class="small text-muted mb-0">
                                <strong>{{ form.type }}</strong>
                            </p>
                            <p class="small text-muted mt-2 mb-0">
                                Type is set when the post is created. To use another category, create a new post or add a
                                type-change action in Posts if your team enables it.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </form>

        <JModal sm>
            <template #header>
                <h5 :id="`modal-default-modal`" class="modal-title fw-semibold">Edit title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" />
            </template>
            <JFloatingInput
                label="Title"
                type="textarea"
                height="120px"
                v-model="form.title"
                required
                :error="form.errors?.title"
            />
            <template #footerend>
                <JModalButtonClose />
                <JButton
                    primary
                    type="button"
                    text="Save title"
                    :processing="form.processing"
                    @click="emit('updateTitle')"
                />
            </template>
        </JModal>
    </div>
</template>

<style scoped>
.post-edit {
    max-width: 1400px;
    margin: 0 auto;
}

.post-edit__card {
    background: var(--cdrrmo-surface-raised, #fff);
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 12%, transparent);
    border-radius: 1rem;
    padding: 1.5rem 1.5rem 1.75rem;
    box-shadow: 0 12px 40px color-mix(in srgb, var(--cdrrmo-800, #075985) 8%, transparent);
}

@media (min-width: 992px) {
    .post-edit__card {
        padding: 2rem 2.25rem 2rem;
    }
}

.post-edit__meta-line {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
}

.post-edit__type-pill {
    display: inline-flex;
    align-items: center;
    padding: 0.2rem 0.65rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--cdrrmo-800, #075985);
    background: color-mix(in srgb, var(--cdrrmo-100, #e0f2fe) 90%, white);
    border: 1px solid color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 28%, transparent);
}

.post-edit__title-block {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 10%, transparent);
}

.post-edit__title {
    font-size: clamp(1.35rem, 2.5vw, 1.85rem);
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--cdrrmo-900, #0c4a6e);
    margin: 0;
    flex: 1 1 12rem;
    line-height: 1.25;
}

.post-edit__title-actions {
    flex-shrink: 0;
}

/* --- Featured image (card / dropzone) --- */
.post-edit__featured-panel {
    margin-bottom: 1.5rem;
    padding: 1.25rem 1.35rem;
    border-radius: 0.85rem;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 14%, transparent);
    background: linear-gradient(
        165deg,
        color-mix(in srgb, var(--cdrrmo-50, #f0f9ff) 88%, #fff) 0%,
        #fff 55%
    );
}

.post-edit__featured-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    margin-bottom: 1rem;
}

.post-edit__featured-head-main {
    display: flex;
    gap: 0.85rem;
    align-items: flex-start;
    min-width: 0;
    flex: 1 1 14rem;
}

.post-edit__featured-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.65rem;
    flex-shrink: 0;
    color: var(--cdrrmo-700, #0369a1);
    background: color-mix(in srgb, var(--cdrrmo-100, #e0f2fe) 95%, white);
    border: 1px solid color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 25%, transparent);
    font-size: 1.25rem;
}

.post-edit__featured-title {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--cdrrmo-900, #0c4a6e);
    margin: 0 0 0.25rem;
}

.post-edit__featured-lede {
    font-size: 0.8125rem;
    line-height: 1.45;
    color: var(--cdrrmo-ink-muted, #64748b);
    margin: 0;
    max-width: 44rem;
}

.post-edit__featured-badge {
    flex-shrink: 0;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    color: var(--cdrrmo-800, #075985);
    background: color-mix(in srgb, var(--cdrrmo-100, #e0f2fe) 90%, white);
    border: 1px solid color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 30%, transparent);
}

.post-edit__featured-dropzone {
    position: relative;
    border-radius: 0.65rem;
    outline: none;
    cursor: pointer;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease,
        background 0.15s ease;
    border: 2px dashed color-mix(in srgb, var(--cdrrmo-500, #0ea5e9) 35%, transparent);
    background: #fff;
}

.post-edit__featured-dropzone:hover,
.post-edit__featured-dropzone:focus-visible {
    border-color: color-mix(in srgb, var(--cdrrmo-600, #0284c7) 55%, transparent);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 18%, transparent);
}

.post-edit__featured-dropzone:focus-visible {
    outline: 2px solid var(--cdrrmo-500, #0ea5e9);
    outline-offset: 2px;
}

.post-edit__featured-dropzone--drag {
    border-style: solid;
    border-color: var(--cdrrmo-500, #0ea5e9);
    background: color-mix(in srgb, var(--cdrrmo-50, #f0f9ff) 96%, #fff);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 22%, transparent);
}

.post-edit__featured-dropzone--filled {
    border-style: solid;
    border-color: color-mix(in srgb, var(--cdrrmo-600, #0284c7) 22%, transparent);
}

.post-edit__featured-aspect {
    position: relative;
    width: 100%;
    aspect-ratio: 1200 / 630;
    max-height: min(52vh, 360px);
    border-radius: calc(0.65rem - 2px);
    overflow: hidden;
    background: color-mix(in srgb, var(--cdrrmo-100, #e0f2fe) 35%, #f8fafc);
}

.post-edit__featured-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.post-edit__featured-img-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(to top, rgb(12 74 110 / 0.55), rgb(12 74 110 / 0.1));
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}

.post-edit__featured-dropzone:hover .post-edit__featured-img-overlay,
.post-edit__featured-dropzone:focus-visible .post-edit__featured-img-overlay {
    opacity: 1;
}

.post-edit__featured-overlay-inner {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 600;
    color: #fff;
    background: rgb(12 74 110 / 0.85);
    backdrop-filter: blur(6px);
}

.post-edit__featured-empty {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 1.5rem;
}

.post-edit__featured-empty-icon {
    font-size: 2.25rem;
    color: color-mix(in srgb, var(--cdrrmo-500, #0ea5e9) 65%, #94a3b8);
    margin-bottom: 0.75rem;
}

.post-edit__featured-empty-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--cdrrmo-900, #0c4a6e);
    margin: 0 0 0.25rem;
}

.post-edit__featured-empty-sub {
    font-size: 0.8125rem;
    color: var(--cdrrmo-ink-muted, #64748b);
    margin: 0;
}

.post-edit__featured-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem 1rem;
    margin-top: 0.9rem;
}

.post-edit__featured-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

.post-edit__featured-specs {
    flex: 1 1 12rem;
    font-size: 0.75rem;
    line-height: 1.4;
    color: var(--cdrrmo-ink-muted, #64748b);
    text-align: right;
}

@media (max-width: 576px) {
    .post-edit__featured-specs {
        text-align: left;
    }
}

.post-edit__featured-error {
    display: flex;
    align-items: flex-start;
    gap: 0.25rem;
}

.post-edit__editor-wrap {
    border-radius: 0.65rem;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 14%, transparent);
    overflow: hidden;
    background: #fff;
}

.post-edit__editor-wrap :deep(.note-editor) {
    border: none;
}

.post-edit__editor-wrap :deep(.note-editable) {
    min-height: 380px;
}

.post-edit__summernote-target {
    width: 100%;
    min-height: 420px;
}

.post-edit__save-bar {
    margin-top: 1.5rem;
    padding-top: 1.25rem;
    border-top: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 10%, transparent);
}

.post-edit__save-btn {
    width: 100%;
    max-width: 24rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.post-edit__aside {
    border-radius: 0.85rem !important;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 10%, transparent) !important;
}

.post-edit__aside .letter-spacing {
    letter-spacing: 0.06em;
    font-size: 0.72rem;
    color: var(--cdrrmo-ink-muted, #64748b);
}
</style>

<style>
@import url('../../../../../public/vendor/summernote-0.8.18-dist/summernote-lite.min.css');
@import url('../../../../../public/custom-summernote.css');
</style>
