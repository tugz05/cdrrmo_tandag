<script setup>
import { usePage, Link } from '@inertiajs/vue3';
import { watch, ref, onMounted } from 'vue';
import { showToast } from '@/Helpers/JHelper';


const page = usePage()
const toastMessage = ref('')

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

watch(() => page.props.flash.success, (message) => {
    setToastMessage(JToast.SUCCESS, message)
    page.props.flash.success = null    
    // resetRoute()
})

watch(() => page.props.flash.warning, (message) => {
    setToastMessage(JToast.WARNING, message)
    page.props.flash.warning = null
    console.log('executed')

    // resetRoute()
})

watch(() => page.props.flash.danger, (message) => {
    setToastMessage(JToast.DANGER, message)
    page.props.flash.danger = null
})


watch(() => page.props.flash.restore, (routeMessage) => {
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