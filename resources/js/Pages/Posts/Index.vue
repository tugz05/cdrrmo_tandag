<script setup>
import JButton from '@/Components/JButton.vue';
import JCard from '@/Components/JCard.vue';
import JModal from '@/Components/JModal.vue';
import JDropdownDivider from '@/Components/JDropdownDivider.vue';
import JDropdownItem from '@/Components/JDropdownItem.vue';
import JDropdownMenu from '@/Components/JDropdownMenu.vue';
import JHeaderTitle from '@/Components/JHeaderTitle.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { usePost } from '@/Composables/usePost';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import { formToJSON } from 'axios';
import JModalButtonClose from '@/Components/JModalButtonClose.vue';
import { timeAgo } from '@/Helpers/JTimeAgo';
defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    news: {
        type: Array,
        default: () => ([])
    },
    post_types: {
        type: Array,
        default: () => ([])
    },
    active_tab: {
        type: String,
        default: null
    }
})

const filterPost = (type = null) => {
    // console.log(type);
    router.get(route('posts.index', type))
}

const { form, create, store, destroy } = usePost()

</script>

<template>

    <Head title="Posts" />
    <JHeaderTitle title="Posts" :breadcrumb-items="[{ title: 'Posts' }]" />

    <div class="d-flex justify-content-between gap-4 align-items-center mb-10">
        <div class="d-flex gap-2">
            <button @click="filterPost()" type="button" class="btn btn-default" :class="{'active' : active_tab == null}"><i class="bi bi-newspaper me-1"></i> News</button>
            <button @click="filterPost('Safety Tips')" type="button" class="btn btn-default" :class="{'active' : active_tab == 'Safety Tips'}"><i class="bi bi-info-circle me-1"></i> Safety Tips</button>
            <button @click="filterPost('Emergency Preparedness')" type="button" class="btn btn-default" :class="{'active' : active_tab == 'Emergency Preparedness'}"><i class="bi bi-person-check me-1"></i> Emergency Preparedness</button>
        </div>
        <JButton primary @click="create()" icon="plus-lg" text="Create"/>
    </div>

    <div class="row g-5">
        <div v-for="newsItem in news" class="col-sm-12 col-md-6 col-lg-4 h-full">
            <JCard class="h-100 shadow-md">
                <div class="d-flex flex-column justify-content-between h-100">
                    <div class="d-flex justify-content-between gap-5">
                        <div>
                            <!-- <span class="fs-sm opacity-50">{{ timeAgo(newsItem.created_at) }}</span> -->
                            <span v-if="newsItem.is_published" class="badge text-bg-primary">Published</span>
                            <span v-else class="badge text-bg-secondary">Unpublished</span>
                        </div>
                        <div class="dropdown">
                            <JButton default outline sm icon="three-dots-vertical" data-bs-toggle="dropdown"/>
                            <JDropdownMenu class="dropdown-menu-end" style="min-width: 7rem">
                                <!-- <JDropdownItem icon="trash3" @click="publish(newsItem.id)" text="Unpublish"/> -->
                                <JDropdownItem icon="trash3" @click="destroy(newsItem)" is-danger text="Delete"/>
                            </JDropdownMenu>
                        </div>
                    </div>
                    <div>
                        <div class="my-5">
                            <h6 class="m-0 fw-bold">{{ newsItem.title }}</h6>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="fs-sm fw-light opacity-75">{{ timeAgo(newsItem.created_at) }}</span>
                            </div>
                            <div class="text-end">
                                <JButton :href="route('posts.edit', newsItem.id)" primary outline sm>
                                    View <i class="bi bi-arrow-right"></i>
                                </JButton>
                            </div>
                        </div>
                    </div>
                </div>
            </JCard>
        </div>
    </div>
    <JModal sm>
        <form @submit.prevent="store()" id="post-form">
            <JFloatingInput label="Title" type="textarea" v-model="form.title" required/>
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
            <JFloatingInput label="Type" type="select" v-model="form.type" required>
                <template #option>
                    <option value="" selected hidden>Select</option>
                    <option value="News">News</option>
                    <option value="Safety Tips">Safety Tips</option>
                    <option value="Emergency Preparedness">Emergency Preparedness</option>
                </template>
            </JFloatingInput>
        </form>
        <template #footerend>
            <JModalButtonClose/>
            <JButton type="submit" primary form="post-form" :processing="form.processing" text="Save"/>
        </template>
    </JModal>    

</template>
