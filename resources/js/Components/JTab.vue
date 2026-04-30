<script setup>
import { onMounted, reactive, watch } from 'vue';

const emit = defineEmits(['active'])
const props = defineProps({
    tabs: Array,
    id: String
})
const state = reactive({active: null})

onMounted(() => {
    mountADefaultTabIfNothingIsSelected()
})

watch(() => state.active, (tab) => {
    setTab(tab)
    getTab()
    activeTab()
})

function setTab(tab) { localStorage.setItem(`activeTab${props.id}`, tab) }
function getTab() { state.active = localStorage.getItem(`activeTab${props.id}`) }
function activeTab() { emit('active', state.active) }

function mountADefaultTabIfNothingIsSelected() {
    if (getTab() === undefined)
        setTab(props.tabs[0])
    if (state.active === null)
        getTab()
}
</script>

<template>
    <div class="pb-5">
        <ul class="nav nav-tabs">
            <li class="nav-item" v-for="(tab, index) in tabs" :key="index">
                <a @click="state.active = tab"
                    :class="`nav-link ${state.active === tab ? 'active' : 'text-reset opacity-50'}`" 
                    href="#">
                    {{ tab }}
                </a>
            </li>
        </ul>
    </div>
</template>