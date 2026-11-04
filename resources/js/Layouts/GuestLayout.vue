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
                <h1 class="auth-shell__brand-title">MDRRMO</h1>
                <p class="auth-shell__brand-sub">Municipality of Tago</p>
                <p class="auth-shell__brand-lead">
                    Advisories, preparedness, and community reporting—stay informed with official updates from your municipality
                    disaster risk reduction office.
                </p>
                <ul class="auth-shell__bullets">
                    <li>
                        <i class="bi bi-shield-check" aria-hidden="true" />
                        Trusted advisories and safety guidance
                    </li>
                    <li>
                        <i class="bi bi-phone" aria-hidden="true" />
                        Same account on the web and mobile app
                    </li>
                    <li>
                        <i class="bi bi-geo-alt" aria-hidden="true" />
                        Local focus for Tago residents
                    </li>
                </ul>
            </div>
        </div>

        <div class="auth-shell__main">
            <div class="auth-shell__content">
                <div class="auth-shell__mobile-brand">
                    <JLogo size="44px" />
                    <div class="auth-shell__mobile-brand-text">
                        <strong>MDRRMO</strong>
                        <span>Municipality of Tago</span>
                    </div>
                </div>

                <Link href="/" class="auth-shell__back">
                    <i class="bi bi-arrow-left" aria-hidden="true" />
                    Back to home
                </Link>

                <div class="auth-card">
                    <slot />
                </div>

                <p class="auth-shell__fine text-center" style="color: var(--mdrrmo-ink-muted, #64748b)">
                    © {{ year }} Municipal Government of Tago · Disaster Risk Reduction and Management
                </p>
            </div>
        </div>
    </div>
</template>
