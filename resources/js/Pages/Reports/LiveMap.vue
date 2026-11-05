<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ageMs, isStale, predictPosition } from '@/utils/livePrediction';
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';

const props = defineProps({
    report: Object,
    initial_state: Object,
    paths: Object,
});

const TICK_MS = 500;
const POLL_MS = 5000;
const STATUS_LABELS = {
    pending: 'Waiting for a rescuer',
    en_route: 'Rescuer on the way',
    arrived: 'Rescuer arrived',
    completed: 'Rescue completed',
};
const PEOPLE = [
    { role: 'resident', label: 'Resident', icon: 'person-fill', color: '#dc2626' },
    { role: 'rescuer', label: 'Rescuer', icon: 'truck-front-fill', color: '#0369a1' },
];

const page = usePage();
const canAccessAdmin = computed(() => page.props.auth?.canAccessAdmin === true);

const mapEl = ref(null);
const positions = reactive({
    resident: props.initial_state.resident,
    rescuer: props.initial_state.rescuer,
});
const rescueStatus = ref(props.initial_state.rescue_status);
const reportStatus = ref(props.initial_state.status);
const sessionActive = ref(props.initial_state.session_active);
const live = ref(false);
const reconnecting = ref(false);
const now = ref(Date.now());

/** "Updated Xs ago" must not depend on this computer's clock being right. */
let clockOffsetMs = new Date(props.initial_state.server_time).getTime() - Date.now();

const channelName = `report.${props.report.id}.tracking`;
let L = null;
let map = null;
let tickTimer = null;
let pollTimer = null;
let channel = null;
let connection = null;
let hasFitted = false;
let unmounted = false;
const markers = {};
const lines = {};

const statusLabel = computed(() => STATUS_LABELS[rescueStatus.value] ?? reportStatus.value ?? 'Unknown');

function serverNow() {
    return now.value + clockOffsetMs;
}

function ageLabel(position) {
    const seconds = Math.floor(ageMs(position, serverNow()) / 1000);
    if (seconds < 60) {
        return `Updated ${seconds}s ago`;
    }
    const minutes = Math.floor(seconds / 60);
    return minutes < 60 ? `Updated ${minutes}m ago` : `Updated ${Math.floor(minutes / 60)}h ago`;
}

function stale(position) {
    return isStale(position, serverNow());
}

function markerIcon(person, isStaleNow) {
    const color = isStaleNow ? '#94a3b8' : person.color;
    return L.divIcon({
        className: '',
        html: `<span class="live-marker" style="background:${color}"><i class="bi bi-${person.icon}"></i></span>`,
        iconSize: [36, 36],
        iconAnchor: [18, 18],
    });
}

function renderMarkers() {
    if (!map) {
        return;
    }
    const time = serverNow();

    for (const person of PEOPLE) {
        const position = positions[person.role];
        if (!position) {
            continue;
        }
        const isStaleNow = isStale(position, time);
        const latLng = predictPosition(position, time);
        let marker = markers[person.role];

        if (!marker) {
            marker = L.marker(latLng, { icon: markerIcon(person, isStaleNow) })
                .addTo(map)
                .bindTooltip(person.label);
            marker.staleShown = isStaleNow;
            markers[person.role] = marker;
        } else {
            marker.setLatLng(latLng);
            if (marker.staleShown !== isStaleNow) {
                marker.setIcon(markerIcon(person, isStaleNow));
                marker.staleShown = isStaleNow;
            }
        }
    }
}

function fitOnce() {
    const points = PEOPLE.map((person) => positions[person.role])
        .filter(Boolean)
        .map((position) => [position.lat, position.lng]);

    if (!map || hasFitted || points.length === 0) {
        return;
    }
    hasFitted = true;
    if (points.length === 1) {
        map.setView(points[0], 16);
    } else {
        map.fitBounds(points, { padding: [48, 48], maxZoom: 17 });
    }
}

/** Offline batches arrive late, so never let an older position replace a newer one. */
function applyPosition(role, position) {
    if (!position || !positions.hasOwnProperty(role)) {
        return;
    }
    const current = positions[role];
    if (current && new Date(position.recorded_at) <= new Date(current.recorded_at)) {
        return;
    }
    positions[role] = position;
    lines[role]?.addLatLng([position.lat, position.lng]);
    renderMarkers();
    fitOnce();
}

function onLocationUpdated(event) {
    applyPosition(event.role, event);
}

function onStatusUpdated(event) {
    rescueStatus.value = event.rescue_status;
    sessionActive.value = event.session_active;
}

async function fetchState() {
    try {
        const { data } = await axios.get(route('reports.live.state', props.report.id));
        if (unmounted) {
            return;
        }
        clockOffsetMs = new Date(data.server_time).getTime() - Date.now();
        rescueStatus.value = data.rescue_status;
        reportStatus.value = data.status;
        sessionActive.value = data.session_active;
        applyPosition('resident', data.resident);
        applyPosition('rescuer', data.rescuer);
    } catch {
        /* the next poll tries again */
    }
}

function startPolling() {
    reconnecting.value = true;
    live.value = false;
    if (pollTimer === null) {
        void fetchState();
        pollTimer = window.setInterval(fetchState, POLL_MS);
    }
}

function stopPolling() {
    reconnecting.value = false;
    if (pollTimer !== null) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

function onConnectionChange({ current }) {
    if (current === 'connected') {
        stopPolling();
        live.value = true;
        // Catch up on anything missed while disconnected.
        void fetchState();
    } else {
        startPolling();
    }
}

function connectRealtime() {
    if (!window.Echo) {
        startPolling();
        return;
    }

    channel = window.Echo.private(channelName)
        .listen('.live-location.updated', onLocationUpdated)
        .listen('.rescue-status.updated', onStatusUpdated)
        .error(() => startPolling());

    connection = window.Echo.connector?.pusher?.connection ?? null;
    if (!connection) {
        startPolling();
        return;
    }
    connection.bind('state_change', onConnectionChange);
    onConnectionChange({ current: connection.state });
}

async function setUpMap() {
    const [{ default: leaflet }] = await Promise.all([
        import('leaflet'),
        import('leaflet/dist/leaflet.css'),
    ]);
    if (unmounted || !mapEl.value) {
        return;
    }
    L = leaflet;

    const fallback = [
        Number(props.report.latitude) || 9.0195,
        Number(props.report.longitude) || 126.2331,
    ];
    map = L.map(mapEl.value, { scrollWheelZoom: true, zoomControl: true }).setView(fallback, 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    for (const person of PEOPLE) {
        lines[person.role] = L.polyline(props.paths[person.role] ?? [], {
            color: person.color,
            weight: 4,
            opacity: 0.7,
        }).addTo(map);
    }

    renderMarkers();
    fitOnce();
}

onMounted(() => {
    void setUpMap();
    connectRealtime();
    tickTimer = window.setInterval(() => {
        now.value = Date.now();
        renderMarkers();
    }, TICK_MS);
});

onUnmounted(() => {
    unmounted = true;
    clearInterval(tickTimer);
    stopPolling();
    connection?.unbind('state_change', onConnectionChange);
    if (channel) {
        channel.stopListening('.live-location.updated');
        channel.stopListening('.rescue-status.updated');
        window.Echo?.leave(channelName);
    }
    map?.remove();
    map = null;
});
</script>

<template>
    <Head :title="`Live map - Report #${report.id}`" />

    <AuthenticatedLayout>
        <JHeaderTitle
            :breadcrumb-items="[{ title: `Live rescue map: Report #${report.id}` }]"
        />

        <Link
            v-if="canAccessAdmin"
            :href="route('reports.index', { type: 'All' })"
            class="btn btn-outline-secondary btn-sm mb-3"
        >
            <i class="bi bi-arrow-left me-1"></i>Back to reports
        </Link>

        <div
            v-if="reconnecting"
            class="alert alert-warning py-2 small"
            role="status"
        >
            <i class="bi bi-arrow-repeat me-1"></i>
            Live updates are reconnecting. Checking for new positions every 5 seconds.
        </div>
        <div v-if="!sessionActive" class="alert alert-secondary py-2 small" role="status">
            <i class="bi bi-check-circle me-1"></i>
            This report is closed, so location sharing has ended. The last known positions are shown.
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="card overflow-hidden">
                    <div ref="mapEl" class="live-map"></div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <span class="fw-semibold">{{ statusLabel }}</span>
                            <span v-if="live" class="badge text-bg-success">Live</span>
                        </div>
                        <div v-if="report.reporter" class="small">{{ report.reporter }}</div>
                        <div v-if="report.address" class="small text-muted">{{ report.address }}</div>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2 live-people">
                    <div v-for="person in PEOPLE" :key="person.role" class="card">
                        <div class="card-body d-flex align-items-center gap-3 py-2">
                            <span
                                class="live-marker"
                                :style="{ background: positions[person.role] && !stale(positions[person.role]) ? person.color : '#94a3b8' }"
                                aria-hidden="true"
                            >
                                <i :class="`bi bi-${person.icon}`"></i>
                            </span>
                            <div>
                                <div class="fw-semibold d-flex align-items-center gap-2">
                                    {{ person.label }}
                                    <span
                                        v-if="positions[person.role] && stale(positions[person.role])"
                                        class="badge text-bg-secondary"
                                    >Stale</span>
                                </div>
                                <div class="small text-muted">
                                    <template v-if="positions[person.role]">{{ ageLabel(positions[person.role]) }}</template>
                                    <template v-else>No position yet</template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.live-map {
    width: 100%;
    height: 70vh;
    min-height: 360px;
}

.live-people .live-marker {
    flex-shrink: 0;
}
</style>

<style>
/* Global: Leaflet renders marker HTML outside this component's scope. */
.live-marker {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 2px solid #fff;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.4);
    color: #fff;
    font-size: 1rem;
}
</style>
