<script setup>
import JLogo from '@/Components/JLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
    latestNews: { type: Array, default: () => [] },
    latestSafetyTips: { type: Array, default: () => [] },
    latestPreparedness: { type: Array, default: () => [] },
    publicStats: {
        type: Object,
        default: () => ({
            reportsRecorded: 0,
            channelsMessages: 0,
            channelsCalls: 0,
        }),
    },
    mobileApp: {
        type: Object,
        default: () => ({
            android: null,
            ios: null,
            direct: null,
        }),
    },
});

const heroRef = ref(null);
const mouse = ref({ x: 0, y: 0 });
const year = new Date().getFullYear();

const updatesTab = ref('news');
const tabIds = ['news', 'safety', 'prep'];

const updatesForTab = computed(() => {
    if (updatesTab.value === 'news') {
        return props.latestNews;
    }
    if (updatesTab.value === 'safety') {
        return props.latestSafetyTips;
    }
    return props.latestPreparedness;
});

const featuredStory = computed(() => props.latestNews[0] ?? null);

const hasMobileLinks = computed(
    () =>
        !!(props.mobileApp?.android || props.mobileApp?.ios || props.mobileApp?.direct)
);

const statCards = computed(() => [
    {
        key: 'reports',
        value: formatCount(props.publicStats.reportsRecorded),
        label: 'Reports on record',
        hint: 'Community reports shared with CDRRMO',
    },
    {
        key: 'msg',
        value: formatCount(props.publicStats.channelsMessages),
        label: 'Message reports',
        hint: 'Incidents reported by text or chat',
    },
    {
        key: 'call',
        value: formatCount(props.publicStats.channelsCalls),
        label: 'Call-linked reports',
        hint: 'Incidents linked to phone reports',
    },
]);

function formatCount(n) {
    const num = Number(n) || 0;
    if (num >= 1000000) {
        return `${(num / 1000000).toFixed(1)}M`;
    }
    if (num >= 10000) {
        return `${(num / 1000).toFixed(1)}k`;
    }
    return String(num);
}

const pillars = [
    {
        icon: 'shield-check',
        title: 'Prevention & mitigation',
        text: 'Risk mapping, public education, and barangay programs that cut exposure before hazards hit.',
    },
    {
        icon: 'broadcast',
        title: 'Preparedness & coordination',
        text: 'Training, early warning, and community drills so you know what to do before a hazard arrives.',
    },
    {
        icon: 'life-preserver',
        title: 'Response & recovery',
        text: 'Relief, rescue, and recovery so families and livelihoods bounce back stronger.',
    },
];

const focusAreas = [
    {
        icon: 'newspaper',
        label: 'News & advisories',
        desc: 'Official updates when routes, weather, or risks change.',
    },
    {
        icon: 'heart-pulse',
        label: 'Safety tips',
        desc: 'Everyday habits that protect homes, schools, and workplaces.',
    },
    {
        icon: 'compass',
        label: 'Emergency readiness',
        desc: 'Go-bags, rally points, and plans your household can practice.',
    },
];

const sectionIds = ['hero', 'app-download', 'mission', 'pillars', 'updates', 'resources', 'cta'];

function sectionLabel(id) {
    switch (id) {
        case 'hero':
            return 'Top';
        case 'mission':
            return 'Mission';
        case 'pillars':
            return 'Services';
        case 'updates':
            return 'Updates';
        case 'resources':
            return 'Resources';
        case 'app-download':
            return 'App';
        case 'cta':
            return 'Join';
        default:
            return id;
    }
}
const activeSection = ref('hero');
let sectionIo = null;
let revealIo = null;

function observeSections() {
    sectionIo?.disconnect();
    sectionIo = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    activeSection.value = entry.target.id;
                }
            });
        },
        { rootMargin: '-12% 0px -55% 0px', threshold: [0, 0.15, 0.35] }
    );
    sectionIds.forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            sectionIo.observe(el);
        }
    });
}

function observeReveal(rootEl) {
    revealIo?.disconnect();
    const nodes = rootEl?.querySelectorAll('.welcome-reveal') ?? [];
    revealIo = new IntersectionObserver(
        (entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting) {
                    e.target.classList.add('welcome-reveal--visible');
                }
            });
        },
        { rootMargin: '0px 0px -6% 0px', threshold: 0.08 }
    );
    nodes.forEach((n) => revealIo.observe(n));
}

function scrollToSection(id, event) {
    event?.preventDefault();
    const el = document.getElementById(id);
    if (!el) {
        return;
    }
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    const hash = `#${id}`;
    if (window.location.hash !== hash) {
        history.replaceState(null, '', hash);
    }
}

function syncScrollFromHash() {
    const raw = window.location.hash?.replace(/^#/, '');
    if (!raw) {
        return;
    }
    nextTick(() => {
        const el = document.getElementById(raw);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
}

const welcomeRoot = ref(null);

onMounted(async () => {
    await nextTick();
    observeReveal(welcomeRoot.value);
    observeSections();
    syncScrollFromHash();
    window.addEventListener('hashchange', syncScrollFromHash);
});

watch(updatesTab, async () => {
    await nextTick();
    observeReveal(welcomeRoot.value);
});

onUnmounted(() => {
    sectionIo?.disconnect();
    revealIo?.disconnect();
    window.removeEventListener('hashchange', syncScrollFromHash);
});

function onHeroMove(e) {
    if (!heroRef.value) {
        return;
    }
    const r = heroRef.value.getBoundingClientRect();
    mouse.value = {
        x: ((e.clientX - r.left) / r.width - 0.5) * 2,
        y: ((e.clientY - r.top) / r.height - 0.5) * 2,
    };
}

function onHeroLeave() {
    mouse.value = { x: 0, y: 0 };
}

const parallaxStyle = computed(() => ({
    transform: `translate(${mouse.value.x * 14}px, ${mouse.value.y * 9}px)`,
}));

const glowStyle = computed(() => ({
    transform: `translate(${mouse.value.x * -20}px, ${mouse.value.y * -15}px)`,
}));

function tabLabel(id) {
    if (id === 'news') {
        return 'News';
    }
    if (id === 'safety') {
        return 'Safety tips';
    }
    return 'Preparedness';
}

function emptyCopy(tab) {
    if (tab === 'news') {
        return 'New stories will show up here as soon as they are posted.';
    }
    if (tab === 'safety') {
        return 'Safety tips will appear here when new guidance is published.';
    }
    return 'Emergency preparedness articles will appear here when they go live.';
}
</script>

<template>
    <Head>
        <title>CDRRMO Tandag — Disaster resilience for every community</title>
        <meta
            name="description"
            content="Official City Disaster Risk Reduction and Management Office portal for Tandag City — preparedness, response, recovery, and public advisories."
        />
    </Head>

    <div ref="welcomeRoot" class="welcome-root font-sans antialiased">
        <!-- Ambient -->
        <div class="welcome-bg-mesh" aria-hidden="true" />

        <header class="welcome-nav">
            <div class="welcome-nav__inner container-xl">
                <Link href="/" class="welcome-brand">
                    <JLogo size="42px" />
                    <span class="welcome-brand__text">
                        <span class="welcome-brand__title">CDRRMO</span>
                        <span class="welcome-brand__sub">City of Tandag</span>
                    </span>
                </Link>

                <nav class="welcome-nav__links d-none d-lg-flex align-items-center gap-1" aria-label="Page sections">
                    <a
                        v-for="id in sectionIds.filter((s) => s !== 'hero')"
                        :key="id"
                        :href="`#${id}`"
                        class="welcome-nav-chip"
                        :class="{ 'welcome-nav-chip--active': activeSection === id }"
                        @click="scrollToSection(id, $event)"
                    >
                        {{ sectionLabel(id) }}
                    </a>
                </nav>

                <div class="welcome-nav__actions d-flex align-items-center gap-2 flex-shrink-0">
                    <Link
                        v-if="canLogin"
                        href="/login"
                        class="btn btn-sm welcome-btn-outline d-none d-sm-inline-flex"
                    >
                        Sign in
                    </Link>
                    <Link
                        v-if="canLogin"
                        href="/login"
                        class="btn btn-sm welcome-btn-outline d-inline-flex d-sm-none p-2"
                        aria-label="Sign in"
                    >
                        <i class="bi bi-box-arrow-in-right fs-6" />
                    </Link>
                    <Link
                        v-if="canRegister"
                        href="/register"
                        class="btn btn-sm welcome-btn-primary d-none d-sm-inline-flex"
                    >
                        Create account
                    </Link>
                    <Link
                        v-if="canRegister"
                        href="/register"
                        class="btn btn-sm welcome-btn-primary d-inline-flex d-sm-none p-2"
                        aria-label="Create account"
                    >
                        <i class="bi bi-person-plus fs-6" />
                    </Link>
                </div>
            </div>
            <div class="welcome-nav__scroll d-lg-none">
                <a
                    v-for="id in sectionIds.filter((s) => s !== 'hero')"
                    :key="`m-${id}`"
                    :href="`#${id}`"
                    class="welcome-pill"
                    @click="scrollToSection(id, $event)"
                >
                    {{ sectionLabel(id) }}
                </a>
            </div>
        </header>

        <!-- Hero -->
        <section
            id="hero"
            ref="heroRef"
            class="welcome-hero welcome-anchor"
            @mousemove="onHeroMove"
            @mouseleave="onHeroLeave"
        >
            <div class="welcome-hero__glow" :style="glowStyle" aria-hidden="true" />
            <div class="welcome-hero__grid" aria-hidden="true" />
            <div class="welcome-hero__orbit" aria-hidden="true">
                <span class="welcome-orbit welcome-orbit--a"><i class="bi bi-shield-fill-check" /></span>
                <span class="welcome-orbit welcome-orbit--b"><i class="bi bi-lightning-charge-fill" /></span>
                <span class="welcome-orbit welcome-orbit--c"><i class="bi bi-heart-pulse-fill" /></span>
            </div>

            <div class="container-xl welcome-hero__content position-relative">
                <div class="row align-items-stretch g-4 g-lg-5 py-5">
                    <div class="col-lg-7 d-flex flex-column justify-content-center">
                        <p class="welcome-eyebrow welcome-hero-fade" style="animation-delay: 0.04s">
                            City Disaster Risk Reduction and Management Office
                        </p>
                        <h1 class="welcome-title welcome-hero-fade" style="animation-delay: 0.1s">
                            Ready today.
                            <span class="welcome-title__accent"> Safer tomorrow.</span>
                        </h1>
                        <p class="welcome-lead welcome-hero-fade" style="animation-delay: 0.18s">
                            One place for official advisories, preparedness guidance, and updates for everyone in
                            Tandag City—on the web or in your pocket with the mobile app.
                        </p>
                        <div class="d-flex flex-wrap gap-3 mt-3 welcome-hero-fade" style="animation-delay: 0.26s">
                            <a
                                href="#updates"
                                class="btn welcome-btn-primary btn-lg px-4 rounded-pill"
                                @click="scrollToSection('updates', $event)"
                            >
                                Read latest updates
                            </a>
                            <a
                                href="#mission"
                                class="btn welcome-btn-ghost btn-lg px-4 rounded-pill"
                                @click="scrollToSection('mission', $event)"
                            >
                                Why we exist
                            </a>
                        </div>
                        <div class="welcome-hero__download-wrap welcome-hero-fade" style="animation-delay: 0.32s">
                            <a
                                href="#app-download"
                                class="welcome-download-hero"
                                @click="scrollToSection('app-download', $event)"
                            >
                                <span class="welcome-download-hero__pulse" aria-hidden="true" />
                                <i class="bi bi-phone-fill welcome-download-hero__phone" aria-hidden="true" />
                                <span class="welcome-download-hero__text">
                                    <span class="welcome-download-hero__kicker">Download</span>
                                    <span class="welcome-download-hero__title">CDRRMO mobile app</span>
                                    <span class="welcome-download-hero__hint">Report, read advisories, manage your profile</span>
                                </span>
                                <span class="welcome-download-hero__cta">
                                    Get the app
                                    <i class="bi bi-arrow-right-short fs-4" aria-hidden="true" />
                                </span>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div
                            class="welcome-featured welcome-hero-fade h-100"
                            style="animation-delay: 0.15s"
                        >
                            <template v-if="featuredStory">
                                <span class="welcome-featured__tag">Featured story</span>
                                <h2 class="welcome-featured__title">
                                    <a :href="featuredStory.href" class="welcome-featured__link stretched-link">
                                        {{ featuredStory.title }}
                                    </a>
                                </h2>
                                <p class="welcome-featured__excerpt">{{ featuredStory.excerpt }}</p>
                                <div class="welcome-featured__meta">
                                    <span class="welcome-chip">
                                        <i class="bi bi-newspaper me-1" /> News
                                    </span>
                                    <a class="btn btn-sm welcome-btn-soft rounded-pill" :href="featuredStory.href">
                                        Open article
                                    </a>
                                </div>
                            </template>
                            <template v-else>
                                <span class="welcome-featured__tag">Getting started</span>
                                <h2 class="welcome-featured__title h4">Public advisories go live here</h2>
                                <p class="welcome-featured__excerpt mb-0">
                                    When new advisories, safety tips, or preparedness articles are posted, the latest
                                    one will show here first.
                                </p>
                            </template>

                            <div class="welcome-hero-card welcome-parallax d-none d-lg-block" :style="parallaxStyle">
                                <div class="welcome-hero-card__inner">
                                    <div class="welcome-hero-card__icon">
                                        <i class="bi bi-geo-alt-fill" />
                                    </div>
                                    <div class="text-white small fw-semibold">Tandag City</div>
                                    <div class="text-white-50 small">Surigao del Sur</div>
                                    <div class="welcome-hero-card__pulse" aria-hidden="true" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="welcome-hero__slant" aria-hidden="true" />
        </section>

        <!-- Stats -->
        <section class="welcome-stats-wrap">
            <div class="container-xl welcome-stats">
                <div class="row g-3">
                    <div v-for="s in statCards" :key="s.key" class="col-md-4">
                        <div class="welcome-stat welcome-reveal h-100">
                            <div class="welcome-stat__value">{{ s.value }}</div>
                            <div class="welcome-stat__label">{{ s.label }}</div>
                            <div class="welcome-stat__hint">{{ s.hint }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mobile app download -->
        <section id="app-download" class="welcome-app-strip welcome-anchor">
            <div class="container-xl">
                <div class="welcome-app-strip__inner welcome-reveal">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-5">
                            <span class="welcome-app-strip__badge">
                                <i class="bi bi-download me-1" aria-hidden="true" />
                                Mobile app
                            </span>
                            <h2 class="welcome-h2 text-white mt-2 mb-2">Get the official app</h2>
                            <p class="text-white-50 mb-0 small">
                                The same trusted information as this website—install once and stay informed at home
                                or on the go.
                            </p>
                        </div>
                        <div class="col-lg-7">
                            <div class="d-flex flex-wrap gap-2 justify-content-lg-end justify-content-center align-items-center">
                                <a
                                    v-if="mobileApp.android"
                                    :href="mobileApp.android"
                                    class="welcome-store-btn welcome-store-btn--play"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <i class="bi bi-google-play welcome-store-btn__icon" aria-hidden="true" />
                                    <span>
                                        <span class="welcome-store-btn__small">Get it on</span>
                                        <span class="welcome-store-btn__big">Google Play</span>
                                    </span>
                                </a>
                                <a
                                    v-if="mobileApp.ios"
                                    :href="mobileApp.ios"
                                    class="welcome-store-btn welcome-store-btn--apple"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <i class="bi bi-apple welcome-store-btn__icon" aria-hidden="true" />
                                    <span>
                                        <span class="welcome-store-btn__small">Download on the</span>
                                        <span class="welcome-store-btn__big">App Store</span>
                                    </span>
                                </a>
                                <a
                                    v-if="mobileApp.direct"
                                    :href="mobileApp.direct"
                                    class="btn btn-lg btn-light fw-bold px-4 rounded-pill"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <i class="bi bi-file-earmark-arrow-down me-2" aria-hidden="true" />
                                    Direct download
                                </a>
                            </div>
                            <p
                                v-if="!hasMobileLinks"
                                class="text-white-50 small mb-0 mt-3 text-lg-end text-center"
                            >
                                App store download buttons will appear here when they are available.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Mission -->
        <section id="mission" class="welcome-section welcome-section--tilt welcome-anchor">
            <div class="container-xl">
                <div class="row justify-content-center">
                    <div class="col-lg-9 text-center welcome-reveal">
                        <h2 class="welcome-h2">Built for real-world emergencies</h2>
                        <p class="welcome-text text-muted mb-0">
                            CDRRMO Tandag brings together advisories, barangay updates, and public alerts—so you get
                            clear, trustworthy information when it matters most.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pillars -->
        <section id="pillars" class="welcome-section welcome-anchor">
            <div class="container-xl">
                <div class="welcome-section-head welcome-reveal">
                    <h2 class="welcome-h2 mb-2">How we protect communities</h2>
                    <p class="text-muted mb-0">Three pillars that guide how we serve families and neighborhoods.</p>
                </div>
                <div class="row g-4 mt-2">
                    <div v-for="(p, i) in pillars" :key="i" class="col-md-4">
                        <article class="welcome-card welcome-reveal h-100" :style="{ animationDelay: `${i * 0.06}s` }">
                            <div class="welcome-card__num">{{ String(i + 1).padStart(2, '0') }}</div>
                            <div class="welcome-card__icon">
                                <i :class="`bi bi-${p.icon}`" />
                            </div>
                            <h3 class="h5 mt-3 mb-2">{{ p.title }}</h3>
                            <p class="small text-muted mb-0">{{ p.text }}</p>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <!-- Live updates -->
        <section id="updates" class="welcome-section welcome-section--dark welcome-anchor">
            <div class="container-xl">
                <div class="row align-items-end g-4 mb-4">
                    <div class="col-lg-8 welcome-reveal">
                        <h2 class="welcome-h2 text-white mb-2">Latest updates</h2>
                        <p class="text-white-50 mb-0">
                            Official news, safety guidance, and preparedness articles—updated here as they are released.
                        </p>
                    </div>
                    <div class="col-lg-4 welcome-reveal">
                        <div class="welcome-tabs" role="tablist">
                            <button
                                v-for="tid in tabIds"
                                :key="tid"
                                type="button"
                                class="welcome-tab"
                                :class="{ 'welcome-tab--active': updatesTab === tid }"
                                role="tab"
                                :aria-selected="updatesTab === tid"
                                @click="updatesTab = tid"
                            >
                                {{ tabLabel(tid) }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="welcome-reveal">
                    <div v-if="updatesForTab.length" class="row g-3">
                        <div
                            v-for="item in updatesForTab"
                            :key="`${updatesTab}-${item.id}`"
                            class="col-md-6 col-xl-4"
                        >
                            <article class="welcome-update-card h-100">
                                <div class="welcome-update-card__top">
                                    <span class="welcome-update-badge">{{ item.type }}</span>
                                </div>
                                <h3 class="welcome-update-card__title">
                                    <a :href="item.href" class="stretched-link text-decoration-none">
                                        {{ item.title }}
                                    </a>
                                </h3>
                                <p class="welcome-update-card__excerpt">{{ item.excerpt }}</p>
                                <div class="welcome-update-card__foot">
                                    <span class="small text-muted">Open full article</span>
                                    <i class="bi bi-arrow-up-right" />
                                </div>
                            </article>
                        </div>
                    </div>
                    <div v-else class="welcome-empty welcome-reveal">
                        <i class="bi bi-journal-richtext welcome-empty__icon" />
                        <p class="mb-2 fw-semibold text-white">Nothing published in this category yet</p>
                        <p class="text-white-50 small mb-0">{{ emptyCopy(updatesTab) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Resources -->
        <section id="resources" class="welcome-section welcome-anchor">
            <div class="container-xl">
                <div class="welcome-bento welcome-reveal">
                    <div class="welcome-bento__main">
                        <h2 class="welcome-h2 mb-3">Information channels</h2>
                        <p class="welcome-text text-muted mb-4">
                            Read official stories under News, Safety tips, and Emergency preparedness—you can open them
                            on this site or continue reading in the mobile app if you use it.
                        </p>
                        <div class="row g-3">
                            <div v-for="(f, i) in focusAreas" :key="i" class="col-sm-4">
                                <div class="welcome-mini">
                                    <i :class="`bi bi-${f.icon}`" />
                                    <div class="fw-semibold small">{{ f.label }}</div>
                                    <div class="text-muted welcome-mini__desc">{{ f.desc }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <aside class="welcome-bento__aside">
                        <div class="welcome-aside-card">
                            <a
                                href="#app-download"
                                class="btn welcome-app-aside-jump w-100 rounded-pill mb-3"
                                @click="scrollToSection('app-download', $event)"
                            >
                                <i class="bi bi-download me-2" aria-hidden="true" />
                                Download mobile app
                            </a>
                            <div
                                v-if="hasMobileLinks"
                                class="d-flex flex-wrap gap-2 mb-3"
                            >
                                <a
                                    v-if="mobileApp.android"
                                    :href="mobileApp.android"
                                    class="btn btn-sm flex-grow-1 text-nowrap welcome-aside-store"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <i class="bi bi-google-play" /> Google Play
                                </a>
                                <a
                                    v-if="mobileApp.ios"
                                    :href="mobileApp.ios"
                                    class="btn btn-sm flex-grow-1 text-nowrap welcome-aside-store"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <i class="bi bi-apple" /> App Store
                                </a>
                            </div>
                            <div class="welcome-aside-card__title">
                                <i class="bi bi-person-check me-2" aria-hidden="true" />
                                New here?
                            </div>
                            <p class="small text-muted mb-3">
                                Create an account to file reports, follow advisories, and manage your profile in the
                                mobile app.
                            </p>
                            <Link
                                v-if="canRegister"
                                href="/register"
                                class="btn welcome-btn-primary w-100 rounded-pill"
                            >
                                Create an account
                            </Link>
                            <p v-else class="small text-muted mb-0">
                                Online registration is not open right now. Please contact CDRRMO if you need help
                                joining.
                            </p>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="cta" class="welcome-cta welcome-anchor">
            <div class="container-xl text-center welcome-reveal">
                <h2 class="welcome-h2 text-white mb-3">Stay connected with CDRRMO</h2>
                <p class="text-white-50 mx-auto mb-4 welcome-cta__lead">
                    Create an account to use the mobile app and receive updates, or sign in if you already have one.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <Link v-if="canRegister" href="/register" class="btn btn-lg btn-light px-5 fw-semibold rounded-pill">
                        Create an account
                    </Link>
                    <Link
                        v-if="canLogin && canRegister"
                        href="/login"
                        class="btn btn-lg welcome-btn-outline-light px-5 rounded-pill"
                    >
                        Sign in
                    </Link>
                    <Link
                        v-if="canLogin && !canRegister"
                        href="/login"
                        class="btn btn-lg btn-light px-5 fw-semibold rounded-pill"
                    >
                        Sign in
                    </Link>
                </div>
            </div>
        </section>

        <footer class="welcome-footer">
            <div class="container-xl d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 py-4">
                <div class="d-flex align-items-center gap-2 small welcome-footer__muted">
                    <JLogo size="28px" />
                    <span>&copy; {{ year }} CDRRMO · City Government of Tandag</span>
                </div>
                <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-3 small welcome-footer__muted text-md-end">
                    <span>Life-threatening emergency? Follow official hotlines and instructions from authorities—not this website alone.</span>
                    <Link href="/privacy-policy" class="welcome-footer__policy-link">Privacy Policy</Link>
                </div>
            </div>
        </footer>

        <!-- Section dots (desktop) -->
        <nav class="welcome-dots d-none d-xl-flex flex-column" aria-label="Section navigation">
            <a
                v-for="id in sectionIds"
                :key="`dot-${id}`"
                :href="`#${id}`"
                class="welcome-dot"
                :class="{ 'welcome-dot--active': activeSection === id }"
                :title="sectionLabel(id)"
                :aria-label="`Go to ${sectionLabel(id)} section`"
                @click="scrollToSection(id, $event)"
            ></a>
        </nav>
    </div>
</template>

<style scoped>
.welcome-anchor {
    scroll-margin-top: 5.5rem;
}

.welcome-root {
    position: relative;
    min-height: 100vh;
    background: var(--cdrrmo-surface, #f0f9ff);
    color: var(--cdrrmo-ink, #0c4a6e);
    overflow-x: clip;
}

.welcome-bg-mesh {
    pointer-events: none;
    position: fixed;
    inset: 0;
    z-index: 0;
    opacity: 0.55;
    background:
        radial-gradient(ellipse 80% 50% at 10% -10%, color-mix(in srgb, var(--cdrrmo-300, #7dd3fc) 35%, transparent), transparent),
        radial-gradient(ellipse 60% 40% at 90% 10%, color-mix(in srgb, var(--cdrrmo-200, #bae6fd) 40%, transparent), transparent),
        radial-gradient(ellipse 50% 30% at 50% 100%, color-mix(in srgb, var(--cdrrmo-100, #e0f2fe) 50%, transparent), transparent);
}

.welcome-nav {
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(16px);
    background: color-mix(in srgb, var(--cdrrmo-surface, #f0f9ff) 88%, white);
    border-bottom: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 16%, transparent);
}

.welcome-nav__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.65rem 1rem;
    position: relative;
    z-index: 2;
}

.welcome-nav-chip {
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    color: var(--cdrrmo-700, #0369a1);
    border: 1px solid transparent;
    transition:
        background 0.2s ease,
        color 0.2s ease,
        border-color 0.2s ease;
}

.welcome-nav-chip:hover {
    background: color-mix(in srgb, var(--cdrrmo-primary, #0284c7) 10%, transparent);
}

.welcome-nav-chip--active {
    background: color-mix(in srgb, var(--cdrrmo-primary, #0284c7) 18%, transparent);
    border-color: color-mix(in srgb, var(--cdrrmo-primary, #0284c7) 35%, transparent);
    color: var(--cdrrmo-900, #0c4a6e);
}

.welcome-nav__scroll {
    display: flex;
    gap: 0.45rem;
    padding: 0 1rem 0.55rem;
    overflow-x: auto;
    scrollbar-width: none;
}

.welcome-nav__scroll::-webkit-scrollbar {
    display: none;
}

.welcome-pill {
    flex-shrink: 0;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-decoration: none;
    text-transform: uppercase;
    color: var(--cdrrmo-700, #0369a1);
    background: color-mix(in srgb, var(--cdrrmo-primary, #0284c7) 9%, transparent);
    border: 1px solid color-mix(in srgb, var(--cdrrmo-primary, #0284c7) 18%, transparent);
}

.welcome-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
}

.welcome-brand__title {
    font-weight: 800;
    letter-spacing: 0.04em;
    display: block;
    line-height: 1.15;
}

.welcome-brand__sub {
    font-size: 0.72rem;
    color: var(--cdrrmo-ink-muted, #64748b);
    display: block;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.welcome-btn-primary {
    background: linear-gradient(
        135deg,
        var(--cdrrmo-500, #0ea5e9) 0%,
        var(--cdrrmo-700, #0369a1) 100%
    );
    border: none;
    color: #fff !important;
    font-weight: 700;
    box-shadow: 0 6px 22px color-mix(in srgb, var(--cdrrmo-600, #0284c7) 38%, transparent);
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease;
}

.welcome-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px color-mix(in srgb, var(--cdrrmo-600, #0284c7) 48%, transparent);
}

.welcome-btn-outline {
    border: 1px solid color-mix(in srgb, var(--cdrrmo-700, #0369a1) 22%, transparent);
    background: var(--cdrrmo-surface-raised, #fff);
    color: var(--cdrrmo-800, #075985);
    font-weight: 700;
}

.welcome-btn-outline:hover {
    border-color: var(--cdrrmo-primary, #0284c7);
    color: var(--cdrrmo-700, #0369a1);
}

.welcome-btn-ghost {
    border: 1px solid rgba(255, 255, 255, 0.45);
    color: #fff !important;
    background: rgba(255, 255, 255, 0.08);
    font-weight: 700;
}

.welcome-btn-ghost:hover {
    background: rgba(255, 255, 255, 0.18);
}

.welcome-btn-soft {
    background: rgba(255, 255, 255, 0.95);
    color: var(--cdrrmo-800, #075985) !important;
    font-weight: 700;
    border: none;
}

.welcome-hero {
    position: relative;
    padding-bottom: 0;
    background: linear-gradient(
        155deg,
        var(--cdrrmo-900, #0c4a6e) 0%,
        var(--cdrrmo-700, #0369a1) 38%,
        var(--cdrrmo-500, #0ea5e9) 100%
    );
    color: #fff;
}

.welcome-hero__grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255, 255, 255, 0.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255, 255, 255, 0.06) 1px, transparent 1px);
    background-size: 56px 56px;
    mask-image: radial-gradient(ellipse at 50% 0%, black 0%, transparent 72%);
    pointer-events: none;
}

.welcome-hero__glow {
    position: absolute;
    width: 130%;
    height: 130%;
    left: -15%;
    top: -15%;
    background: radial-gradient(
        circle at 25% 25%,
        color-mix(in srgb, var(--cdrrmo-200, #bae6fd) 45%, transparent) 0%,
        transparent 42%
    );
    pointer-events: none;
    transition: transform 0.14s ease-out;
}

.welcome-hero__orbit {
    position: absolute;
    right: 8%;
    top: 18%;
    width: min(320px, 40vw);
    height: min(320px, 40vw);
    pointer-events: none;
    z-index: 1;
}

.welcome-orbit {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.22);
    color: #fff;
    font-size: 1.35rem;
    animation: welcome-float 7s ease-in-out infinite;
}

.welcome-orbit--a {
    left: 10%;
    top: 8%;
    animation-delay: 0s;
}

.welcome-orbit--b {
    right: 5%;
    top: 38%;
    animation-delay: 1.2s;
}

.welcome-orbit--c {
    left: 28%;
    bottom: 6%;
    animation-delay: 2.1s;
}

@keyframes welcome-float {
    0%,
    100% {
        transform: translateY(0) scale(1);
    }
    50% {
        transform: translateY(-12px) scale(1.03);
    }
}

.welcome-hero__content {
    z-index: 2;
}

.welcome-eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.2em;
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.58);
    margin-bottom: 1rem;
}

.welcome-title {
    font-size: clamp(2.1rem, 4.5vw, 3.5rem);
    font-weight: 900;
    line-height: 1.05;
    letter-spacing: -0.035em;
}

.welcome-title__accent {
    display: inline;
    background: linear-gradient(90deg, #f0f9ff, #bae6fd, #7dd3fc);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.welcome-lead {
    font-size: 1.08rem;
    line-height: 1.7;
    color: rgba(255, 255, 255, 0.82);
    max-width: 38rem;
}

.welcome-hero-fade {
    animation: welcome-hero-in 0.85s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes welcome-hero-in {
    from {
        opacity: 0;
        transform: translateY(18px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.welcome-featured {
    position: relative;
    border-radius: 1.5rem;
    padding: 1.75rem 1.75rem 2rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.22);
    backdrop-filter: blur(14px);
    min-height: 280px;
}

.welcome-featured__tag {
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.55);
    margin-bottom: 0.75rem;
}

.welcome-featured__title {
    font-size: 1.35rem;
    font-weight: 800;
    line-height: 1.3;
    margin-bottom: 0.75rem;
}

.welcome-featured__link {
    color: #fff;
    text-decoration: none;
}

.welcome-featured__link:hover {
    text-decoration: underline;
    text-underline-offset: 4px;
}

.welcome-featured__excerpt {
    font-size: 0.92rem;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.78);
    margin-bottom: 1.25rem;
}

.welcome-featured__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
}

.welcome-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.14);
    color: #fff;
}

.welcome-hero-card {
    position: absolute;
    right: -0.5rem;
    bottom: -1.25rem;
    width: 200px;
    transition: transform 0.18s ease-out;
    will-change: transform;
}

.welcome-hero-card__inner {
    position: relative;
    border-radius: 1.15rem;
    padding: 1.15rem 1.25rem;
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.24);
    backdrop-filter: blur(10px);
    overflow: hidden;
}

.welcome-hero-card__icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.65rem;
    background: color-mix(in srgb, var(--cdrrmo-200, #bae6fd) 35%, transparent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    margin-bottom: 0.5rem;
    color: #fff;
}

.welcome-hero-card__pulse {
    position: absolute;
    right: -35%;
    bottom: -40%;
    width: 160px;
    height: 160px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.22) 0%, transparent 68%);
    animation: welcome-pulse 4.5s ease-in-out infinite;
}

@keyframes welcome-pulse {
    0%,
    100% {
        opacity: 0.45;
        transform: scale(1);
    }
    50% {
        opacity: 0.85;
        transform: scale(1.06);
    }
}

.welcome-hero__slant {
    height: 72px;
    background: var(--cdrrmo-surface, #f0f9ff);
    clip-path: polygon(0 48%, 100% 0, 100% 100%, 0 100%);
    margin-top: -1px;
}

.welcome-hero__download-wrap {
    margin-top: 1.75rem;
    max-width: min(100%, 34rem);
}

.welcome-download-hero {
    position: relative;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.9rem 1.15rem;
    padding: 1rem 1.15rem 1.05rem 1.1rem;
    border-radius: 1.1rem;
    text-decoration: none;
    color: #fff;
    background: linear-gradient(
        135deg,
        color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 22%, rgba(255, 255, 255, 0.08)),
        color-mix(in srgb, var(--cdrrmo-700, #0369a1) 45%, rgba(0, 0, 0, 0.2))
    );
    border: 1px solid rgba(255, 255, 255, 0.28);
    box-shadow:
        0 0 0 1px color-mix(in srgb, var(--cdrrmo-200, #bae6fd) 15%, transparent) inset,
        0 18px 50px color-mix(in srgb, var(--cdrrmo-900, #0c4a6e) 45%, transparent);
    overflow: hidden;
    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        border-color 0.2s ease;
}

.welcome-download-hero:hover {
    color: #fff;
    transform: translateY(-2px);
    box-shadow:
        0 0 0 1px rgba(255, 255, 255, 0.2) inset,
        0 22px 55px color-mix(in srgb, var(--cdrrmo-900, #0c4a6e) 50%, transparent);
    border-color: rgba(255, 255, 255, 0.38);
}

.welcome-download-hero:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--cdrrmo-200, #bae6fd) 75%, white);
    outline-offset: 3px;
}

.welcome-download-hero__pulse {
    position: absolute;
    inset: -40% -20% auto auto;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.25) 0%, transparent 65%);
    pointer-events: none;
    animation: welcome-download-pulse 5s ease-in-out infinite;
}

@keyframes welcome-download-pulse {
    0%,
    100% {
        opacity: 0.4;
        transform: scale(1) translate(0, 0);
    }
    50% {
        opacity: 0.75;
        transform: scale(1.05) translate(-4px, 4px);
    }
}

.welcome-download-hero__phone {
    position: relative;
    font-size: 1.65rem;
    line-height: 1;
    color: color-mix(in srgb, var(--cdrrmo-100, #e0f2fe) 95%, white);
    filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.18));
}

.welcome-download-hero__text {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    flex: 1 1 160px;
    min-width: 0;
}

.welcome-download-hero__kicker {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.65);
}

.welcome-download-hero__title {
    font-size: 1.05rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.25;
}

.welcome-download-hero__hint {
    font-size: 0.78rem;
    line-height: 1.35;
    color: rgba(255, 255, 255, 0.78);
}

.welcome-download-hero__cta {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    margin-left: auto;
    padding: 0.45rem 0.85rem 0.45rem 1rem;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 800;
    white-space: nowrap;
    background: rgba(255, 255, 255, 0.18);
    border: 1px solid rgba(255, 255, 255, 0.35);
    color: #fff;
    transition: background 0.18s ease;
}

.welcome-download-hero:hover .welcome-download-hero__cta {
    background: rgba(255, 255, 255, 0.28);
}

@media (max-width: 575.98px) {
    .welcome-download-hero__cta {
        width: 100%;
        justify-content: center;
        margin-left: 0;
    }
}

.welcome-stats-wrap {
    position: relative;
    z-index: 3;
    margin-top: -3.25rem;
    padding: 0 1rem 2.5rem;
}

.welcome-app-strip {
    position: relative;
    padding: 3.25rem 1rem;
    background: linear-gradient(
        125deg,
        var(--cdrrmo-800, #075985) 0%,
        var(--cdrrmo-700, #0369a1) 42%,
        color-mix(in srgb, var(--cdrrmo-900, #0c4a6e) 88%, black) 100%
    );
    overflow: hidden;
}

.welcome-app-strip::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 15% 20%, rgba(255, 255, 255, 0.12), transparent 50%),
        radial-gradient(
            ellipse 60% 50% at 90% 80%,
            color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 35%, transparent),
            transparent 55%
        );
    pointer-events: none;
}

.welcome-app-strip .container-xl {
    position: relative;
    z-index: 1;
}

.welcome-app-strip__inner {
    border-radius: 1.35rem;
    padding: 2rem 1.75rem 2.1rem;
    background: color-mix(in srgb, var(--cdrrmo-950, #082f49) 35%, transparent);
    border: 1px solid rgba(255, 255, 255, 0.14);
    backdrop-filter: blur(8px);
}

.welcome-app-strip__badge {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    font-size: 0.68rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.95);
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.welcome-store-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.65rem;
    padding: 0.65rem 1.15rem 0.65rem 0.85rem;
    border-radius: 0.65rem;
    text-decoration: none;
    color: #fff;
    font-weight: 700;
    border: 1px solid rgba(255, 255, 255, 0.22);
    background: rgba(0, 0, 0, 0.22);
    transition:
        transform 0.18s ease,
        background 0.18s ease,
        border-color 0.18s ease;
}

.welcome-store-btn:hover {
    color: #fff;
    transform: translateY(-2px);
    background: rgba(0, 0, 0, 0.35);
    border-color: rgba(255, 255, 255, 0.38);
}

.welcome-store-btn:focus-visible {
    outline: 3px solid rgba(255, 255, 255, 0.55);
    outline-offset: 3px;
}

.welcome-store-btn--play,
.welcome-store-btn--apple {
    min-width: 11.5rem;
}

.welcome-store-btn__icon {
    font-size: 1.65rem;
    line-height: 1;
    flex-shrink: 0;
}

.welcome-store-btn__small {
    display: block;
    font-size: 0.62rem;
    font-weight: 600;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    opacity: 0.82;
    line-height: 1.2;
}

.welcome-store-btn__big {
    display: block;
    font-size: 0.98rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.welcome-stat {
    background: #fff;
    border-radius: 1.15rem;
    padding: 1.35rem 1.5rem;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 12%, transparent);
    box-shadow: 0 18px 45px color-mix(in srgb, var(--cdrrmo-800, #075985) 8%, transparent);
    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease;
}

.welcome-stat:hover {
    transform: translateY(-5px);
}

.welcome-stat__value {
    font-size: 2rem;
    font-weight: 900;
    color: var(--cdrrmo-700, #0369a1);
    letter-spacing: -0.03em;
    line-height: 1;
}

.welcome-stat__label {
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--cdrrmo-900, #0c4a6e);
    margin-top: 0.35rem;
}

.welcome-stat__hint {
    font-size: 0.78rem;
    color: var(--cdrrmo-ink-muted, #64748b);
    margin-top: 0.35rem;
}

.welcome-section {
    position: relative;
    padding: 4rem 1rem;
    z-index: 1;
}

.welcome-section--tilt {
    padding-top: 2rem;
}

.welcome-section-head {
    max-width: 560px;
}

.welcome-h2 {
    font-size: clamp(1.55rem, 2.8vw, 2.15rem);
    font-weight: 900;
    letter-spacing: -0.03em;
    color: var(--cdrrmo-ink, #0c4a6e);
}

.welcome-text {
    font-size: 1.06rem;
    line-height: 1.75;
}

.welcome-card {
    position: relative;
    background: #fff;
    border-radius: 1.25rem;
    padding: 1.75rem 1.75rem 1.85rem;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 10%, transparent);
    box-shadow: 0 14px 40px color-mix(in srgb, var(--cdrrmo-800, #075985) 6%, transparent);
    transition:
        transform 0.3s ease,
        border-color 0.3s ease,
        box-shadow 0.3s ease;
    overflow: hidden;
}

.welcome-card:hover {
    transform: translateY(-8px) rotate(-0.25deg);
    border-color: color-mix(in srgb, var(--cdrrmo-primary, #0284c7) 38%, transparent);
    box-shadow: 0 26px 55px color-mix(in srgb, var(--cdrrmo-800, #075985) 12%, transparent);
}

.welcome-card__num {
    position: absolute;
    top: 0.85rem;
    right: 1rem;
    font-size: 2.5rem;
    font-weight: 900;
    color: color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 35%, transparent);
    line-height: 1;
}

.welcome-card__icon {
    width: 3rem;
    height: 3rem;
    border-radius: 0.85rem;
    background: linear-gradient(
        135deg,
        color-mix(in srgb, var(--cdrrmo-600, #0284c7) 18%, transparent),
        color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 24%, transparent)
    );
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    color: var(--cdrrmo-700, #0369a1);
}

.welcome-section--dark {
    background: linear-gradient(165deg, var(--cdrrmo-950, #082f49) 0%, var(--cdrrmo-800, #075985) 55%, var(--cdrrmo-700, #0369a1) 100%);
    color: #fff;
    padding-top: 4.5rem;
    padding-bottom: 4.5rem;
}

.welcome-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    justify-content: flex-end;
}

.welcome-tab {
    border: 1px solid rgba(255, 255, 255, 0.28);
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.45rem 0.85rem;
    border-radius: 999px;
    cursor: pointer;
    transition:
        background 0.2s ease,
        border-color 0.2s ease,
        color 0.2s ease;
}

.welcome-tab:hover {
    background: rgba(255, 255, 255, 0.12);
}

.welcome-tab--active {
    background: rgba(255, 255, 255, 0.95);
    color: var(--cdrrmo-800, #075985);
    border-color: transparent;
}

.welcome-update-card {
    position: relative;
    background: rgba(255, 255, 255, 0.07);
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 1.15rem;
    padding: 1.25rem 1.35rem 1.15rem;
    backdrop-filter: blur(10px);
    transition:
        transform 0.25s ease,
        border-color 0.25s ease,
        background 0.25s ease;
}

.welcome-update-card:hover {
    transform: translateY(-4px);
    background: rgba(255, 255, 255, 0.11);
    border-color: rgba(255, 255, 255, 0.28);
}

.welcome-update-card__title {
    font-size: 1.05rem;
    font-weight: 800;
    line-height: 1.35;
    margin: 0.5rem 0 0.65rem;
    color: #fff;
}

.welcome-update-card__excerpt {
    font-size: 0.86rem;
    line-height: 1.55;
    color: rgba(255, 255, 255, 0.72);
    margin-bottom: 0;
}

.welcome-update-card__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1rem;
    padding-top: 0.65rem;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.55);
}

.welcome-update-badge {
    display: inline-block;
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    padding: 0.3rem 0.55rem;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.75);
}

.welcome-empty {
    text-align: center;
    padding: 2.5rem 1.5rem;
    border-radius: 1.25rem;
    border: 1px dashed rgba(255, 255, 255, 0.22);
    background: rgba(0, 0, 0, 0.12);
}

.welcome-empty__icon {
    font-size: 2.25rem;
    opacity: 0.45;
    margin-bottom: 0.75rem;
    display: block;
}

.welcome-bento {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 992px) {
    .welcome-bento {
        grid-template-columns: 1.35fr 0.65fr;
        align-items: start;
    }
}

.welcome-bento__main {
    background: #fff;
    border-radius: 1.35rem;
    padding: 2rem 2rem 2.25rem;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 12%, transparent);
    box-shadow: 0 16px 42px color-mix(in srgb, var(--cdrrmo-800, #075985) 7%, transparent);
}

.welcome-code {
    font-size: 0.78rem;
    padding: 0.15rem 0.4rem;
    border-radius: 6px;
    background: var(--cdrrmo-50, #f0f9ff);
    color: var(--cdrrmo-800, #075985);
}

.welcome-code--on-dark {
    background: rgba(255, 255, 255, 0.1);
    color: color-mix(in srgb, var(--cdrrmo-100, #e0f2fe) 92%, white);
    border: 1px solid rgba(255, 255, 255, 0.18);
}

.welcome-mini {
    padding: 1rem 1rem 1.1rem;
    border-radius: 1rem;
    background: var(--cdrrmo-50, #f0f9ff);
    border: 1px solid color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 22%, transparent);
    height: 100%;
}

.welcome-mini i {
    font-size: 1.35rem;
    color: var(--cdrrmo-primary, #0284c7);
    margin-bottom: 0.35rem;
    display: block;
}

.welcome-mini__desc {
    font-size: 0.82rem;
    margin-top: 0.25rem;
    line-height: 1.45;
}

.welcome-bento__aside {
    position: sticky;
    top: 96px;
}

.welcome-aside-card {
    background: linear-gradient(
        160deg,
        color-mix(in srgb, var(--cdrrmo-100, #e0f2fe) 90%, white),
        #fff
    );
    border-radius: 1.35rem;
    padding: 1.5rem 1.5rem 1.65rem;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 14%, transparent);
}

.welcome-aside-card__title {
    font-weight: 900;
    font-size: 1.05rem;
    color: var(--cdrrmo-900, #0c4a6e);
    margin-bottom: 0.5rem;
}

.welcome-app-aside-jump {
    font-weight: 800;
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
    border: none;
    background: linear-gradient(
        135deg,
        var(--cdrrmo-600, #0284c7),
        var(--cdrrmo-700, #0369a1)
    );
    color: #fff !important;
    box-shadow: 0 10px 28px color-mix(in srgb, var(--cdrrmo-800, #075985) 45%, transparent);
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease;
}

.welcome-app-aside-jump:hover {
    color: #fff !important;
    transform: translateY(-2px);
    box-shadow: 0 14px 34px color-mix(in srgb, var(--cdrrmo-900, #0c4a6e) 40%, transparent);
}

.welcome-app-aside-jump:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--cdrrmo-300, #7dd3fc) 70%, white);
    outline-offset: 2px;
}

.welcome-aside-store {
    font-weight: 700;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-500, #0ea5e9) 45%, transparent);
    background: #fff;
    color: var(--cdrrmo-800, #075985) !important;
}

.welcome-aside-store:hover {
    background: var(--cdrrmo-50, #f0f9ff);
    border-color: var(--cdrrmo-500, #0ea5e9);
    color: var(--cdrrmo-900, #0c4a6e) !important;
}

.welcome-cta {
    padding: 5rem 1rem;
    background: radial-gradient(
            circle at 20% 30%,
            color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 28%, transparent),
            transparent 55%
        ),
        linear-gradient(135deg, var(--cdrrmo-800, #075985) 0%, var(--cdrrmo-900, #0c4a6e) 100%);
    position: relative;
    overflow: hidden;
}

.welcome-cta__lead {
    max-width: 34rem;
}

.welcome-btn-outline-light {
    border: 2px solid rgba(255, 255, 255, 0.55);
    color: #fff !important;
    background: transparent;
    font-weight: 800;
}

.welcome-btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.12);
    border-color: #fff;
}

.welcome-footer {
    background: var(--cdrrmo-950, #082f49);
    color: var(--cdrrmo-200, #bae6fd);
    border-top: 1px solid color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 24%, transparent);
}

.welcome-footer__muted {
    color: color-mix(in srgb, var(--cdrrmo-200, #bae6fd) 88%, transparent);
}

.welcome-footer__policy-link {
    color: color-mix(in srgb, var(--cdrrmo-200, #bae6fd) 70%, transparent);
    text-decoration: none;
    white-space: nowrap;
    transition: color 0.2s ease;
}

.welcome-footer__policy-link:hover {
    color: var(--cdrrmo-200, #bae6fd);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.welcome-dots {
    position: fixed;
    right: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    z-index: 90;
    gap: 0.65rem;
}

.welcome-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--cdrrmo-600, #0284c7) 35%, transparent);
    border: 2px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 55%, transparent);
    transition:
        transform 0.2s ease,
        background 0.2s ease;
}

.welcome-dot:hover {
    transform: scale(1.15);
}

.welcome-dot--active {
    background: var(--cdrrmo-primary, #0284c7);
    border-color: var(--cdrrmo-700, #0369a1);
}

/* Scroll reveal */
.welcome-reveal {
    opacity: 0;
    transform: translateY(22px);
    transition:
        opacity 0.65s cubic-bezier(0.22, 1, 0.36, 1),
        transform 0.65s cubic-bezier(0.22, 1, 0.36, 1);
}

.welcome-reveal--visible {
    opacity: 1;
    transform: translateY(0);
}

@media (max-width: 991.98px) {
    .welcome-hero__orbit {
        display: none;
    }

    .welcome-hero-card {
        display: none;
    }
}

@media (max-width: 575.98px) {
    .welcome-nav__inner .welcome-brand__sub {
        display: none;
    }
}

@media (prefers-reduced-motion: reduce) {
    .welcome-hero__glow,
    .welcome-parallax {
        transform: none !important;
    }

    .welcome-orbit {
        animation: none;
    }

    .welcome-hero-card__pulse {
        animation: none;
    }

    .welcome-reveal {
        opacity: 1;
        transform: none;
    }

    .welcome-hero-fade {
        animation: none;
        opacity: 1;
        transform: none;
    }
}
</style>

<style>
html:has(.welcome-root) {
    scroll-behavior: smooth;
}
</style>
