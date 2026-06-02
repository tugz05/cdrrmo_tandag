<script setup>
import JLogo from '@/Components/JLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

const year = new Date().getFullYear();
const effectiveDate = 'June 2, 2026';
</script>

<template>
    <Head>
        <title>Privacy Policy — CDRRMO Tandag</title>
        <meta
            name="description"
            content="Privacy policy for the CDRRMO Tandag mobile app and website — how we collect, use, and protect your information."
        />
    </Head>

    <div class="pp-root font-sans antialiased">

        <!-- Nav -->
        <header class="welcome-nav">
            <div class="welcome-nav__inner container-xl">
                <Link href="/" class="welcome-brand">
                    <JLogo size="42px" />
                    <span class="welcome-brand__text">
                        <span class="welcome-brand__title">CDRRMO</span>
                        <span class="welcome-brand__sub">City of Tandag</span>
                    </span>
                </Link>
                <Link href="/" class="pp-back-link">
                    <i class="bi bi-arrow-left me-1" aria-hidden="true" />
                    Back to home
                </Link>
            </div>
        </header>

        <!-- Hero -->
        <section class="pp-hero">
            <div class="container-xl pp-hero__inner">
                <p class="welcome-eyebrow">CDRRMO · City of Tandag</p>
                <h1 class="pp-hero__title">Privacy Policy</h1>
                <p class="pp-hero__meta">Effective {{ effectiveDate }}</p>
            </div>
            <div class="pp-hero__slant" aria-hidden="true" />
        </section>

        <!-- Content -->
        <main class="pp-content-wrap">
            <div class="container-xl">
                <article class="pp-content">

                    <section class="pp-section">
                        <h2 class="pp-h2">Introduction</h2>
                        <p>The City Disaster Risk Reduction and Management Office (CDRRMO) of Tandag City ("we", "our", or "CDRRMO") operates this website and the CDRRMO Tandag mobile application (the "App"). This Privacy Policy explains how we collect, use, and protect information when you use the App or visit this website.</p>
                        <p>By using the App or this website, you agree to the collection and use of information as described in this policy.</p>
                    </section>

                    <section class="pp-section">
                        <h2 class="pp-h2">Information We Collect</h2>

                        <h3 class="pp-h3">Account Information</h3>
                        <p>When you create an account, we collect your <strong>full name</strong>, <strong>email address</strong>, and <strong>phone number</strong>. This information is used to identify you as a registered community member and to communicate with you about your reports and advisories.</p>

                        <h3 class="pp-h3">Location Data</h3>
                        <p>When you submit an incident report through the App, we request access to your device's location (GPS coordinates) to associate the report with a geographic area. Location access is requested only at the time of submitting a report and only with your explicit permission. We do not track your location in the background.</p>

                        <h3 class="pp-h3">Incident Reports and Media</h3>
                        <p>Reports you submit may include text descriptions and photos or other media files you attach. This content becomes part of the official CDRRMO Tandag incident record and is used by CDRRMO staff to assess, respond to, and document emergencies and community concerns.</p>

                        <h3 class="pp-h3">Device Identifiers</h3>
                        <p>To deliver push notifications (such as disaster alerts and advisories), the App collects your device's push notification token, device type, and operating system version. This information is used solely to route notifications to your device.</p>
                    </section>

                    <section class="pp-section">
                        <h2 class="pp-h2">How We Use Your Information</h2>
                        <ul class="pp-list">
                            <li>To receive, process, and respond to incident reports you submit.</li>
                            <li>To send you official disaster alerts, advisories, and updates.</li>
                            <li>To contact you regarding the status of your submitted reports.</li>
                            <li>To maintain official CDRRMO Tandag records as required by local government regulations.</li>
                            <li>To improve the reliability and features of the App and website.</li>
                        </ul>
                    </section>

                    <section class="pp-section">
                        <h2 class="pp-h2">Information Sharing</h2>
                        <p>We do not sell, trade, or rent your personal information to third parties. Your information may be shared only in the following circumstances:</p>
                        <ul class="pp-list">
                            <li><strong>CDRRMO staff:</strong> Authorized personnel of the City Government of Tandag who need access to process reports and coordinate emergency response.</li>
                            <li><strong>Service providers:</strong> Third-party services required to operate the platform (for example, push notification delivery services). These providers are permitted to use your data only to perform services on our behalf.</li>
                            <li><strong>Legal obligations:</strong> When required by law or by lawful order of a government authority.</li>
                        </ul>
                    </section>

                    <section class="pp-section">
                        <h2 class="pp-h2">Data Retention</h2>
                        <p>Incident reports and related records are retained as part of official CDRRMO Tandag documentation in accordance with applicable local government retention policies. Account information is retained for as long as your account is active. You may request deletion of your account at any time by contacting us (see below).</p>
                    </section>

                    <section class="pp-section">
                        <h2 class="pp-h2">Your Rights</h2>
                        <p>You have the right to:</p>
                        <ul class="pp-list">
                            <li>Access the personal information we hold about you.</li>
                            <li>Request correction of inaccurate information.</li>
                            <li>Request deletion of your account and associated personal data (subject to official records retention requirements).</li>
                        </ul>
                        <p>To exercise any of these rights, contact us using the information in the <em>Contact</em> section below.</p>
                    </section>

                    <section class="pp-section">
                        <h2 class="pp-h2">Security</h2>
                        <p>We implement reasonable technical and organizational measures to protect your personal information against unauthorized access, disclosure, alteration, or destruction. However, no method of transmission over the internet or method of electronic storage is 100% secure.</p>
                    </section>

                    <section class="pp-section">
                        <h2 class="pp-h2">Changes to This Policy</h2>
                        <p>We may update this Privacy Policy from time to time. When we do, we will update the "Effective" date at the top of this page and, where appropriate, notify users through the App. Your continued use of the App or website after changes are posted constitutes acceptance of the updated policy.</p>
                    </section>

                    <section class="pp-section">
                        <h2 class="pp-h2">Contact</h2>
                        <p>If you have questions or requests regarding this Privacy Policy, please contact:</p>
                        <address class="pp-address">
                            <strong>CDRRMO — City of Tandag</strong><br>
                            City Government of Tandag, Surigao del Sur, Philippines<br>
                            Email: <a href="mailto:cdrrmo@tandag.gov.ph" class="pp-link">cdrrmo@tandag.gov.ph</a>
                        </address>
                    </section>

                </article>
            </div>
        </main>

        <!-- Footer -->
        <footer class="welcome-footer">
            <div class="container-xl d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 py-4">
                <div class="d-flex align-items-center gap-2 small welcome-footer__muted">
                    <JLogo size="28px" />
                    <span>&copy; {{ year }} CDRRMO · City Government of Tandag</span>
                </div>
                <div class="small welcome-footer__muted text-md-end">
                    Life-threatening emergency? Follow official hotlines and instructions from authorities—not this website alone.
                </div>
            </div>
        </footer>

    </div>
</template>

<style scoped>
.pp-root {
    min-height: 100vh;
    background: var(--cdrrmo-surface, #f0f9ff);
    color: var(--cdrrmo-ink, #0c4a6e);
}

.pp-back-link {
    display: inline-flex;
    align-items: center;
    font-size: 0.88rem;
    font-weight: 700;
    text-decoration: none;
    color: var(--cdrrmo-700, #0369a1);
    padding: 0.4rem 0.85rem;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-700, #0369a1) 22%, transparent);
    background: var(--cdrrmo-surface-raised, #fff);
    transition: border-color 0.2s ease, color 0.2s ease;
}

.pp-back-link:hover {
    border-color: var(--cdrrmo-primary, #0284c7);
    color: var(--cdrrmo-700, #0369a1);
}

.pp-hero {
    position: relative;
    padding-bottom: 0;
    background: linear-gradient(
        155deg,
        var(--cdrrmo-900, #0c4a6e) 0%,
        var(--cdrrmo-700, #0369a1) 38%,
        var(--cdrrmo-500, #0ea5e9) 100%
    );
    color: #fff;
}

.pp-hero__inner {
    padding: 3.5rem 1rem 4rem;
}

.pp-hero__title {
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 900;
    letter-spacing: -0.03em;
    line-height: 1.1;
    margin: 0.5rem 0 0.75rem;
}

.pp-hero__meta {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.72);
    margin: 0;
}

.pp-hero__slant {
    height: 60px;
    background: var(--cdrrmo-surface, #f0f9ff);
    clip-path: polygon(0 48%, 100% 0, 100% 100%, 0 100%);
    margin-top: -1px;
}

.pp-content-wrap {
    padding: 2.5rem 1rem 5rem;
}

.pp-content {
    max-width: 680px;
    margin: 0 auto;
    background: #fff;
    border-radius: 1.35rem;
    padding: 2.5rem 2.5rem 3rem;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 12%, transparent);
    box-shadow: 0 16px 42px color-mix(in srgb, var(--cdrrmo-800, #075985) 7%, transparent);
}

.pp-section {
    margin-bottom: 2.25rem;
}

.pp-section:last-child {
    margin-bottom: 0;
}

.pp-h2 {
    font-size: 1.2rem;
    font-weight: 900;
    color: var(--cdrrmo-900, #0c4a6e);
    margin-bottom: 0.65rem;
    padding-bottom: 0.4rem;
    border-bottom: 2px solid color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 35%, transparent);
}

.pp-h3 {
    font-size: 1rem;
    font-weight: 800;
    color: var(--cdrrmo-800, #075985);
    margin-top: 1.15rem;
    margin-bottom: 0.4rem;
}

.pp-content p,
.pp-content address {
    font-size: 0.95rem;
    line-height: 1.75;
    color: #334155;
    margin-bottom: 0.85rem;
}

.pp-content p:last-child {
    margin-bottom: 0;
}

.pp-list {
    font-size: 0.95rem;
    line-height: 1.75;
    color: #334155;
    padding-left: 1.35rem;
    margin-bottom: 0.85rem;
}

.pp-list li {
    margin-bottom: 0.4rem;
}

.pp-address {
    font-style: normal;
    background: var(--cdrrmo-50, #f0f9ff);
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    border: 1px solid color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 25%, transparent);
}

.pp-link {
    color: var(--cdrrmo-700, #0369a1);
    font-weight: 600;
}

.pp-link:hover {
    color: var(--cdrrmo-900, #0c4a6e);
}

@media (max-width: 575.98px) {
    .pp-content {
        padding: 1.5rem 1.25rem 2rem;
    }
}
</style>

<style>
/* welcome-nav, welcome-brand, welcome-footer are scoped in Welcome.vue and won't
   apply here. Re-declare them scoped under .pp-root to avoid polluting global styles. */
.pp-root .welcome-nav {
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(16px);
    background: color-mix(in srgb, var(--cdrrmo-surface, #f0f9ff) 88%, white);
    border-bottom: 1px solid color-mix(in srgb, var(--cdrrmo-600, #0284c7) 16%, transparent);
}

.pp-root .welcome-nav__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.65rem 1rem;
}

.pp-root .welcome-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
}

.pp-root .welcome-brand__title {
    font-weight: 800;
    letter-spacing: 0.04em;
    display: block;
    line-height: 1.15;
}

.pp-root .welcome-brand__sub {
    font-size: 0.72rem;
    color: var(--cdrrmo-ink-muted, #64748b);
    display: block;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.pp-root .welcome-footer {
    background: var(--cdrrmo-950, #082f49);
    color: var(--cdrrmo-200, #bae6fd);
    border-top: 1px solid color-mix(in srgb, var(--cdrrmo-400, #38bdf8) 24%, transparent);
}

.pp-root .welcome-footer__muted {
    color: color-mix(in srgb, var(--cdrrmo-200, #bae6fd) 88%, transparent);
}

.pp-root .welcome-eyebrow {
    text-transform: uppercase;
    letter-spacing: 0.2em;
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.58);
    margin-bottom: 1rem;
}
</style>
