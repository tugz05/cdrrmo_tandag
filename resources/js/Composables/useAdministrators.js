import { confirmDialog } from "@/Helpers/JConfirmDialog"
import { toggleModal } from "@/Helpers/JModal"
import { useForm } from "@inertiajs/vue3"

export function useAdministrators() {
    const form = useForm({
        id: '',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'admin',
    })

    const create = () => {
        form.reset()
        form.role = 'admin'
        toggleModal('Create staff account')
    }

    const edit = (admin) => {
        form.reset()
        form.id = admin.id
        form.name = admin.name
        form.email = admin.email
        form.role = admin.role ?? 'admin'
        toggleModal('Edit staff account')
    }

    const store = () => {
        form.post(route('administrators.store'), {
            onSuccess: () => {
                form.reset()
                form.role = 'admin'
                toggleModal('', 'default-modal')
            },
        })
    }

    const update = () => {
        form.put(route('administrators.update', form.id), {
            onSuccess: () => {
                form.reset()
                form.role = 'admin'
                toggleModal('', 'default-modal')
            },
        })
    }

    const destroy = (admin) => {
        confirmDialog({
            title: 'Delete staff account?',
            message: `You're going to delete ${admin.name}. Are you sure?`
        }).then((confirmed) => {
            if (confirmed) {
                form.delete(route('administrators.destroy', admin.id))
            }
        }).catch(() => {})
    }

    return {
        form,
        create,
        store,
        update,
        edit,
        destroy
    }

}
