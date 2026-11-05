<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { isStale, predictPosition, updatedAgoLabel } from '@/utils/livePrediction';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import '../../../css/live-marker.css';

const props = defineProps({
    initial_state: Object,
});

const TICK_MS = 1000;
const POLL_LIVE_MS = 15000;
const POLL_OFFLINE_MS = 5000;
const REFRESH_DELAY_MS = 500;
const CHANNEL = 'admin.live-map';
const FALLBACK_CENTER = [9.0195, 126.2331];
const GRAY = '#94a3b8';
const INCIDENT_COLOR = '#c81e1e';
const RESCUER_STATES = {
    en_route: { label: 'En route', color: '#b45309', badge: 'text-bg-warning' },
    arrived: { label: 'On scene', color: '#15803d', badge: 'text-bg-success' },
    available: { label: 'Available', color: '#2b4481', badge: 'text-bg-primary' },
    no_location: { label: 'No location', color: GRAY, badge: 'text-bg-secondary' },
};
const INCIDENT_LABELS = {
    en_route: 'Rescuer on the way',
    arrived: 'Rescuer on scene',
};

const mapEl = ref(null);
const rescuers = ref(props.initial_state.rescuers);
const incidents = ref(props.initial_state.incidents);
const incidentsTotal = ref(props.initial_state.incidents_total);
const recentDays = ref(props.initial_state.recent_days);
const live = ref(false);
const reconnecting = ref(false);
const selected = ref(null);
const now = ref(Date.now());

/** "Updated Xs ago" must not depend on this computer's clock being right. */
let clockOffsetMs = new Date(props.initial_state.server_time).getTime() - Date.now();

let L = null;
let map = null;
let tickTimer = null;
let pollTimer = null;
let refreshTimer = null;
let channel = null;
let alertsChannel = null;
let connection = null;
let hasFitted = false;
let unmounted = false;
let fetching = false;
const markers = new Map();
const lines = new Map();

function serverNow() {
    return now.value + clockOffsetMs;
}

function stale(position) {
    return isStale(position, serverNow());
}

function ageLabel(position) {
    return updatedAgoLabel(position, serverNow());
}

function rescuerState(rescuer) {
    const rescue = rescuer.assignment?.rescue_status;
    if (rescue) {
        return RESCUER_STATES[rescue];
    }
    return rescuer.position ? RESCUER_STATES.available : RESCUER_STATES.no_location;
}

function incidentLabel(incident) {
    return INCIDENT_LABELS[incident.rescue_status] ?? 'Waiting for a rescuer';
}

/** The resident's shared position when there is one, otherwise where the report was sent from. */
function incidentPoint(incident) {
    return incident.resident ?? incident.reported_location;
}

function incidentAt(id) {
    return incidents.value.find((incident) => incident.id === id);
}

const counts = computed(() => {
    const byLabel = (state) => rescuers.value.filter((rescuer) => rescuerState(rescuer) === state).length;
    return {
        enRoute: byLabel(RESCUER_STATES.en_route),
        arrived: byLabel(RESCUER_STATES.arrived),
        available: byLabel(RESCUER_STATES.available),
        noLocation: byLabel(RESCUER_STATES.no_location),
        waiting: incidents.value.filter((incident) => !INCIDENT_LABELS[incident.rescue_status]).length,
    };
});
const hiddenIncidents = computed(() => Math.max(incidentsTotal.value - incidents.value.length, 0));
const noRescuerSharing = computed(() => rescuers.value.length > 0 && rescuers.value.every((rescuer) => !rescuer.position));

function markerIcon(color, icon, isSelected) {
    const selectedClass = isSelected ? ' live-marker--selected' : '';
    return L.divIcon({
        className: '',
        html: `<span class="live-marker${selectedClass}" style="background:${color}"><i class="bi bi-${icon}"></i></span>`,
        iconSize: [36, 36],
        iconAnchor: [18, 18],
    });
}

/** Names and addresses come from users, so tooltips get a text node and never parsed HTML. */
function tooltipContent(title, detail) {
    const element = document.createElement('div');
    const strong = document.createElement('strong');
    strong.textContent = title;
    element.append(strong);
    if (detail) {
        const line = document.createElement('div');
        line.textContent = detail;
        element.append(line);
    }
    return element;
}

function drawMarker(key, latLng, color, icon, title, detail, seen) {
    seen.add(key);
    const isSelected = selected.value === key;
    const iconKey = `${color}|${icon}|${isSelected}`;
    let marker = markers.get(key);

    if (!marker) {
        marker = L.marker(latLng, { icon: markerIcon(color, icon, isSelected), keyboard: true, title })
            .addTo(map)
            .bindTooltip(tooltipContent(title, detail))
            .on('click', () => select(key, false));
        marker.iconKey = iconKey;
        marker.detail = detail;
        markers.set(key, marker);
        return;
    }

    marker.setLatLng(latLng);
    if (marker.iconKey !== iconKey) {
        marker.setIcon(markerIcon(color, icon, isSelected));
        marker.iconKey = iconKey;
    }
    if (marker.detail !== detail) {
        marker.setTooltipContent(tooltipContent(title, detail));
        marker.detail = detail;
    }
}

function drawLine(rescuer, from, seen) {
    const incident = rescuer.assignment ? incidentAt(rescuer.assignment.report_id) : null;
    const point = incident ? incidentPoint(incident) : null;
    if (!point) {
        return;
    }

    const key = `rescuer:${rescuer.id}`;
    const to = incident.resident ? predictPosition(incident.resident, serverNow()) : [point.lat, point.lng];
    seen.add(key);

    const line = lines.get(key);
    if (line) {
        line.setLatLngs([from, to]);
    } else {
        lines.set(key, L.polyline([from, to], { color: '#b45309', weight: 2, opacity: 0.7, dashArray: '6 8' }).addTo(map));
    }
}

function removeUnseen(layers, seen) {
    for (const [key, layer] of layers) {
        if (!seen.has(key)) {
            layer.remove();
            layers.delete(key);
        }
    }
}

function renderMap() {
    if (!map) {
        return;
    }
    const time = serverNow();
    const seenMarkers = new Set();
    const seenLines = new Set();

    for (const incident of incidents.value) {
        const point = incidentPoint(incident);
        if (point) {
            const latLng = incident.resident ? predictPosition(incident.resident, time) : [point.lat, point.lng];
            drawMarker(
                `incident:${incident.id}`,
                latLng,
                INCIDENT_COLOR,
                incident.resident ? 'person-fill' : 'exclamation-triangle-fill',
                `Report #${incident.id}: ${incident.reporter || 'Unknown'}`,
                incidentLabel(incident),
                seenMarkers,
            );
        }
    }

    for (const rescuer of rescuers.value) {
        if (rescuer.position) {
            const state = rescuerState(rescuer);
            const latLng = predictPosition(rescuer.position, time);
            const isStaleNow = isStale(rescuer.position, time);
            drawMarker(
                `rescuer:${rescuer.id}`,
                latLng,
                isStaleNow ? GRAY : state.color,
                'truck-front-fill',
                rescuer.name,
                isStaleNow ? `${state.label}, last position may be old` : state.label,
                seenMarkers,
            );
            drawLine(rescuer, latLng, seenLines);
        }
    }

    removeUnseen(markers, seenMarkers);
    removeUnseen(lines, seenLines);
}

function allPoints() {
    return [
        ...incidents.value.map(incidentPoint),
        ...rescuers.value.map((rescuer) => rescuer.position),
    ]
        .filter(Boolean)
        .map((point) => [point.lat, point.lng]);
}

function fitAll() {
    const points = allPoints();
    if (!map || points.length === 0) {
        return;
    }
    hasFitted = true;
    if (points.length === 1) {
        map.setView(points[0], 16);
    } else {
        map.fitBounds(points, { padding: [48, 48], maxZoom: 16 });
    }
}

function fitOnce() {
    if (!hasFitted) {
        fitAll();
    }
}

function select(key, moveMap = true) {
    selected.value = key;
    renderMap();
    const marker = markers.get(key);
    if (marker) {
        if (moveMap) {
            map.setView(marker.getLatLng(), Math.max(map.getZoom(), 15));
        }
        marker.openTooltip();
    }
    if (!moveMap) {
        document.getElementById(`live-row-${key.replace(':', '-')}`)?.scrollIntoView({ block: 'nearest' });
    }
}

/** Offline batches arrive late, so never let an older position replace a newer one. */
function newerPosition(current, incoming) {
    if (!current) {
        return incoming;
    }
    if (!incoming) {
        return current;
    }
    return new Date(incoming.recorded_at) >= new Date(current.recorded_at) ? incoming : current;
}

function applyState(data) {
    clockOffsetMs = new Date(data.server_time).getTime() - Date.now();

    const oldRescuers = new Map(rescuers.value.map((rescuer) => [rescuer.id, rescuer]));
    const oldIncidents = new Map(incidents.value.map((incident) => [incident.id, incident]));

    rescuers.value = data.rescuers.map((rescuer) => ({
        ...rescuer,
        position: newerPosition(oldRescuers.get(rescuer.id)?.position, rescuer.position),
    }));
    incidents.value = data.incidents.map((incident) => ({
        ...incident,
        resident: newerPosition(oldIncidents.get(incident.id)?.resident, incident.resident),
    }));
    incidentsTotal.value = data.incidents_total;
    recentDays.value = data.recent_days;

    renderMap();
    fitOnce();
}

async function fetchState() {
    if (fetching) {
        return;
    }
    fetching = true;
    try {
        const { data } = await axios.get(route('live-map.state'));
        if (!unmounted) {
            applyState(data);
        }
    } catch {
        /* the next poll tries again */
    } finally {
        fetching = false;
    }
}

/** Several events can land together, for example when a rescue starts. */
function queueRefresh() {
    if (refreshTimer === null) {
        refreshTimer = window.setTimeout(() => {
            refreshTimer = null;
            void fetchState();
        }, REFRESH_DELAY_MS);
    }
}

function onLocationUpdated(event) {
    const list = event.role === 'resident' ? incidents.value : rescuers.value;
    const id = event.role === 'resident' ? event.report_id : event.user_id;
    const field = event.role === 'resident' ? 'resident' : 'position';
    const item = list.find((entry) => entry.id === id);

    if (!item) {
        queueRefresh();
        return;
    }
    item[field] = newerPosition(item[field], event);
    renderMap();
    fitOnce();
}

function setPolling(intervalMs) {
    if (pollTimer !== null) {
        clearInterval(pollTimer);
    }
    pollTimer = window.setInterval(fetchState, intervalMs);
}

function goOffline() {
    live.value = false;
    reconnecting.value = true;
    setPolling(POLL_OFFLINE_MS);
}

function onConnectionChange({ current }) {
    if (current === 'connected') {
        live.value = true;
        reconnecting.value = false;
        setPolling(POLL_LIVE_MS);
        // Catch up on anything missed while disconnected.
        void fetchState();
    } else {
        goOffline();
    }
}

function connectRealtime() {
    if (!window.Echo) {
        goOffline();
        return;
    }

    channel = window.Echo.private(CHANNEL)
        .listen('.live-location.updated', onLocationUpdated)
        .listen('.rescue-status.updated', queueRefresh)
        .error(goOffline);

    // MessageReportAlert shares this channel, so only this listener is removed on leaving.
    alertsChannel = window.Echo.private('admin.reports').listen('.message-report.received', queueRefresh);

    connection = window.Echo.connector?.pusher?.connection ?? null;
    if (!connection) {
        goOffline();
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

    map = L.map(mapEl.value, { scrollWheelZoom: true, zoomControl: true }).setView(FALLBACK_CENTER, 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    renderMap();
    fitOnce();
}

onMounted(() => {
    void setUpMap();
    connectRealtime();
    tickTimer = window.setInterval(() => {
        now.value = Date.now();
        renderMap();
    }, TICK_MS);
});

onUnmounted(() => {
    unmounted = true;
    clearInterval(tickTimer);
    clearInterval(pollTimer);
    clearTimeout(refreshTimer);
    connection?.unbind('state_change', onConnectionChange);
    if (channel) {
        channel.stopListening('.live-location.updated');
        channel.stopListening('.rescue-status.updated');
        window.Echo?.leave(CHANNEL);
    }
    alertsChannel?.stopListening('.message-report.received', queueRefresh);
    map?.remove();
    map = null;
});
</script>

<template>
    <Head title="Live Map" />

    <AuthenticatedLayout>
        <JHeaderTitle :breadcrumb-items="[{ title: 'Live Map' }]" />

        <div v-if="reconnecting" class="alert alert-warning py-2 small" role="status">
            <i class="bi bi-arrow-repeat me-1"></i>
            Live updates are reconnecting. Checking for new positions every 5 seconds.
        </div>
        <div v-if="noRescuerSharing" class="alert alert-info py-2 small" role="status">
            <i class="bi bi-info-circle me-1"></i>
            No rescuer is sharing a location yet. Rescuers appear on the map once the mobile app sends their position.
        </div>

        <div class="row g-2 mb-3">
            <div class="col-4 col-xl-2">
                <div class="card h-100"><div class="card-body live-stat">
                    <div class="small text-muted">Open incidents</div>
                    <div class="fs-4 fw-semibold">{{ incidentsTotal }}</div>
                </div></div>
            </div>
            <div class="col-4 col-xl-2">
                <div class="card h-100"><div class="card-body live-stat">
                    <div class="small text-muted">Waiting for a rescuer</div>
                    <div class="fs-4 fw-semibold text-danger">{{ counts.waiting }}</div>
                </div></div>
            </div>
            <div class="col-4 col-xl-2">
                <div class="card h-100"><div class="card-body live-stat">
                    <div class="small text-muted">En route</div>
                    <div class="fs-4 fw-semibold">{{ counts.enRoute }}</div>
                </div></div>
            </div>
            <div class="col-4 col-xl-2">
                <div class="card h-100"><div class="card-body live-stat">
                    <div class="small text-muted">On scene</div>
                    <div class="fs-4 fw-semibold">{{ counts.arrived }}</div>
                </div></div>
            </div>
            <div class="col-4 col-xl-2">
                <div class="card h-100"><div class="card-body live-stat">
                    <div class="small text-muted">Available</div>
                    <div class="fs-4 fw-semibold">{{ counts.available }}</div>
                </div></div>
            </div>
            <div class="col-4 col-xl-2">
                <div class="card h-100"><div class="card-body live-stat">
                    <div class="small text-muted">No location</div>
                    <div class="fs-4 fw-semibold">{{ counts.noLocation }}</div>
                </div></div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-8">
                <div class="card overflow-hidden">
                    <div class="d-flex flex-wrap align-items-center gap-3 px-3 py-2 border-bottom small">
                        <span v-if="live" class="badge text-bg-success">Live</span>
                        <span><i class="bi bi-exclamation-triangle-fill text-danger me-1"></i>Reported location</span>
                        <span><i class="bi bi-person-fill text-danger me-1"></i>Resident sharing</span>
                        <span><i class="bi bi-truck-front-fill me-1" style="color: #b45309"></i>En route</span>
                        <span><i class="bi bi-truck-front-fill me-1" style="color: #15803d"></i>On scene</span>
                        <span><i class="bi bi-truck-front-fill me-1" style="color: #2b4481"></i>Available</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm ms-auto" @click="fitAll">
                            <i class="bi bi-arrows-fullscreen me-1"></i>Show all
                        </button>
                    </div>
                    <div ref="mapEl" class="live-overview-map"></div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Rescuers ({{ rescuers.length }})</div>
                    <div class="list-group list-group-flush live-overview-list">
                        <div v-if="rescuers.length === 0" class="list-group-item small text-muted">
                            No field staff accounts yet.
                        </div>
                        <div
                            v-for="rescuer in rescuers"
                            :id="`live-row-rescuer-${rescuer.id}`"
                            :key="rescuer.id"
                            class="list-group-item d-flex align-items-start gap-2"
                            :class="{ 'list-group-item-primary': selected === `rescuer:${rescuer.id}` }"
                        >
                            <button
                                type="button"
                                class="btn btn-link text-start text-decoration-none text-body p-0 flex-fill"
                                :disabled="!rescuer.position"
                                @click="select(`rescuer:${rescuer.id}`)"
                            >
                                <span class="fw-semibold d-block">{{ rescuer.name }}</span>
                                <span class="d-flex flex-wrap align-items-center gap-1 small">
                                    <span class="badge" :class="rescuerState(rescuer).badge">{{ rescuerState(rescuer).label }}</span>
                                    <span v-if="rescuer.position && stale(rescuer.position)" class="badge text-bg-secondary">Stale</span>
                                    <span v-if="rescuer.assignment" class="text-muted">Report #{{ rescuer.assignment.report_id }}</span>
                                </span>
                                <span v-if="rescuer.position" class="small text-muted d-block">{{ ageLabel(rescuer.position) }}</span>
                            </button>
                            <a v-if="rescuer.phone" :href="`tel:${rescuer.phone}`" class="btn btn-outline-secondary btn-sm" :aria-label="`Call ${rescuer.name}`">
                                <i class="bi bi-telephone"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header fw-semibold">Open incidents ({{ incidentsTotal }})</div>
                    <div class="list-group list-group-flush live-overview-list">
                        <div v-if="incidents.length === 0" class="list-group-item small text-muted">
                            No open incidents in the last {{ recentDays }} days.
                        </div>
                        <div
                            v-for="incident in incidents"
                            :id="`live-row-incident-${incident.id}`"
                            :key="incident.id"
                            class="list-group-item d-flex align-items-start gap-2"
                            :class="{ 'list-group-item-primary': selected === `incident:${incident.id}` }"
                        >
                            <button
                                type="button"
                                class="btn btn-link text-start text-decoration-none text-body p-0 flex-fill"
                                :disabled="!incidentPoint(incident)"
                                @click="select(`incident:${incident.id}`)"
                            >
                                <span class="fw-semibold d-block">#{{ incident.id }} {{ incident.reporter || 'Unknown' }}</span>
                                <span class="small d-block">{{ incidentLabel(incident) }}</span>
                                <span v-if="incident.address" class="small text-muted d-block">{{ incident.address }}</span>
                                <span v-if="!incidentPoint(incident)" class="small text-danger d-block">No location on this report</span>
                                <span v-if="incident.resident" class="small text-muted d-block">
                                    Resident {{ ageLabel(incident.resident).toLowerCase() }}
                                </span>
                            </button>
                            <Link :href="route('reports.live', incident.id)" class="btn btn-outline-secondary btn-sm" :aria-label="`Open map for report ${incident.id}`">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </Link>
                        </div>
                        <div v-if="hiddenIncidents" class="list-group-item small text-muted">
                            {{ hiddenIncidents }} more not shown. See Reports for the full list.
                        </div>
                    </div>
                    <div class="card-footer small text-muted">
                        Open reports from the last {{ recentDays }} days, plus any rescue under way.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.live-overview-map {
    width: 100%;
    height: 70vh;
    min-height: 360px;
}

.live-stat {
    padding: 0.5rem 0.75rem;
}

.live-overview-list {
    max-height: 34vh;
    overflow-y: auto;
}

@media (max-width: 767.98px) {
    .live-overview-map {
        height: 58vh;
    }

    .live-stat {
        padding: 0.375rem 0.5rem;
    }

    .live-stat .small {
        font-size: 0.75rem;
        line-height: 1.15;
    }
}
</style>
