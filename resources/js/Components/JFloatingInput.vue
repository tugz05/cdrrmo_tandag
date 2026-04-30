<script setup>
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    label: {
        type: String,
        default: 'Input Label'
    },
    type: {
        type: String,
        default: 'text'
    },
    modelValue: {
        default: '',
        validator: (v) => v == null || typeof v === 'string',
    },
    required: {
        type: Boolean,
        default: false
    },
    textarea: {
        type: Boolean,
        default: false
    },
    // isInvalid: String,
    isInvalid: {
        type: Function,
        default: null
    },
    disabled: {
        type: Boolean,
        default: false
    },
    disableErrorLabel: {
        type: Boolean,
        default: false
    },
    caption: {
        type: String,
        default: ''
    },
    height: {
        type: String,
        default: () => ('100px')
    },
    min: String,
    max: String,
    error: String
})

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

watch(() => props.isInvalid, (value) => {
    if (typeof value === 'function') {
        const errorMessage = value();
        if (errorMessage) {
            // console.log('true', errorMessage);
        } else {
            // console.log('false');
        } 
    } else {
        if (value) {
            // console.log('true', value);
        } else {
            // console.log('false');=> {
        } 
    }
})

defineExpose({ focus: () => input.value.focus() });

// const isInvalidInput = () => typeof props.isInvalid === 'function' && props.isInvalid() !== '' ? 'is-invalid' : ''
const isInvalidInput = () => {
    if (props.error)
        return 'is-invalid' 
    return ''
}

const inputId = getLabelName => {
    const removeWhiteSpace = getLabelName.replace(' ', '_')
    return removeWhiteSpace.toLowerCase()
}

</script>
<template>
    <div class="form-floating mb-3 position-relative">
        <template v-if="type === 'textarea'">
            <textarea
                :class="`form-control ${ isInvalidInput() }`" 
                :id="inputId(label)" 
                :placeholder="label" 
                :value="modelValue ?? ''"
                @input="$emit('update:modelValue', $event.target.value)"
                :required="required"
                :disabled="disabled"
                :style="`height:${height}`"
                ref="input"
            ></textarea>
        </template>
        <template v-else-if="type == 'select'">
            <select
                :class="`form-select ${ isInvalidInput() }`" 
                :id="inputId(label)" 
                :placeholder="label" 
                :value="modelValue ?? ''"
                @input="$emit('update:modelValue', $event.target.value)"
                :required="required"
                :disabled="disabled"
                ref="input"
            >
                <slot name="option"></slot>
            </select>
        </template>
        <template v-else>
            <input 
                :class="`form-control ${ isInvalidInput() }`" 
                :id="inputId(label)" 
                :type="type" 
                :placeholder="label"
                :value="modelValue ?? ''"
                @input="$emit('update:modelValue', $event.target.value)"
                :required="required"
                :min="min"
                :max="max"
                :disabled="disabled"
                ref="input"
            >
        </template>
        <label :for="inputId(label)"  class="form-label opacity-75">
            {{ label }}
            <span v-if="required" class="text-danger">*</span>
        </label>
        <!-- <div v-show="typeof isInvalid === 'function' && isInvalid() !== ''" class="text-danger ">
            {{ typeof isInvalid === 'function' ? isInvalid() : isInvalid }}
        </div> -->
        <div v-show="!disableErrorLabel && error" class="text-danger mt-1" style="font-size: smaller">
            {{ error }}
        </div>
        <small v-if="caption" class="d-block mt-2 opacity-50" style="font-size:x-small">
            <span v-html="caption"></span>
        </small>
        <slot/>
    </div>
</template>
