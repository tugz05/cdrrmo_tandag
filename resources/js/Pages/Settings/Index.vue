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
    <Head title="Settings" />
  <JHeaderTitle title="Settings" :breadcrumb-items="[{title: 'Settings' }]"/>

    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
          <a class="nav-link active" id="simple-tab-0" data-bs-toggle="tab" href="#simple-tabpanel-0" role="tab" aria-controls="simple-tabpanel-0" aria-selected="true">About</a>
        </li>
        <li class="nav-item" role="presentation">
          <a class="nav-link" id="simple-tab-1" data-bs-toggle="tab" href="#simple-tabpanel-1" role="tab" aria-controls="simple-tabpanel-1" aria-selected="false">Privacy</a>
        </li>
        <li class="nav-item" role="presentation">
          <a class="nav-link" id="simple-tab-2" data-bs-toggle="tab" href="#simple-tabpanel-2" role="tab" aria-controls="simple-tabpanel-2" aria-selected="false">Terms and Conditions</a>
        </li>
      </ul>
      <div class="tab-content pt-5" id="tab-content">
        <div class="tab-pane active" id="simple-tabpanel-0" role="tabpanel" aria-labelledby="simple-tab-0">Tab 1 selected</div>
        <div class="tab-pane" id="simple-tabpanel-1" role="tabpanel" aria-labelledby="simple-tab-1">Tab 2 selected</div>
        <div class="tab-pane" id="simple-tabpanel-2" role="tabpanel" aria-labelledby="simple-tab-2">Tab 3 selected</div>
      </div>
    
</template>