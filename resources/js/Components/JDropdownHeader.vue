<script setup>
defineProps({
    text: String
})
</script>

<template>
    <li><h6 class="dropdown-header">{{ text }}</h6></li>
</template>