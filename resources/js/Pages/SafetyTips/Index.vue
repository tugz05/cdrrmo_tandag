<script setup>
import JCard from '@/Components/JCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { dataTable, toggleOffCanvas } from '@/Helpers/JHelper';
import { Head } from '@inertiajs/vue3';
import { nextTick, onMounted } from 'vue';
import JButton from '@/Components/JButton.vue';
import JDropdownMenu from '@/Components/JDropdownMenu.vue';
import JDropdownItem from '@/Components/JDropdownItem.vue';
import JOffCanvas from '@/Components/JOffCanvas.vue';
import JOffCanvasHeader from '@/Components/JOffCanvasHeader.vue';
defineOptions({ layout: AuthenticatedLayout });


</script>

<template>
    <Head title="Safety Tips" />

    <JHeaderTitle title="Safety Tips" :breadcrumb-items="[{title: 'Safety Tips'}]"/>

    </template>