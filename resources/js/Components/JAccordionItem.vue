<script setup>
defineProps({
    parent: {
        type: String,
        default: "j-accordion",
    },
    noParent: {
        type: Boolean,
        default: false
    },
    show: {
        type: Boolean,
        default: false
    },
    target: {
        type: String,
        default: "",
    },
    title: {
        type: String,
        default: "No title",
    },
});
</script>
<template>
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button
                style="background-color:transparent"
                class="accordion-button"
                :class="{'collapsed' : !show}"
                type="button"
                data-bs-toggle="collapse"
                :data-bs-target="`#${target}`"
                aria-expanded="false"
                :aria-controls="target"
            >
                <slot name="header">
                    {{ title }}
                </slot>
            </button>
        </h2>

        <div v-if="noParent" :id="target" class="accordion-collapse collapse" :class="{'show' : show}">
            <div class="accordion-body">
                <slot />
            </div>
        </div>

        <div
            v-else
            :id="target"
            class="accordion-collapse collapse"
            :class="{'show' : show}"
            :data-bs-parent="`#${parent}`"
        >
            <div class="accordion-body">
                <slot />
            </div>
        </div>

       
    </div>
</template>
