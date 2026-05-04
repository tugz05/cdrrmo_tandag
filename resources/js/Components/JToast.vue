<script setup>
import { usePage, Link } from '@inertiajs/vue3';
import { watch, ref, onMounted } from 'vue';
import { showToast } from '@/Helpers/JHelper';


const page = usePage()
const toastMessage = ref('')

/** Inertia shared `flash` may be absent during edge navigations — avoid throwing in watchers. */
function flashProp(key) {
    return page.props.flash?.[key]
}

const JToast = {
    SUCCESS: 'success',
    WARNING: 'warning',
    DANGER: 'danger',
}

const restoreRoute = ref('')
const restoreRouteParam = ref('')
const withRestore = ref(false)

onMounted(() => {
    resetRoute()
})

watch(() => flashProp('success'), (message) => {
    setToastMessage(JToast.SUCCESS, message)
    if (page.props.flash) {
        page.props.flash.success = null
    }
    // resetRoute()
})

watch(() => flashProp('warning'), (message) => {
    setToastMessage(JToast.WARNING, message)
    if (page.props.flash) {
        page.props.flash.warning = null
    }

    // resetRoute()
})

watch(() => flashProp('danger'), (message) => {
    setToastMessage(JToast.DANGER, message)
    if (page.props.flash) {
        page.props.flash.danger = null
    }
})


watch(() => flashProp('restore'), (routeMessage) => {
    if (routeMessage === null)
        return
    
    try {
        const routeArr = routeMessage.split(' ')

        restoreRoute.value = routeArr[0]
        restoreRouteParam.value = routeArr[1]

        withRestore.value = restoreRoute.value !== '' && restoreRouteParam.value !== ''
    } catch (e) {
        console.log(e)
    }

})

const resetRoute = () => {
    restoreRoute.value = ''
    restoreRouteParam.value = ''
    withRestore.value = false
}



let setToastMessage = (type, message) => {
    if (message !== null) {
        toastMessage.value = message
        showToast(true, toastMessage.value, type)
    }
}
</script>


<template>
    <Teleport to="body">
        <div class="toast-container position-fixed top-0 end-0 p-7">
            <div id="bootstrapToast1" 
                class="toast" 
                role="alert" 
                aria-live="assertive"
                aria-atomic="true"
            >
                <div class="d-flex align-items-center">
                    <div class="ps-4">
                        <i class="toast-icon bi"></i>
                    </div>
                    <div class="toast-body">
                        <small class="toast-body-text fw-semibold pe-2"></small>
                        <span v-if="withRestore" class="toast-body-undo">
                            <Link :href="route(restoreRoute, restoreRouteParam)" 
                                :preserve-scroll="true"
                                method="post" 
                                class="btn btn-sm btn-link link-primary fw-bold p-0" 
                                as="button">
                                UNDO
                            </Link>
                        </span>
                    </div>
                    <button type="button" 
                        class="btn-close me-2 m-auto" 
                        data-bs-dismiss="toast" 
                        aria-label="Close">
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>