<script setup>
import JCard from '@/Components/JCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import ApexCharts from 'apexcharts';
import { computed, nextTick, onMounted } from 'vue';
import CardItem from './Partials/CardItem.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const statusStats = computed(() => props.stats?.status ?? {});
const typeStats = computed(() => props.stats?.type ?? {});

const items = computed(() => {
    const s = statusStats.value;

    return [
        { title: 'Total', count: s.all ?? 0 },
        { title: 'Pending', count: s.pending ?? 0 },
        { title: 'In Progress', count: s.in_progress ?? 0 },
        { title: 'Rescued', count: s.rescued ?? 0 },
    ];
});

onMounted(async () => {
    await nextTick();

    const t = typeStats.value;
    const messages = t.messages ?? 0;
    const calls = t.calls ?? 0;

    const options = {
        title: {
            text: 'Reports by channel',
        },
        chart: {
            height: '400px',
            type: 'bar',
        },
        series: [
            {
                name: 'Count',
                data: [messages, calls],
            },
        ],
        xaxis: {
            categories: ['Messages', 'Calls'],
        },
    };

    const el = document.querySelector('#chart');
    if (el) {
        const chart = new ApexCharts(el, options);
        chart.render();
    }

    const s = statusStats.value;
    const options2 = {
        series: [
            {
                name: 'Reports',
                data: [s.pending ?? 0, s.in_progress ?? 0, s.rescued ?? 0],
            },
        ],
        chart: {
            type: 'area',
            height: 350,
            zoom: {
                enabled: false,
            },
        },
        title: {
            text: 'Reports by status (active queue)',
            align: 'left',
        },
        xaxis: {
            categories: ['Pending', 'In progress', 'Rescued'],
        },
    };

    const el2 = document.querySelector('#chart2');
    if (el2) {
        const chart2 = new ApexCharts(el2, options2);
        chart2.render();
    }
});
</script>

<template>
    <Head title="Dashboard" />
    <JHeaderTitle title="Dashboard" :breadcrumb-items="[{ title: 'Dashboard' }]" />

    <div class="mb-10 d-flex gap-3 align-items-center">
        <label for="year">Select Year</label>
        <select id="year" class="form-select" style="max-width: 300px">
            <option value="2025">2025</option>
        </select>
    </div>

    <div class="row g-5 mb-16 mt-5">
        <CardItem
            v-for="(item, i) in items"
            :key="i"
            :title="item.title"
            :count="item.count"
        />
    </div>

    <div id="chart" />

    <div class="my-8" />

    <div id="chart2" />

    <div class="mt-10" style="height: 450px">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d8951.459930290917!2d126.19356012964299!3d9.072451954515648!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1732154794212!5m2!1sen!2sph"
            width="100%"
            height="450"
            style="border: 0"
            allowfullscreen
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
        />
    </div>
</template>
