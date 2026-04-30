<script setup> 
import { Link } from '@inertiajs/vue3'

defineProps({
    href: {
        type: String,
        default: null
    },
    text: {
        type: String,
        default: null
    },
    icon: {
        type: String,
        default: null
    },
    form: {
        type: String,
        defualt: null
    },
    type: {
        type: String,
        default: 'button'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    isDanger: {
        type: Boolean,
        default: false
    }
})
</script>
<template>
    <li>
        <Link v-if="href !== null" :href="href" class="dropdown-item" :class="{'disabled': disabled, 'text-danger' : isDanger}" :aria-disabled="disabled">
            <template v-if="icon !== null">
                <i :class="[`bi bi-${icon}`, {'me-2' : text !== null}]"></i>
            </template>
            <span v-html="text"></span>
            <slot/>
        </Link>
        <button v-else :type="type" :form="form" class="dropdown-item " :class="{'disabled': disabled, 'text-danger' : isDanger}" :aria-disabled="disabled">
            <template v-if="icon !== null">
                <i :class="[`bi bi-${icon}`, {'me-2' : text !== null}]"></i>
            </template>
            <span v-html="text"></span>
            <slot/>
        </button>
    </li>
</template>