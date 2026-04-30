<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
const props = defineProps({
    type: {
        type: String,
        default: 'button'
    },
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
    link: {
        type: Boolean,
        default: false
    },
    primary: {
        type: Boolean,
        default: false
    },
    secondary: {
        type: Boolean,
        default: false
    },
    danger: {
        type: Boolean,
        default: false
    },
    success: {
        type: Boolean,
        default: false
    },
    warning: {
        type: Boolean,
        default: false
    },
    default: {
        type: Boolean,
        default: false
    },
    subtle: {
        type: Boolean,
        default: false
    },
    light: {
        type: Boolean,
        default: false
    },
    outline: {
        type:Boolean,
        default: false
    },
    sm: {
        type: Boolean,
        default: false
    },
    lg: {
        type: Boolean,
        default: false
    },
    processing: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    processingText: {
        type: String,
        default: null
    },
    iconEnd: {
        type: Boolean,
        default: false
    }
})

const buttonClasses = computed(() => ({
    'btn-subtle' : props.subtle,
    'btn-light' : props.light,
    
    'btn-primary' : props.primary && (!props.outline && !props.link),
    'btn-secondary' : props.secondary && (!props.outline && !props.link),
    'btn-success' : props.success && (!props.outline && !props.link),
    'btn-warning' : props.warning && (!props.outline && !props.link),
    'btn-danger' : props.danger && (!props.outline && !props.link),
    'btn-default' : props.default && (!props.outline && !props.link),

    'btn-outline-primary' : props.primary && props.outline,
    'btn-outline-secondary' : props.secondary && props.outline,
    'btn-outline-success' : props.success && props.outline,
    'btn-outline-warning' : props.warning && props.outline,
    'btn-outline-danger' : props.danger && props.outline,
    'btn-outline-default': props.default && props.outline,
    
    'btn-link text-decoration-none' : props.link,
    'link-primary' : props.primary && props.link,
    'link-secondary' : props.secondary && props.link,
    'link-success' : props.success && props.link,
    'link-warning' : props.warning && props.link,
    'link-danger' : props.danger && props.link,

    'disabled': props.disabled,
    'btn-sm' : props.sm,
    'btn-lg' : props.lg,
}))  
</script>

<template>
    <Link v-if="href" :href="href" class="btn" :class="buttonClasses">
        <i v-if="icon && !iconEnd" :class="[`bi bi-${icon}`, {'pe-2' : text}]"></i>
        {{ text }}        
        <slot></slot>
        <i v-if="icon && iconEnd" :class="[`bi bi-${icon}`, {'ps-2' : text}]"></i>
    </Link>

    <button v-else :type="type" class="btn" :class="buttonClasses" :disabled="processing || disabled">
        <template v-if="processing">
            <span class="spinner-border spinner-border-sm" :class="{'me-1' : processingText}" aria-hidden="true"></span>
            {{ processingText }}
        </template>
        <template v-else>
            <i v-if="icon && !iconEnd" :class="[`bi bi-${icon}`, {'pe-2' : text}]"></i>
            <span v-if="text" v-html="text"></span>
            <i v-if="icon && iconEnd" :class="[`bi bi-${icon}`, {'ps-2' : text}]"></i>
        </template>
        <slot></slot>
    </button>
</template>