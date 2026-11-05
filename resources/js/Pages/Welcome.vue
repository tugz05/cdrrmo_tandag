<script setup>
import tagoMap from '@/Assets/tago-map.svg';
import JLogo from '@/Components/JLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

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

const year = new Date().getFullYear();

/** The route follows the Surigao-Davao Coastal Road, in the map's own coordinates. */
const ROUTE = 'M1245 457L1256 490L1268 521L1279 543L1298 572L1329 612L1339 631L1354 645L1383 677L1397 688L1426 720L1455 740L1466 755L1474 779L1474 783L1468 786L1468 787L1494 808L1508 807L1526 815L1555 844L1693 1006L1696 1010L1701 1025';
const VIEW_WIDE = '411 294 1517 892';
const VIEW_NARROW = '822 180 1300 960';
const MIN_COUNT_TO_SHOW = 100;

const mapView = ref(VIEW_WIDE);
let narrowQuery = null;

function syncMapView() {
    mapView.value = narrowQuery.matches ? VIEW_NARROW : VIEW_WIDE;
}

onMounted(() => {
    narrowQuery = window.matchMedia('(max-width: 991.98px)');
    syncMapView();
    narrowQuery.addEventListener('change', syncMapView);
});

onUnmounted(() => {
    narrowQuery?.removeEventListener('change', syncMapView);
});

const TABS = [
    { id: 'news', label: 'News', empty: 'News will appear here when MDRRMO posts it.' },
    { id: 'safety', label: 'Safety tips', empty: 'Safety tips will appear here when MDRRMO posts them.' },
    { id: 'prep', label: 'Preparedness', empty: 'Preparedness guides will appear here when MDRRMO posts them.' },
];
const updatesTab = ref('news');
const activeTab = computed(() => TABS.find((tab) => tab.id === updatesTab.value));

const updatesForTab = computed(() => {
    if (updatesTab.value === 'news') {
        return props.latestNews;
    }
    return updatesTab.value === 'safety' ? props.latestSafetyTips : props.latestPreparedness;
});
const leadStory = computed(() => updatesForTab.value[0] ?? null);
const otherStories = computed(() => updatesForTab.value.slice(1));
const latestNews = computed(() => props.latestNews[0] ?? null);

const downloadUrl = computed(() => props.mobileApp?.android || props.mobileApp?.direct || null);
const reportsShown = computed(() =>
    props.publicStats.reportsRecorded >= MIN_COUNT_TO_SHOW ? props.publicStats.reportsRecorded.toLocaleString() : null,
);

const steps = [
    {
        title: 'Create your account',
        text: 'Sign up in the app once, so your name and number are ready when you need them.',
    },
    {
        title: 'Call or send a report',
        text: 'Call a dispatcher from the app. If you can’t talk, send a message with photos instead.',
    },
    {
        title: 'A dispatcher sees it',
        text: 'An alarm sounds at MDRRMO with your name, phone number and location on screen.',
    },
];

const phases = [
    {
        title: 'Before a disaster',
        text: 'Risk mapping, public education, training and barangay drills, so your household knows what to do.',
    },
    {
        title: 'As a hazard approaches',
        text: 'Official advisories and early warning when weather, routes or risks change.',
    },
    {
        title: 'During and after',
        text: 'Rescue, relief and recovery, so families and livelihoods can get back on their feet.',
    },
];
</script>

<template>
    <Head>
        <title>MDRRMO Tago: call for help, read official advisories</title>
        <meta
            name="description"
            content="Municipal Disaster Risk Reduction and Management Office of Tago, Surigao del Sur. Call for help or send a report from the mobile app, and read official advisories."
        />
        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Schibsted+Grotesk:wght@400;500;600;700&display=swap"
        />
    </Head>

    <div class="welcome-root">
        <a href="#how-it-works" class="skip-link">Skip to main content</a>

        <section class="hero">
            <svg class="hero__map" :viewBox="mapView" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                <image :href="tagoMap" width="2400" height="1519" />
                <path class="hero__route-base" :d="ROUTE" />
                <path class="hero__route" :d="ROUTE" pathLength="1" />
                <circle class="hero__origin" cx="1245" cy="457" r="9" />
                <g class="hero__destination">
                    <circle class="hero__ripple" cx="1701" cy="1025" r="16" />
                    <circle class="hero__pin" cx="1701" cy="1025" r="13" />
                    <text class="hero__label" x="1672" y="1034" text-anchor="end">Your location</text>
                </g>
            </svg>
            <div class="hero__scrim" aria-hidden="true"></div>

            <header class="nav">
                <Link href="/" class="nav__brand">
                    <JLogo size="42px" />
                    <span>MDRRMO Tago</span>
                </Link>
                <nav class="nav__links" aria-label="Page sections">
                    <a href="#how-it-works">How it works</a>
                    <a href="#updates">Updates</a>
                    <a href="#get-the-app">Get the app</a>
                </nav>
                <Link v-if="canLogin" href="/login" class="nav__signin">Sign in</Link>
            </header>

            <div class="hero__body">
                <h1 class="hero__title">
                    <span>Call for help.</span>
                    <span>Your location goes with it.</span>
                </h1>
                <p class="hero__lead">
                    Use the MDRRMO Tago mobile app to call a dispatcher or send a report with photos. They see who and
                    where you are straight away.
                </p>
                <div class="hero__actions">
                    <a v-if="downloadUrl" :href="downloadUrl" class="button button--light">
                        <i class="bi bi-download" aria-hidden="true"></i>Download for Android
                    </a>
                    <a href="#how-it-works" class="hero__secondary">See how it works</a>
                </div>
            </div>

            <small class="hero__credit">Map data &copy; OpenStreetMap contributors</small>
        </section>

        <a v-if="latestNews" :href="latestNews.href" class="latest">
            <span class="latest__label">Latest news</span>
            <span class="latest__title">{{ latestNews.title }}</span>
            <span class="latest__read">Read</span>
        </a>

        <main>
            <section id="how-it-works" class="how">
                <div class="how__copy">
                    <h2 class="heading">From your phone to the dispatcher’s screen</h2>
                    <ol class="steps">
                        <li v-for="step in steps" :key="step.title" class="step">
                            <h3 class="step__title">{{ step.title }}</h3>
                            <p class="step__text">{{ step.text }}</p>
                        </li>
                    </ol>
                    <p v-if="reportsShown" class="how__count">
                        {{ reportsShown }} reports received through this system so far.
                    </p>
                </div>

                <figure class="preview">
                    <div class="alert-card">
                        <div class="alert-card__header">
                            <span class="alert-card__icon" aria-hidden="true">
                                <i class="bi bi-chat-square-text-fill"></i>
                            </span>
                            <div>
                                <div class="alert-card__kind">Emergency message report</div>
                                <div class="alert-card__title">New report received</div>
                            </div>
                        </div>
                        <dl class="alert-card__facts">
                            <dt>Reporter</dt>
                            <dd>Example Resident</dd>
                            <dt>Location</dt>
                            <dd>Brgy. Poblacion, Tago</dd>
                            <dt>Received</dt>
                            <dd>Just now</dd>
                            <dt>Photos</dt>
                            <dd>2 attached</dd>
                        </dl>
                        <p class="alert-card__message">Flood water is entering our house. Two children are with us.</p>
                    </div>
                    <figcaption>An example of the alert a dispatcher sees. The details shown are not a real report.</figcaption>
                </figure>
            </section>

            <section id="updates" class="updates">
                <div class="updates__head">
                    <h2 class="heading">Latest updates</h2>
                    <div class="tabs" role="tablist" aria-label="Update categories">
                        <button
                            v-for="tab in TABS"
                            :key="tab.id"
                            type="button"
                            role="tab"
                            class="tabs__tab"
                            :aria-selected="updatesTab === tab.id"
                            @click="updatesTab = tab.id"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <div v-if="leadStory" class="updates__body" :class="{ 'updates__body--single': otherStories.length === 0 }">
                    <article class="story-lead">
                        <img v-if="leadStory.image" :src="leadStory.image" alt="" class="story-lead__image" loading="lazy" />
                        <h3 class="story-lead__title">
                            <a :href="leadStory.href">{{ leadStory.title }}</a>
                        </h3>
                        <p class="story-lead__excerpt">{{ leadStory.excerpt }}</p>
                    </article>
                    <ul v-if="otherStories.length" class="story-list">
                        <li v-for="story in otherStories" :key="story.id" class="story">
                            <div class="story__text">
                                <h3 class="story__title">
                                    <a :href="story.href">{{ story.title }}</a>
                                </h3>
                                <p class="story__excerpt">{{ story.excerpt }}</p>
                            </div>
                            <img v-if="story.image" :src="story.image" alt="" class="story__image" loading="lazy" />
                        </li>
                    </ul>
                </div>
                <p v-else class="updates__empty">{{ activeTab.empty }}</p>
            </section>

            <section class="phases">
                <h2 class="heading">What MDRRMO does</h2>
                <ol class="phases__list">
                    <li v-for="phase in phases" :key="phase.title" class="phase">
                        <h3 class="phase__title">{{ phase.title }}</h3>
                        <p class="phase__text">{{ phase.text }}</p>
                    </li>
                </ol>
            </section>

            <section id="get-the-app" class="app">
                <img :src="tagoMap" alt="" class="app__map" />
                <div class="app__body">
                    <h2 class="heading heading--light">Install the app before you need it</h2>
                    <p>In an emergency there’s no time to download and sign up. Do it today, and help your family do the same.</p>
                    <div class="app__actions">
                        <a v-if="downloadUrl" :href="downloadUrl" class="button button--light">
                            <i class="bi bi-download" aria-hidden="true"></i>Download for Android
                        </a>
                        <a v-if="mobileApp.ios" :href="mobileApp.ios" class="button button--outline" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-apple" aria-hidden="true"></i>App Store
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <p class="footer__warning">
                In a life-threatening emergency, follow official hotlines and instructions from authorities, not this
                website alone.
            </p>
            <div class="footer__row">
                <span>Municipal Disaster Risk Reduction and Management Office, Tago, Surigao del Sur</span>
                <span class="footer__meta">
                    <Link href="/privacy-policy">Privacy policy</Link>
                    <span>&copy; {{ year }} Municipal Government of Tago</span>
                </span>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.welcome-root {
    --gutter: clamp(1.25rem, 5vw, 5rem);
    --section-space: clamp(4.5rem, 10vw, 9rem);
    --navy: #0a1c58;
    --navy-mid: #2b4481;
    --ink: #0b1a45;
    --muted: #56617f;
    --line: #dde2ee;
    --mist: #f3f5fa;
    --signal: #f4b400;
    background: #fff;
    color: var(--ink);
    font-family: 'Schibsted Grotesk', var(--mdrrmo-font-body);
    font-size: 1.0625rem;
    line-height: 1.55;
    font-variant-numeric: normal;
}

.welcome-root :focus-visible {
    outline: 3px solid var(--navy-mid);
    outline-offset: 3px;
}

.welcome-root :is(.hero, .app, .footer) :focus-visible {
    outline-color: var(--signal);
}

.welcome-root :is(h1, h2, h3) {
    font-family: inherit;
    color: inherit;
}

.welcome-root a {
    color: inherit;
}

.skip-link {
    position: absolute;
    top: -5rem;
    left: 1rem;
    z-index: 10;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    background: #fff;
}

.skip-link:focus {
    top: 0.75rem;
}

/* Type */
.heading {
    max-width: 18ch;
    margin: 0;
    font-size: clamp(2.125rem, 4.2vw, 3.75rem);
    font-weight: 600;
    line-height: 1.04;
    letter-spacing: -0.03em;
    text-wrap: balance;
}

.welcome-root .heading--light {
    color: #fff;
}

/* Buttons */
.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.625rem;
    min-height: 52px;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: background-color 0.15s ease;
}

.welcome-root .button--light {
    background: #fff;
    color: var(--navy);
}

.button--light:hover {
    background: #dfe6f5;
}

.welcome-root .button--outline {
    border: 1px solid rgba(255, 255, 255, 0.5);
    color: #fff;
}

.button--outline:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Hero */
.hero {
    position: relative;
    display: flex;
    flex-direction: column;
    min-height: min(100svh, 62rem);
    overflow: hidden;
    background: var(--navy);
    color: #fff;
}

.hero__map {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}

.hero__scrim {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, var(--navy) 0%, rgba(10, 28, 88, 0.92) 34%, rgba(10, 28, 88, 0) 66%);
}

.hero__route-base {
    fill: none;
    stroke: #fff;
    stroke-width: 11;
    stroke-linecap: round;
    stroke-linejoin: round;
    opacity: 0.1;
}

/* The page's one moment of motion: help travels to the person, then their pin answers. */
.hero__route {
    fill: none;
    stroke: var(--signal);
    stroke-width: 5;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 1;
    animation: route-draw 2.4s 0.3s cubic-bezier(0.45, 0.05, 0.3, 1) both;
}

.hero__origin {
    fill: var(--signal);
}

.hero__pin {
    fill: #fff;
    stroke: var(--signal);
    stroke-width: 6;
}

.hero__ripple {
    fill: none;
    stroke: var(--signal);
    stroke-width: 3;
    transform-box: fill-box;
    transform-origin: center;
    opacity: 0;
    animation: pin-ripple 1.4s 2.6s ease-out 2;
}

.hero__label {
    fill: #fff;
    font-size: 24px;
    font-weight: 600;
}

.hero__destination {
    animation: pin-in 0.4s 2.4s ease-out both;
}

@keyframes route-draw {
    from {
        stroke-dashoffset: 1;
    }
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes pin-in {
    from {
        opacity: 0;
    }
}

@keyframes pin-ripple {
    0% {
        opacity: 0.9;
        transform: scale(1);
    }
    100% {
        opacity: 0;
        transform: scale(4);
    }
}

.nav {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 1.25rem var(--gutter);
}

.nav__brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-right: auto;
    font-size: 1.1875rem;
    font-weight: 600;
    letter-spacing: -0.01em;
    white-space: nowrap;
    text-decoration: none;
}

.nav__links {
    display: flex;
    gap: 2rem;
}

.nav__links a {
    font-size: 1rem;
    font-weight: 500;
    text-decoration: none;
    opacity: 0.86;
}

.nav__links a:hover {
    text-decoration: underline;
    text-underline-offset: 5px;
    opacity: 1;
}

.nav__signin {
    display: inline-flex;
    align-items: center;
    min-height: 44px;
    padding: 0.375rem 1.125rem;
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
}

.nav__signin:hover {
    background: rgba(255, 255, 255, 0.1);
}

.hero__body {
    position: relative;
    z-index: 1;
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: center;
    max-width: calc(46rem + var(--gutter));
    padding: 2rem 0 5rem var(--gutter);
}

.hero__title {
    margin: 0;
    font-size: clamp(3rem, 6.3vw, 6rem);
    font-weight: 600;
    line-height: 1;
    letter-spacing: -0.04em;
}

.hero__title span {
    display: block;
}

.hero__lead {
    max-width: 33rem;
    margin: 1.75rem 0 0;
    font-size: clamp(1.0625rem, 1.5vw, 1.25rem);
    line-height: 1.5;
    color: rgba(255, 255, 255, 0.86);
}

.hero__actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem 1.75rem;
    margin-top: 2.25rem;
}

.hero__secondary {
    font-weight: 600;
    text-underline-offset: 5px;
}

.hero__credit {
    position: absolute;
    right: var(--gutter);
    bottom: 0.75rem;
    z-index: 1;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.72);
}

.hero__credit,
.hero__credit * {
    font-size: 0.75rem;
}

/* Latest news */
.latest {
    display: flex;
    align-items: baseline;
    gap: 1rem 1.5rem;
    padding: 1.125rem var(--gutter);
    background: var(--mist);
    border-bottom: 1px solid var(--line);
    text-decoration: none;
}

.latest__label {
    flex-shrink: 0;
    font-size: 0.9375rem;
    color: var(--muted);
}

.latest__title {
    flex: 1;
    min-width: 0;
    font-weight: 600;
}

.latest__read {
    flex-shrink: 0;
    font-weight: 600;
    color: var(--navy-mid);
    text-decoration: underline;
    text-underline-offset: 4px;
}

.latest:hover .latest__title {
    text-decoration: underline;
    text-underline-offset: 4px;
}

/* How it works */
.how {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: clamp(2.5rem, 6vw, 6rem);
    align-items: center;
    padding: var(--section-space) var(--gutter);
    scroll-margin-top: 1rem;
}

.steps {
    margin: clamp(2rem, 4vw, 3rem) 0 0;
    padding: 0;
    list-style: none;
    counter-reset: step;
}

.step {
    position: relative;
    padding: 0 0 1.75rem 3.25rem;
    counter-increment: step;
}

.step::before {
    content: counter(step);
    position: absolute;
    top: 0;
    left: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 50%;
    background: var(--navy);
    font-size: 0.9375rem;
    font-weight: 600;
    color: #fff;
}

/* The line joins the steps because they happen in this order. */
.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 2.25rem;
    bottom: 0.25rem;
    left: calc(1rem - 1px);
    width: 2px;
    background: var(--line);
}

.step:last-child {
    padding-bottom: 0;
}

.step__title {
    margin: 0;
    padding-top: 0.125rem;
    font-size: 1.3125rem;
    font-weight: 600;
    letter-spacing: -0.015em;
}

.step__text {
    max-width: 30rem;
    margin: 0.375rem 0 0;
    color: var(--muted);
}

.how__count {
    margin: 2rem 0 0;
    color: var(--muted);
}

.preview {
    margin: 0;
    padding: clamp(1.5rem, 5vw, 4.5rem) clamp(1.25rem, 4vw, 3.5rem) clamp(1.25rem, 3vw, 2rem);
    border-radius: 20px;
    background: var(--mist);
}

.preview figcaption {
    margin-top: 1.5rem;
    font-size: 0.9375rem;
    text-align: center;
    color: var(--muted);
}

.alert-card {
    max-width: 28rem;
    margin: 0 auto;
    overflow: hidden;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 1px 2px rgba(11, 26, 69, 0.08), 0 24px 48px -16px rgba(11, 26, 69, 0.28);
}

.alert-card__header {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    padding: 1rem 1.25rem;
    background: var(--mdrrmo-alarm);
    color: #fff;
}

.alert-card__icon {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    font-size: 1.125rem;
}

.alert-card__kind {
    font-size: 0.875rem;
}

.alert-card__title {
    font-size: 1.25rem;
    font-weight: 600;
    line-height: 1.2;
    letter-spacing: -0.01em;
}

.alert-card__facts {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 0.5rem 1.5rem;
    margin: 0;
    padding: 1.25rem 1.25rem 0;
    font-size: 1rem;
}

.alert-card__facts dt {
    font-weight: 400;
    color: var(--muted);
}

.alert-card__facts dd {
    margin: 0;
    font-weight: 500;
}

.alert-card__message {
    margin: 1.125rem 1.25rem 1.25rem;
    padding: 0.75rem 1rem;
    border-left: 3px solid var(--mdrrmo-alarm);
    border-radius: 4px;
    background: #fdf2f2;
    font-size: 1rem;
}

/* Updates */
.updates {
    padding: var(--section-space) var(--gutter);
    background: var(--mist);
    scroll-margin-top: 1rem;
}

.updates__head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1.5rem 3rem;
    margin-bottom: clamp(2rem, 4vw, 3.5rem);
}

.tabs {
    display: flex;
    gap: 0.25rem;
    max-width: 100%;
    padding: 0.25rem;
    overflow-x: auto;
    border-radius: 10px;
    background: #e3e8f4;
    scrollbar-width: none;
}

.tabs__tab {
    flex: 0 0 auto;
    min-height: 44px;
    padding: 0.375rem 1.125rem;
    border: 0;
    border-radius: 7px;
    background: transparent;
    font-size: 1rem;
    font-weight: 500;
    color: var(--muted);
}

.tabs__tab:hover {
    color: var(--ink);
}

.tabs__tab[aria-selected='true'] {
    background: #fff;
    font-weight: 600;
    color: var(--ink);
    box-shadow: 0 1px 2px rgba(11, 26, 69, 0.12);
}

.updates__body {
    display: grid;
    grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
    gap: clamp(2rem, 5vw, 5rem);
    align-items: start;
}

.updates__body--single {
    grid-template-columns: minmax(0, 46rem);
}

.story-lead {
    position: relative;
}

.story-lead__image {
    display: block;
    width: 100%;
    aspect-ratio: 16 / 9;
    margin-bottom: 1.5rem;
    border-radius: 14px;
    object-fit: cover;
    background: #dfe6f5;
}

.story-lead__title {
    max-width: 24ch;
    margin: 0;
    font-size: clamp(1.75rem, 3vw, 2.625rem);
    font-weight: 600;
    line-height: 1.1;
    letter-spacing: -0.025em;
}

.story-lead__title a,
.story__title a {
    text-decoration: none;
}

.story-lead__title a::after,
.story__title a::after {
    content: '';
    position: absolute;
    inset: 0;
}

.story-lead:hover .story-lead__title a,
.story:hover .story__title a {
    text-decoration: underline;
    text-decoration-thickness: 2px;
    text-underline-offset: 4px;
}

.story-lead__excerpt {
    max-width: 38rem;
    margin: 0.875rem 0 0;
    color: var(--muted);
}

.story-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.story {
    position: relative;
    display: flex;
    gap: 1.25rem;
    justify-content: space-between;
    padding: 1.5rem 0;
    border-top: 1px solid #d3daea;
}

.story:first-child {
    padding-top: 0;
    border-top: 0;
}

.story__title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    line-height: 1.25;
    letter-spacing: -0.015em;
}

.story__excerpt {
    display: -webkit-box;
    margin: 0.375rem 0 0;
    overflow: hidden;
    font-size: 1rem;
    color: var(--muted);
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.story__image {
    flex-shrink: 0;
    width: 6.5rem;
    height: 6.5rem;
    border-radius: 10px;
    object-fit: cover;
    background: #dfe6f5;
}

.updates__empty {
    margin: 0;
    padding: 3rem 0;
    color: var(--muted);
}

/* What MDRRMO does: a disaster runs in this order, so the rule reads as a timeline. */
.phases {
    padding: var(--section-space) var(--gutter);
}

.phases__list {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: clamp(1.5rem, 4vw, 4rem);
    margin: clamp(2.5rem, 5vw, 4rem) 0 0;
    padding: 0;
    list-style: none;
}

.phase {
    padding-top: 1.5rem;
    border-top: 2px solid var(--navy);
}

.phase__title {
    margin: 0;
    font-size: clamp(1.375rem, 2vw, 1.75rem);
    font-weight: 600;
    line-height: 1.15;
    letter-spacing: -0.02em;
}

.phase__text {
    max-width: 26rem;
    margin: 0.75rem 0 0;
    color: var(--muted);
}

/* App */
.app {
    position: relative;
    margin: 0 var(--gutter) var(--section-space);
    overflow: hidden;
    border-radius: 20px;
    background: var(--navy);
    color: rgba(255, 255, 255, 0.86);
    scroll-margin-top: 1rem;
}

.app__map {
    position: absolute;
    top: 0;
    right: 0;
    width: 70%;
    height: 100%;
    object-fit: cover;
    object-position: 75% 40%;
    -webkit-mask-image: linear-gradient(90deg, transparent, #000 60%);
    mask-image: linear-gradient(90deg, transparent, #000 60%);
}

.app__body {
    position: relative;
    max-width: 40rem;
    padding: clamp(2.5rem, 7vw, 6rem) clamp(1.5rem, 6vw, 5.5rem);
}

.app__body p {
    max-width: 30rem;
    margin: 1.25rem 0 0;
    font-size: clamp(1.0625rem, 1.5vw, 1.1875rem);
}

.app__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 2rem;
}

/* Footer */
.footer {
    padding: clamp(2.5rem, 5vw, 4rem) var(--gutter) 2rem;
    background: #06123b;
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.9375rem;
}

.footer__warning {
    max-width: 40rem;
    margin: 0 0 2.5rem;
    font-size: clamp(1.1875rem, 2vw, 1.5rem);
    font-weight: 500;
    line-height: 1.3;
    letter-spacing: -0.015em;
    color: #fff;
}

.footer__row {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.75rem 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.16);
}

.footer__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1.5rem;
}

@media (max-width: 991.98px) {
    .how,
    .updates__body {
        grid-template-columns: minmax(0, 1fr);
    }

    .nav__links {
        display: none;
    }

    /* The map takes the top of the hero and the copy sits below it, so the route stays clear. */
    .hero {
        min-height: max(100svh, 56rem);
    }

    .hero__map {
        height: 52%;
    }

    .hero__scrim {
        background: linear-gradient(0deg, var(--navy) 0%, var(--navy) 48%, rgba(10, 28, 88, 0) 53%);
    }

    .hero__body {
        justify-content: flex-end;
        padding-right: var(--gutter);
        padding-bottom: 2.75rem;
    }

    .hero__title {
        font-size: clamp(2.75rem, 12.5vw, 5rem);
    }

    .hero__label {
        font-size: 30px;
    }

    .hero__route {
        stroke-width: 7;
    }

    .hero__pin {
        r: 18px;
    }

    .hero__origin {
        r: 13px;
    }
}

@media (max-width: 767.98px) {
    .hero__actions .button {
        width: 100%;
    }

    .latest {
        flex-wrap: wrap;
    }

    .latest__label {
        width: 100%;
    }

    .latest__title {
        flex: 1 1 60%;
    }

    .phases__list {
        grid-template-columns: minmax(0, 1fr);
    }

    .app__map {
        width: 100%;
        opacity: 0.45;
        -webkit-mask-image: none;
        mask-image: none;
    }

    .app__actions .button {
        width: 100%;
    }

    .footer__row {
        flex-direction: column;
    }
}

@media (prefers-reduced-motion: reduce) {
    .hero__route,
    .hero__destination,
    .hero__ripple {
        animation: none;
    }

    .button {
        transition: none;
    }
}
</style>

<style>
html:has(.welcome-root) {
    scroll-behavior: smooth;
}

@media (prefers-reduced-motion: reduce) {
    html:has(.welcome-root) {
        scroll-behavior: auto;
    }
}
</style>
