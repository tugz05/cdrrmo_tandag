<script>
import { ref } from 'vue';

/** Module scope so unacknowledged alerts survive the layout remounting on Inertia navigation. */
const queue = ref([]);
</script>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Real-time alert for message reports from the mobile app.
 * Listens on the private `admin.reports` channel (Laravel Reverb + Echo) and opens a modal
 * that stays until an operator acknowledges it. Mounted by AuthenticatedLayout for admins only.
 */

const MODAL_ID = 'modal-message-report-alert';
const EVENT_NAME = '.message-report.received';

const current = computed(() => queue.value[0] ?? null);
/** Keeps the last report on screen while the modal fades out after the final acknowledge. */
const lastShown = ref(null);
const shown = computed(() => current.value ?? lastShown.value);
watch(current, (r) => {
    if (r) {
        lastShown.value = r;
    }
}, { immediate: true });
const remaining = computed(() => Math.max(queue.value.length - 1, 0));

let channel = null;
let alarmTimer = null;
let audioContext = null;
let titleFlashTimer = null;
let savedTitle = '';
let browserNotification = null;

function modalInstance() {
    const el = document.getElementById(MODAL_ID);
    if (!el || typeof bootstrap === 'undefined') {
        return null;
    }
    return bootstrap.Modal.getOrCreateInstance(el, { backdrop: 'static', keyboard: false });
}

function onReportReceived(report) {
    if (queue.value.some((r) => r.id === report.id)) {
        return;
    }
    queue.value.push(report);
    if (queue.value.length === 1) {
        openAlert();
    } else {
        notifyBrowser(report);
    }
}

function openAlert() {
    if (!current.value) {
        return;
    }
    nextTick(() => modalInstance()?.show());
    startAlarm();
    startTitleFlash();
    notifyBrowser(current.value);
    try {
        navigator.vibrate?.([300, 150, 300]);
    } catch {
        /* ignore */
    }
}

function closeAlert() {
    stopAlarm();
    stopTitleFlash();
    try {
        browserNotification?.close?.();
    } catch {
        /* ignore */
    }
    browserNotification = null;
    modalInstance()?.hide();
}

function acknowledge() {
    queue.value.shift();
    if (queue.value.length) {
        notifyBrowser(current.value);
        return;
    }
    closeAlert();
}

function viewReports() {
    queue.value = [];
    closeAlert();
    router.visit(route('reports.index', { type: 'Message', status: 'Pending' }));
}

function openMap() {
    const r = current.value;
    if (!r?.latitude || !r?.longitude) {
        return;
    }
    window.open(`https://www.google.com/maps?q=${r.latitude},${r.longitude}`, '_blank', 'noopener');
}

/** Two-tone siren via Web Audio — no asset needed. Browsers allow it after the operator's first click on the page. */
function playSirenOnce() {
    try {
        audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
        if (audioContext.state === 'suspended') {
            void audioContext.resume();
        }
        const now = audioContext.currentTime;
        [880, 660, 880].forEach((freq, i) => {
            const osc = audioContext.createOscillator();
            const gain = audioContext.createGain();
            osc.type = 'square';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.0001, now + i * 0.25);
            gain.gain.exponentialRampToValueAtTime(0.15, now + i * 0.25 + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + i * 0.25 + 0.22);
            osc.connect(gain).connect(audioContext.destination);
            osc.start(now + i * 0.25);
            osc.stop(now + i * 0.25 + 0.24);
        });
    } catch {
        /* audio unavailable */
    }
}

function startAlarm() {
    stopAlarm();
    playSirenOnce();
    alarmTimer = window.setInterval(playSirenOnce, 2500);
}

function stopAlarm() {
    if (alarmTimer !== null) {
        clearInterval(alarmTimer);
        alarmTimer = null;
    }
}

function startTitleFlash() {
    stopTitleFlash();
    savedTitle = document.title;
    let on = false;
    titleFlashTimer = window.setInterval(() => {
        on = !on;
        document.title = on ? '🚨 New message report' : savedTitle;
    }, 900);
}

function stopTitleFlash() {
    if (titleFlashTimer !== null) {
        clearInterval(titleFlashTimer);
        titleFlashTimer = null;
        document.title = savedTitle || document.title;
    }
}

function notifyBrowser(report) {
    try {
        if (typeof Notification === 'undefined' || Notification.permission !== 'granted') {
            return;
        }
        browserNotification?.close?.();
        browserNotification = new Notification('CDRRMO — New message report', {
            body: [report.reporter?.name, report.details].filter(Boolean).join(': ') || 'A citizen sent an emergency report.',
            tag: 'cdrrmo-message-report',
            requireInteraction: true,
        });
        browserNotification.onclick = () => window.focus();
    } catch {
        /* ignore */
    }
}

function receivedAt(iso) {
    const d = iso ? new Date(iso) : null;
    if (!d || Number.isNaN(d.getTime())) {
        return 'Just now';
    }
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
}

onMounted(() => {
    if (!window.Echo) {
        console.warn('[Reverb] Echo is not configured — real-time message report alerts are disabled.');
        return;
    }
    channel = window.Echo.private('admin.reports');
    channel.listen(EVENT_NAME, onReportReceived);

    if (typeof Notification !== 'undefined' && Notification.permission === 'default') {
        void Notification.requestPermission().catch(() => {});
    }

    if (current.value) {
        openAlert();
    }
});

onUnmounted(() => {
    channel?.stopListening(EVENT_NAME, onReportReceived);
    channel = null;
    stopAlarm();
    stopTitleFlash();
});
</script>

<template>
    <Teleport to="body">
        <div
            :id="MODAL_ID"
            class="modal fade report-alert"
            tabindex="-1"
            role="alertdialog"
            aria-labelledby="report-alert-title"
            aria-modal="true"
            data-bs-backdrop="static"
            data-bs-keyboard="false"
            style="z-index: 1070"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div v-if="shown" class="modal-content report-alert__content">
                    <div class="report-alert__header">
                        <span class="report-alert__pulse" aria-hidden="true">
                            <i class="bi bi-chat-square-text-fill"></i>
                        </span>
                        <div>
                            <div class="report-alert__eyebrow">Emergency · Message report</div>
                            <h5 id="report-alert-title" class="report-alert__title">New report received</h5>
                        </div>
                        <span v-if="remaining" class="badge rounded-pill bg-light text-danger ms-auto">
                            +{{ remaining }} more
                        </span>
                    </div>

                    <div class="modal-body">
                        <dl class="report-alert__facts">
                            <dt><i class="bi bi-person-fill"></i> Reporter</dt>
                            <dd>
                                {{ shown.reporter?.name || 'Unknown' }}
                                <a v-if="shown.reporter?.phone" :href="`tel:${shown.reporter.phone}`" class="ms-1">
                                    {{ shown.reporter.phone }}
                                </a>
                            </dd>

                            <dt><i class="bi bi-geo-alt-fill"></i> Location</dt>
                            <dd>
                                {{ shown.address || `${shown.latitude}, ${shown.longitude}` }}
                            </dd>

                            <dt><i class="bi bi-clock-fill"></i> Received</dt>
                            <dd>{{ receivedAt(shown.created_at) }}</dd>

                            <template v-if="shown.images_count">
                                <dt><i class="bi bi-image-fill"></i> Photos</dt>
                                <dd>{{ shown.images_count }} attached</dd>
                            </template>
                        </dl>

                        <div class="report-alert__message">
                            {{ shown.details || 'No message details were provided.' }}
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 d-flex justify-content-between">
                        <button
                            v-if="shown.latitude && shown.longitude"
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="openMap"
                        >
                            <i class="bi bi-map"></i> Open map
                        </button>
                        <span v-else></span>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light" @click="acknowledge">
                                {{ remaining ? 'Next' : 'Acknowledge' }}
                            </button>
                            <button type="button" class="btn btn-danger" @click="viewReports">
                                <i class="bi bi-box-arrow-up-right"></i> View report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.report-alert__content {
    border: 0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.25), 0 20px 50px rgba(15, 23, 42, 0.35);
    animation: report-alert-ring 1.6s ease-in-out infinite;
}

.report-alert__header {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: #fff;
}

.report-alert__pulse {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    font-size: 1.25rem;
}

.report-alert__pulse::after {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 2px solid rgba(255, 255, 255, 0.8);
    animation: report-alert-pulse 1.4s ease-out infinite;
}

.report-alert__eyebrow {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    opacity: 0.85;
}

.report-alert__title {
    margin: 0;
    font-weight: 700;
}

.report-alert__facts {
    display: grid;
    grid-template-columns: max-content 1fr;
    gap: 0.4rem 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.report-alert__facts dt {
    font-weight: 500;
    color: var(--cdrrmo-ink-muted, #64748b);
}

.report-alert__facts dt i {
    margin-right: 0.25rem;
}

.report-alert__facts dd {
    margin: 0;
    color: var(--cdrrmo-ink, #0c4a6e);
    word-break: break-word;
}

.report-alert__message {
    padding: 0.85rem 1rem;
    border-left: 4px solid #dc2626;
    border-radius: 6px;
    background: #fef2f2;
    color: #7f1d1d;
    white-space: pre-line;
    max-height: 200px;
    overflow-y: auto;
}

@keyframes report-alert-pulse {
    from { transform: scale(1); opacity: 1; }
    to { transform: scale(1.6); opacity: 0; }
}

@keyframes report-alert-ring {
    0%, 100% { box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.25), 0 20px 50px rgba(15, 23, 42, 0.35); }
    50% { box-shadow: 0 0 0 10px rgba(220, 38, 38, 0.12), 0 20px 50px rgba(15, 23, 42, 0.35); }
}

@media (prefers-reduced-motion: reduce) {
    .report-alert__content,
    .report-alert__pulse::after {
        animation: none;
    }
}
</style>
