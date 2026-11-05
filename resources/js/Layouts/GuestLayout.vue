<script setup>
import JLogo from '@/Components/JLogo.vue';
import { Link } from '@inertiajs/vue3';

const year = new Date().getFullYear();
</script>

<template>
    <div class="auth-shell font-sans antialiased">
        <div class="auth-shell__brand">
            <div class="auth-shell__brand-inner">
                <JLogo size="64px" />
                <div class="auth-shell__brand-title">MDRRMO Tago</div>
                <p class="auth-shell__brand-sub">Surigao del Sur</p>
                <p class="auth-shell__brand-lead">
                    Report emergencies, read official advisories, and prepare your household with the Municipal Disaster Risk
                    Reduction and Management Office.
                </p>
                <ul class="auth-shell__bullets">
                    <li>
                        <i class="bi bi-shield-check" aria-hidden="true" />
                        Official advisories and safety guidance
                    </li>
                    <li>
                        <i class="bi bi-phone" aria-hidden="true" />
                        Same account on the web and mobile app
                    </li>
                    <li>
                        <i class="bi bi-geo-alt" aria-hidden="true" />
                        Made for the people of Tago
                    </li>
                </ul>
            </div>
        </div>

        <div class="auth-shell__main">
            <div class="auth-shell__content">
                <div class="auth-shell__mobile-brand">
                    <JLogo size="44px" />
                    <div class="auth-shell__mobile-brand-text">
                        <strong>MDRRMO Tago</strong>
                        <span>Surigao del Sur</span>
                    </div>
                </div>

                <Link href="/" class="auth-shell__back">
                    <i class="bi bi-arrow-left" aria-hidden="true" />
                    Back to home
                </Link>

                <div class="auth-card">
                    <slot />
                </div>

                <p class="auth-shell__fine text-center" style="color: var(--mdrrmo-ink-muted, #56617f)">
                    © {{ year }} Municipal Government of Tago
                </p>
            </div>
        </div>
    </div>
</template>
