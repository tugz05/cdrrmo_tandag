<script>
import { ref } from 'vue';

/** Module scope so unacknowledged alerts survive the layout remounting on Inertia navigation. */
const queue = ref([]);
</script>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { startAlarm, stopAlarm } from '@/utils/alarmSound';
import '../../css/emergency-alert.css';

/**
 * Real-time alert for message reports from the mobile app.
 * Listens on the private `admin.reports` channel (Laravel Reverb + Echo) and opens a modal
 * that stays until an operator acknowledges it. Mounted by AuthenticatedLayout for admins only.
 */

const MODAL_ID = 'modal-message-emergency-alert';
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
        browserNotification = new Notification('MDRRMO — New message report', {
            body: [report.reporter?.name, report.details].filter(Boolean).join(': ') || 'A citizen sent an emergency report.',
            tag: 'mdrrmo-message-report',
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
            class="modal fade emergency-alert"
            tabindex="-1"
            role="alertdialog"
            aria-labelledby="emergency-alert-title"
            aria-modal="true"
            data-bs-backdrop="static"
            data-bs-keyboard="false"
            style="z-index: 1070"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div v-if="shown" class="modal-content emergency-alert__content">
                    <div class="emergency-alert__header">
                        <span class="emergency-alert__pulse" aria-hidden="true">
                            <i class="bi bi-chat-square-text-fill"></i>
                        </span>
                        <div>
                            <div class="emergency-alert__eyebrow">Emergency · Message report</div>
                            <h5 id="emergency-alert-title" class="emergency-alert__title">New report received</h5>
                        </div>
                        <span v-if="remaining" class="badge rounded-pill bg-light text-danger ms-auto">
                            +{{ remaining }} more
                        </span>
                    </div>

                    <div class="modal-body">
                        <dl class="emergency-alert__facts">
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

                        <div class="emergency-alert__message">
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
