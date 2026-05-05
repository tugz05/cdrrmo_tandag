<template>
    <footer class="app-footer border-top bg-body">
        <div
            class="footer-inner d-flex flex-wrap justify-content-between align-items-center gap-3 text-muted py-4 px-1"
        >
            <nav class="d-flex flex-wrap gap-3 align-items-center" aria-label="Footer links">
                <a href="#" class="app-footer__link text-decoration-none text-uppercase">About</a>
                <a href="#" class="app-footer__link text-decoration-none text-uppercase">Privacy</a>
                <a href="#" class="app-footer__link text-decoration-none text-uppercase">Terms</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <img src="../Partials/nemsu-logo.png" alt="NEMSU" height="28" width="auto" class="opacity-90" />
                <div>
                    <p class="m-0 text-uppercase letter-spacing-tight small text-secondary">Credit</p>
                    <p class="m-0 fw-semibold small text-body-secondary">NEMSU</p>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.app-footer {
    --app-footer-link: var(--cdrrmo-ink-muted, #64748b);
}

.app-footer__link {
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    color: var(--app-footer-link);
}

.app-footer__link:hover {
    color: var(--cdrrmo-primary, #0284c7);
}

.letter-spacing-tight {
    letter-spacing: 0.04em;
    font-size: 0.65rem;
}
</style>