<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    breadcrumbItems: Array,
    title: String,
    subtitle: String,
    /** Tighter top/bottom spacing for dense admin views (e.g. Posts). */
    compact: {
        type: Boolean,
        default: false,
    },
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
    <header
        class="j-header-title d-flex align-items-center justify-content-between"
        :class="compact ? 'j-header-title--compact' : 'j-header-title--default'"
    >
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

<style scoped>
.j-header-title--default {
    margin-top: clamp(2.5rem, 6vw, 4.5rem);
    margin-bottom: clamp(1.75rem, 4vw, 3.25rem);
    gap: 1.25rem;
}

.j-header-title--compact {
    margin-top: 0.35rem;
    margin-bottom: 1rem;
    gap: 0.75rem;
}

@media (min-width: 992px) {
    .j-header-title--compact {
        margin-top: 0.5rem;
        margin-bottom: 1.125rem;
    }
}

:global(#btn-toggle-sidenav) {
    display: none;
}
@media (max-width: 768px) {
    :global(#btn-toggle-sidenav) {
        display: inline-block;
    }
}
</style>
