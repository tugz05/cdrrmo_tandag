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

        <!-- Brand -->
        <div class="nav-brand d-flex align-items-center gap-3 px-4 py-4">
            <JLogo size="38px" />
            <div>
                <div class="nav-brand__name">CDRRMO</div>
                <div class="nav-brand__tagline">Operations</div>
            </div>
        </div>

        <!-- User profile -->
        <div class="nav-user px-3 py-3">
            <div class="dropdown">
                <button class="btn p-0 border-0 w-100 text-start" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="nav-user__trigger d-flex align-items-center gap-2 rounded-3 px-2 py-2">
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
                        <div class="text-muted" style="font-size: 0.72rem">{{ $page.props.auth.user.email }}</div>
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

        <!-- Separator -->
        <div class="nav-sep mx-3"></div>

        <!-- Section label -->
        <div class="nav-label px-4 pt-3 pb-1">Menu</div>

        <!-- Nav links -->
        <ul v-if="$page.props.auth.canAccessAdmin">
            <li v-for="nav in navlinks" :key="nav.route">
                <div :class="`list ${isNavItemActive(nav) ? 'active' : ''}`">
                    <Link :href="route(nav.route, nav.param)">
                        <span class="icon"><i :class="`bi bi-${nav.icon}`"></i></span>
                        <span class="title">{{ nav.title }}</span>
                    </Link>
                </div>
            </li>
        </ul>

    </div>
</template>
