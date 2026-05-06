<script setup>
import JDropdownDivider from '@/Components/JDropdownDivider.vue';
import JDropdownItem from '@/Components/JDropdownItem.vue';
import JDropdownMenu from '@/Components/JDropdownMenu.vue';
import JLogo from '@/Components/JLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

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
    const path = (typeof window !== 'undefined' ? window.location.pathname : page.url || '').toLowerCase();

    switch (nav.active) {
        case 'dashboard':
            return /\/admin\/dashboard\/?$/.test(path);
        case 'situational-incident':
            return path.includes('/admin/situational-incident-reports');
        case 'reports':
            return /\/admin\/reports(\/|$)/.test(path) && !path.includes('situational-incident');
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
    <div class="navigation">
        <div class="d-flex align-items-center gap-3 justify-content-between px-3 mt-3">
            <div><JLogo size="50px" /> </div>
            <div class="dropdown">
                <button class="btn border-0" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar avatar-lg">{{ $page.props.auth.user.name.charAt(0) }}</span>
                </button>
                <JDropdownMenu class="py-2">
                    <div>
                        <div class="px-3 py-3 text-center">
                            <div class="fw-bold">{{ $page.props.auth.user.name }}</div>
                            <span class="opacity-50" style="font-size:smaller">{{ $page.props.auth.user.email }}</span>
                        </div>
                        <JDropdownDivider/>
                        <Link class="dropdown-item text-dark fw-normal" :href="route('profile.edit')">
                            <i class="bi bi-person me-3"></i> Profile
                        </Link>
                        <Link class="dropdown-item text-danger" :href="route('logout')" method="post" as="button">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </Link>
                    </div>
                </JDropdownMenu>
            </div>
        </div>
        <ul v-if="$page.props.auth.canAccessAdmin">
            <li v-for="nav in navlinks" :key="nav.route">
                <div :class="`list ${isNavItemActive(nav) ? 'active' : ''}`">
                    <b></b>
                    <b></b>
                    <Link :href="route(nav.route, nav.param)">
                        <span class="icon"><i :class="`bi bi-${nav.icon}`"></i></span>
                        <span class="title">{{ nav.title }}</span>
                    </Link>
                </div>
            </li>
        </ul>
        <div></div>
    </div>
</template>
