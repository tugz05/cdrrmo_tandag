import { useForm, router } from "@inertiajs/vue3"
import { confirmDialog, toggleModal } from "@/Helpers/JHelper"
import { hideModal } from "@/Helpers/JModal"
import { nextTick, onMounted } from "vue"

export function usePost(post = null) {
    const summernoteId = 'summernote-default-id'

    const form = useForm({
        id: post?.id ?? '',
        is_published: post?.is_published == 1 ? true : false,
        title: post?.title ?? '',
        type: post?.type ?? '',
        content: post?.content ?? '',
        created_at: post?.created_at ?? '',
        is_publishing: false,
        /** @type {File|null} new upload; server path is not stored on the form */
        bg_image: null,
    })

    onMounted(() => {
        nextTick(() => {
            $(`#${summernoteId}`).summernote('code', form.content)
            $(`#${summernoteId}`).summernote({
                placeholder: 'Start typing here.',
                height: '500',
                width: '100%',
            })

            $(`#${summernoteId}`).on('summernote.change', (we, contents) => {
                form.content = contents
            })
        })
    })



    const create = () => {
        form.reset()
        form.bg_image = null
        toggleModal('Create Post')
    }


    const store = () => {
        form.post(route('posts.store'), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                hideModal()
            },
        })
    }

    const editTitle = () => {
        toggleModal('Edit Title')
    }

    const updateTitle = () => {
        router.put(route('posts.update-title'), {
            id: form.id,
            title: form.title,
        }, {
            preserveScroll: true,
            onError: () => form.clearErrors(),
            onSuccess: () => {
                hideModal()
            },
        })
    }

    const edit = (post) => {
        form.id = post.id
        form.is_published = post.is_published
        form.title = post.title ?? ''
        form.content = post.content ?? ''
        form.created_at = post.created_at
    }


    const destroy = (post) => {
        confirmDialog({
            title: 'Delete Post?',
            message: `You're going to delete ${post.title}. Are you sure?`
        }).then((confirmed) => {
            if (confirmed) {
                form.delete(route('posts.destroy', post.id))
            }
        })
    }

    const publish = () => {
        form.put(route('posts.publish', form.id), {
            onStart: () => form.is_publishing = true,
            onError: () => form.is_publishing = false,
            onFinish: () => form.is_publishing = false,
        })
    }

    return {
        summernoteId,
        form,
        create,
        store,
        edit,
        editTitle,
        updateTitle,
        destroy,
        publish,
    }
}