<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted } from 'vue'

const props = defineProps({
    breadcrumbItems: Array,
    title: String,
    subtitle: String
})

// onMounted(() => {
//     let sideNavBtn = document.getElementById('btn-toggle-sidenav')
//     let sideNav = document.getElementById('custom-sidenav')
//     sideNavBtn.addEventListener('click', function() {
//         sideNav.style.left = "0"
//         sideNav.classList.add("shadow")
//     })
// })

const totalCountOfItems = () => props.breadcrumbItems.length - 1
const title = () =>  props.title ?? 'No title'

const isItemNotActive = itemIndex => 
    itemIndex !== totalCountOfItems() ? true : false

</script>

<template>
    <header class="mb-14 mt-20 d-flex gap-5 align-items-center justify-content-between">
        <hgroup>
            <h6 v-if="subtitle" class="fw-normal text-muted" style="text-transform:capitalize;">{{ subtitle }}</h6>
            <h2 class="fw-bold m-0 mb-2">
                <slot>
                    <nav style="--bs-breadcrumb-divider: url(&#34;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E&#34;);" aria-label="breadcrumb">
                        <ol class="breadcrumb m-0">
                            <!-- <li class="breadcrumb-item"><Link :href="route('dashboard.index')">Home</Link></li> -->
                            <li v-for="item, index in props.breadcrumbItems" 
                                :key="index" 
                                style="max-width:15em; font-weight:400;"
                                :class="isItemNotActive(index)
                                    ? 'breadcrumb-item text-truncate' 
                                    : 'breadcrumb-item text-truncate active text-reset'" 
                                >
                                <template v-if="isItemNotActive(index)">
                                    <Link :href="route(item.route)" class="link-secondary opacity-75">{{ item.title }}</Link>
                                </template>
                                <template v-else>
                                    <span :title="item.title">
                                        {{  item.title }}
                                    </span>
                                </template>
                            </li>
                        </ol>
                    </nav>
                </slot>
            </h2>
            <!-- <h3 class="fw-bolder text-truncate text-wrap m-0" :title="title()">
                {{ title() }}
            </h3> -->
        </hgroup>
        <div>
            <slot name="end"/>
        </div>
    </header>
</template>

<style>

    #btn-toggle-sidenav {
        display: none;
    }
    @media (max-width: 768px) {
        #btn-toggle-sidenav {
            display: inline-block;
        }
    }
</style>
