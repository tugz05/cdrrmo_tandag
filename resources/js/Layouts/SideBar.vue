<script setup>
import JDropdownDivider from '@/Components/JDropdownDivider.vue';
import JDropdownItem from '@/Components/JDropdownItem.vue';
import JDropdownMenu from '@/Components/JDropdownMenu.vue';
import JLogo from '@/Components/JLogo.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();

const menuOpen = ref(false);
const menuButton = ref(null);
const closeButton = ref(null);
let removeNavigationListener = null;

function closeMenu() {
    menuOpen.value = false;
}

function onKeydown(event) {
    if (event.key === 'Escape' && menuOpen.value) {
        closeMenu();
    }
}

watch(menuOpen, async (open, wasOpen) => {
    document.body.classList.toggle('nav-open', open);
    await nextTick();
    if (open) {
        closeButton.value?.focus();
    } else if (wasOpen) {
        menuButton.value?.focus();
    }
});

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    removeNavigationListener = router.on('start', closeMenu);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    removeNavigationListener?.();
    document.body.classList.remove('nav-open');
});

const baseNavLinks = [
    {
        icon: 'pie-chart',
        title: 'Dashboard',
        route: 'dashboard',
        param: '',
        active: 'dashboard',
        items: [],
        superAdminOnly: false,
    },
    {
        icon: 'clipboard2-pulse',
        title: 'SIR forms',
        route: 'situational-incident-reports.index',
        param: '',
        active: 'situational-incident',
        items: [],
        superAdminOnly: false,
    },
    {
        icon: 'graph-up',
        title: 'Reports',
        route: 'reports.index',
        param: { type: 'All' },
        active: 'reports',
        items: [],
        superAdminOnly: false,
    },
    {
        icon: 'geo-alt',
        title: 'Live Map',
        route: 'live-map.index',
        param: '',
        active: 'live-map',
        items: [],
        superAdminOnly: false,
    },
    {
        icon: 'people',
        title: 'User Accounts',
        route: 'users.index',
        active: 'users',
        items: [],
        superAdminOnly: false,
    },
    {
        icon: 'file-earmark-post-fill',
        title: 'Posts',
        route: 'posts.index',
        param: '',
        active: 'posts',
        items: [],
        superAdminOnly: false,
    },
    {
        icon: 'person-gear',
        title: 'Staff & super admin',
        route: 'administrators.index',
        param: '',
        active: 'administrators',
        items: [],
        superAdminOnly: true,
    },
];

const navlinks = computed(() => {
    const isSuper = page.props.auth?.isSuperAdmin === true;
    return baseNavLinks.filter((l) => !l.superAdminOnly || isSuper);
});

/**
 * One active nav item at a time. Avoid substring collisions (e.g. "reports" inside "situational-incident-reports").
 */
function isNavItemActive(nav) {
    const path = (page.url || (typeof window !== 'undefined' ? window.location.pathname : '')).toLowerCase();

    switch (nav.active) {
        case 'dashboard':
            return /\/admin\/dashboard\/?$/.test(path);
        case 'situational-incident':
            return path.includes('/admin/situational-incident-reports');
        case 'reports':
            return /\/admin\/reports(\/|$)/.test(path) && !path.includes('situational-incident');
        case 'live-map':
            return /\/admin\/live-map(\/|$)/.test(path);
        case 'users':
            return /\/admin\/users(\/|$)/.test(path);
        case 'posts':
            return /\/admin\/posts(\/|$)/.test(path);
        case 'administrators':
            return path.includes('/admin/administrators');
        default:
            return false;
    }
}
</script>
<template>
    <div class="mobile-bar no-print">
        <button
            ref="menuButton"
            type="button"
            class="mobile-bar__menu"
            aria-label="Open menu"
            aria-controls="app-navigation"
            :aria-expanded="menuOpen"
            @click="menuOpen = true"
        >
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <JLogo size="32px" />
        <span class="mobile-bar__name">MDRRMO Tago</span>
    </div>

    <button
        v-if="menuOpen"
        type="button"
        class="nav-backdrop"
        aria-label="Close menu"
        tabindex="-1"
        @click="closeMenu"
    ></button>

    <nav id="app-navigation" class="navigation no-print" :class="{ 'is-open': menuOpen }" aria-label="Main">
        <div class="nav-brand">
            <JLogo size="40px" />
            <div>
                <div class="nav-brand__name">MDRRMO Tago</div>
                <div class="nav-brand__tagline">Operations</div>
            </div>
            <button ref="closeButton" type="button" class="nav-close" aria-label="Close menu" @click="closeMenu">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>

        <div class="nav-user">
            <div class="dropdown">
                <button class="btn p-0 border-0 w-100 text-start" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="nav-user__trigger d-flex align-items-center gap-2 rounded-1 px-2 py-2">
                        <div class="avatar-sidebar">{{ $page.props.auth.user.name.charAt(0).toUpperCase() }}</div>
                        <div class="flex-fill overflow-hidden">
                            <div class="nav-user__name text-truncate">{{ $page.props.auth.user.name }}</div>
                            <div class="nav-user__email text-truncate">{{ $page.props.auth.user.email }}</div>
                        </div>
                        <i class="bi bi-chevron-expand nav-user__caret"></i>
                    </div>
                </button>
                <JDropdownMenu class="py-2">
                    <div class="px-3 py-2 border-bottom mb-2">
                        <div class="fw-semibold small text-dark">{{ $page.props.auth.user.name }}</div>
                        <div class="text-muted small">{{ $page.props.auth.user.email }}</div>
                    </div>
                    <Link class="dropdown-item text-dark fw-normal" :href="route('profile.edit')">
                        <i class="bi bi-person me-3"></i> Profile
                    </Link>
                    <JDropdownDivider />
                    <Link class="dropdown-item text-danger" :href="route('logout')" method="post" as="button">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </Link>
                </JDropdownMenu>
            </div>
        </div>

        <ul v-if="$page.props.auth.canAccessAdmin" class="nav-links">
            <li v-for="nav in navlinks" :key="nav.route">
                <Link
                    :href="route(nav.route, nav.param)"
                    class="nav-links__link"
                    :class="{ active: isNavItemActive(nav) }"
                    :aria-current="isNavItemActive(nav) ? 'page' : undefined"
                >
                    <span class="icon"><i :class="`bi bi-${nav.icon}`" aria-hidden="true"></i></span>
                    <span class="title">{{ nav.title }}</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
