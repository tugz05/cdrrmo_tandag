<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <footer class="app-footer">
        <Link :href="route('privacy-policy')" class="app-footer__link">Privacy policy</Link>
        <div class="d-flex align-items-center gap-2">
            <img src="../Partials/nemsu-logo.png" alt="" height="24" width="auto" />
            <span>Developed with NEMSU</span>
        </div>
    </footer>
</template>

<style scoped>
.app-footer {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 1rem 0;
    border-top: 1px solid var(--mdrrmo-line);
    font-size: 0.875rem;
    color: var(--mdrrmo-ink-muted);
}

.app-footer__link {
    color: inherit;
}
</style>
