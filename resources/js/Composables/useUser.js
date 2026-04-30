import { router } from "@inertiajs/vue3"
import { ref } from "vue"
import { toggleOffCanvas  } from "@/Helpers/JHelper"

export function useUser() {
    const userId = ref(null)
    const isVerified = ref(false)
    const shouldShowUser = ref(false)
    const userData = ref({})

    const approve = () => {

    }

    const viewUser = async (user) => {
        toggleOffCanvas('offcanvas-users')
        shouldShowUser.value = true
        userId.value = user.id
        isVerified.value = user.confirmed_at != null
        try {
            userData.value = await (await fetch(route('users.show', user.id))).json()
            console.log(userData.value)
        } catch (e) {
            console.error(e)
        } finally {
            
        }
    }

    const verify = () => {
        router.put(route('users.verify', userId.value), {}, {
            preserveScroll: true,
            onSuccess: () => {
                toggleOffCanvas('offcanvas-users')
                isVerified.value = !isVerified.value
            }
        })
    }

    const destroy = (id) => {
        router.destroy(route('users.destroy', id), {}, {
            preserveScroll: true,
            onSuccess: () => {

            }
        })
    }

    return {
        userId,
        isVerified,
        userData,
        shouldShowUser,
        viewUser,
        approve,
        verify,
        destroy,
    }
}