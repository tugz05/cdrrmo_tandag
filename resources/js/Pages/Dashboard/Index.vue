<script setup>
import PagePrintBar from '@/Components/PagePrintBar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import ApexCharts from 'apexcharts';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    year: { type: Number, required: true },
    years: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    heatmap_points: { type: Array, default: () => [] },
    monthly_chart: {
        type: Object,
        default: () => ({ labels: [], counts: [] }),
    },
});

const page = usePage();

const localYear = ref(props.year);
const mapEl = ref(null);
let mapInstance = null;
let heatLayer = null;
const chartInstances = [];

const chartChannelRef = ref(null);
const chartStatusDonutRef = ref(null);
const chartTypeDonutRef = ref(null);
const chartMonthlyRef = ref(null);

const statusStats = computed(() => props.stats?.status ?? {});
const typeStats = computed(() => props.stats?.type ?? {});

const kpis = computed(() => {
    const s = statusStats.value;

    return [
        {
            title: 'Total reports',
            value: s.all ?? 0,
            hint: `Year ${props.year}`,
            tone: 'primary',
            icon: 'clipboard2-pulse',
            iconBg: 'rgba(2, 132, 199, 0.1)',
            iconColor: '#0284c7',
            accentColor: '#0284c7',
        },
        {
            title: 'Pending',
            value: s.pending ?? 0,
            hint: 'Awaiting dispatch',
            tone: 'warning',
            icon: 'hourglass-split',
            iconBg: 'rgba(217, 119, 6, 0.1)',
            iconColor: '#d97706',
            accentColor: '#f59e0b',
        },
        {
            title: 'In progress',
            value: s.in_progress ?? 0,
            hint: 'Active response',
            tone: 'info',
            icon: 'arrow-clockwise',
            iconBg: 'rgba(8, 145, 178, 0.1)',
            iconColor: '#0891b2',
            accentColor: '#06b6d4',
        },
        {
            title: 'Rescued',
            value: s.rescued ?? 0,
            hint: 'Closed successfully',
            tone: 'success',
            icon: 'check-circle-fill',
            iconBg: 'rgba(22, 163, 74, 0.1)',
            iconColor: '#16a34a',
            accentColor: '#22c55e',
        },
    ];
});

watch(
    () => props.year,
    (y) => {
        localYear.value = y;
    }
);

function changeYear() {
    router.get(
        route('dashboard'),
        { year: localYear.value },
        { preserveScroll: true, replace: true }
    );
}

const apexCommon = {
    chart: {
        fontFamily: 'system-ui, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
        toolbar: { show: false },
        zoom: { enabled: false },
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    grid: { borderColor: '#e2e8f0', strokeDashArray: 4 },
    legend: { fontSize: '13px' },
    theme: { mode: 'light' },
    colors: ['#0284c7', '#0ea5e9', '#0369a1', '#38bdf8'],
};

function destroyCharts() {
    chartInstances.forEach((c) => {
        try {
            c.destroy();
        } catch {
            //
        }
    });
    chartInstances.length = 0;
}

function mountCharts() {
    const t = typeStats.value;
    const messages = t.messages ?? 0;
    const calls = t.calls ?? 0;

    if (chartChannelRef.value) {
        const ch = new ApexCharts(chartChannelRef.value, {
            ...apexCommon,
            chart: { ...apexCommon.chart, type: 'bar', height: 320 },
            series: [{ name: 'Reports', data: [messages, calls] }],
            plotOptions: {
                bar: { borderRadius: 8, columnWidth: '46%', distributed: true },
            },
            xaxis: {
                categories: ['Messages', 'Calls'],
                labels: { style: { fontSize: '13px' } },
            },
            yaxis: { labels: { style: { fontSize: '12px' } } },
            title: {
                text: 'Volume by channel',
                align: 'left',
                style: { fontSize: '15px', fontWeight: 600, color: '#0f172a' },
            },
            colors: ['#0284c7', '#0ea5e9'],
        });
        ch.render();
        chartInstances.push(ch);
    }

    const s = statusStats.value;
    if (chartStatusDonutRef.value) {
        const pending = s.pending ?? 0;
        const prog = s.in_progress ?? 0;
        const rescued = s.rescued ?? 0;
        const d1 = new ApexCharts(chartStatusDonutRef.value, {
            ...apexCommon,
            chart: { ...apexCommon.chart, type: 'donut', height: 320 },
            labels: ['Pending', 'In progress', 'Rescued'],
            series: [pending, prog, rescued],
            title: {
                text: 'Status mix',
                align: 'left',
                style: { fontSize: '15px', fontWeight: 600, color: '#0f172a' },
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Active queue',
                                formatter: () =>
                                    String((pending + prog + rescued).toLocaleString()),
                            },
                        },
                    },
                },
            },
            colors: ['#0369a1', '#f59e0b', '#16a34a'],
        });
        d1.render();
        chartInstances.push(d1);
    }

    if (chartTypeDonutRef.value) {
        const d2 = new ApexCharts(chartTypeDonutRef.value, {
            ...apexCommon,
            chart: { ...apexCommon.chart, type: 'donut', height: 320 },
            labels: ['Messages', 'Calls'],
            series: [messages, calls],
            title: {
                text: 'Channel share',
                align: 'left',
                style: { fontSize: '15px', fontWeight: 600, color: '#0f172a' },
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '68%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: () =>
                                    String((messages + calls).toLocaleString()),
                            },
                        },
                    },
                },
            },
            colors: ['#0284c7', '#7dd3fc'],
        });
        d2.render();
        chartInstances.push(d2);
    }

    const mc = props.monthly_chart ?? { labels: [], counts: [] };
    if (chartMonthlyRef.value) {
        const line = new ApexCharts(chartMonthlyRef.value, {
            ...apexCommon,
            chart: { ...apexCommon.chart, type: 'area', height: 340 },
            series: [{ name: 'Reports', data: mc.counts ?? [] }],
            xaxis: {
                categories: mc.labels ?? [],
                labels: { style: { fontSize: '12px' } },
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 0.45,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100],
                },
            },
            title: {
                text: `Monthly intake (${props.year})`,
                align: 'left',
                style: { fontSize: '15px', fontWeight: 600, color: '#0f172a' },
            },
            colors: ['#0284c7'],
        });
        line.render();
        chartInstances.push(line);
    }
}

async function mountHeatmap() {
    if (!mapEl.value) {
        return;
    }

    const L = (await import('leaflet')).default;
    await import('leaflet/dist/leaflet.css');
    window.L = L;
    await import('leaflet.heat/dist/leaflet-heat.js');

    const pts = props.heatmap_points ?? [];
    const center = [9.07245, 126.19356];
    const zoom = pts.length ? 11 : 10;

    mapInstance = L.map(mapEl.value, {
        scrollWheelZoom: false,
        zoomControl: true,
    }).setView(center, zoom);

    /**
     * Realistic satellite base (Esri World Imagery) + reference labels for place names.
     * Streets (OSM) available as alternate base in the layer control.
     */
    const satellite = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom: 19,
            attribution:
                'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community',
        }
    );

    const streets = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    });

    const labels = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
        {
            maxZoom: 19,
            pane: 'overlayPane',
            opacity: 0.92,
            attribution: '&copy; Esri',
        }
    );

    satellite.addTo(mapInstance);
    labels.addTo(mapInstance);

    L.control
        .layers(
            {
                'Satellite (Esri)': satellite,
                'Streets (OSM)': streets,
            },
            {},
            { collapsed: false, position: 'topright' }
        )
        .addTo(mapInstance);

    if (pts.length) {
        const maxW = Math.max(...pts.map((p) => p[2] ?? 1), 1);
        heatLayer = L.heatLayer(pts, {
            radius: 26,
            blur: 20,
            maxZoom: 17,
            max: maxW,
            minOpacity: 0.4,
            gradient: {
                0.2: '#38bdf8',
                0.45: '#22d3ee',
                0.65: '#fbbf24',
                0.85: '#fb923c',
                1: '#ef4444',
            },
        }).addTo(mapInstance);

        try {
            const bounds = L.latLngBounds(pts.map((p) => [p[0], p[1]]));
            mapInstance.fitBounds(bounds.pad(0.12));
        } catch {
            //
        }
    }
}

function teardownMap() {
    if (mapInstance) {
        mapInstance.remove();
        mapInstance = null;
        heatLayer = null;
    }
}

onMounted(async () => {
    await nextTick();
    destroyCharts();
    mountCharts();
    await mountHeatmap();
});

onBeforeUnmount(() => {
    destroyCharts();
    teardownMap();
});

watch(
    () => props.year,
    async () => {
        await nextTick();
        destroyCharts();
        mountCharts();
        teardownMap();
        await nextTick();
        await mountHeatmap();
    }
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="print-only border-bottom pb-2 mb-4">
        <div class="fw-bold fs-5 text-dark">CDRRMO — Operations dashboard</div>
        <div class="small text-muted mt-1">
            Reporting year <strong>{{ year }}</strong>
            <span v-if="page.props.auth?.user?.name"> — Prepared for {{ page.props.auth.user.name }}</span>
        </div>
        <div class="small text-muted mt-1">{{ new Date().toLocaleString() }}</div>
    </div>

    <div class="no-print">
        <JHeaderTitle title="Dashboard" :breadcrumb-items="[{ title: 'Dashboard' }]" />
    </div>

    <div class="no-print d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <PagePrintBar label="Print Report Summary" />
        <div class="d-flex align-items-center gap-2">
            <label class="form-label m-0 small text-muted" for="dash-year">Reporting year</label>
            <select
                id="dash-year"
                v-model.number="localYear"
                class="form-select form-select-sm"
                style="min-width: 7rem"
                @change="changeYear"
            >
                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>
    </div>

    <div class="print-root">
        <div class="row g-3 g-lg-4 mb-4">
            <div v-for="(k, i) in kpis" :key="i" class="col-6 col-xl-3">
                <div
                    class="kpi-card rounded-4 shadow-sm h-100 p-3 p-lg-4 position-relative overflow-hidden"
                    :style="{
                        background: 'linear-gradient(135deg, #ffffff 0%, var(--cdrrmo-50) 100%)',
                        borderTop: `3px solid ${k.accentColor}`,
                    }"
                >
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="small text-uppercase text-muted fw-semibold letter-spacing" style="font-size: 0.68rem !important; padding-top: 2px">
                            {{ k.title }}
                        </div>
                        <div
                            class="kpi-icon"
                            :style="{ background: k.iconBg, width: '2.35rem', height: '2.35rem' }"
                        >
                            <i :class="`bi bi-${k.icon}`" :style="{ color: k.iconColor, fontSize: '1rem' }"></i>
                        </div>
                    </div>
                    <div class="display-6 fw-bold mt-1" style="color: var(--cdrrmo-800)">
                        {{ k.value.toLocaleString() }}
                    </div>
                    <div class="small text-muted mt-2">{{ k.hint }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3 g-lg-4 mb-4">
            <div class="col-lg-6">
                <div class="rounded-4 bg-white shadow-sm border p-3 p-lg-4 h-100">
                    <div ref="chartChannelRef" class="w-100" />
                </div>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="rounded-4 bg-white shadow-sm border p-2 h-100">
                            <div ref="chartStatusDonutRef" class="w-100" />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="rounded-4 bg-white shadow-sm border p-2 h-100">
                            <div ref="chartTypeDonutRef" class="w-100" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-4 bg-white shadow-sm border p-3 p-lg-4 mb-4">
            <div ref="chartMonthlyRef" class="w-100" />
        </div>

        <div class="rounded-4 bg-white shadow-sm border p-3 p-lg-4 mb-2">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                    <h2 class="h5 fw-semibold mb-1">Incident concentration (heatmap)</h2>
                    <p class="small text-muted mb-0">
                        Satellite basemap with incident density overlay for {{ year }}. Warmer colors = more reports in that area.
                    </p>
                </div>
            </div>
            <div
                ref="mapEl"
                class="rounded-3 border"
                style="min-height: 440px; z-index: 1"
                role="presentation"
            />
            <p v-if="!heatmap_points.length" class="small text-muted mt-2 mb-0">
                No geotagged reports for this year yet. Locations appear when citizen reports include coordinates.
            </p>
        </div>

        <p class="print-only small text-muted border-top pt-3 mt-4 mb-0">
            End of report — map and charts reflect data at time of printing. Use layer control on screen to switch basemap.
        </p>
    </div>
</template>
