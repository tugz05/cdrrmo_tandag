<script setup>
import { computed } from 'vue';
import '../../css/emergency-alert.css';

const props = defineProps({
    caller: { type: Object, default: () => ({}) },
    status: { type: String, default: '' },
    active: { type: Boolean, default: false },
});

defineEmits(['answer', 'reject', 'end']);

const location = computed(() => {
    const { address, latitude, longitude } = props.caller;
    if (address) {
        return address;
    }
    return latitude && longitude ? `${latitude}, ${longitude}` : 'Location not available';
});

const reportedAt = computed(() => {
    const d = props.caller.reportedAt ? new Date(props.caller.reportedAt) : null;
    if (!d || Number.isNaN(d.getTime())) {
        return null;
    }
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
});

function openMap() {
    const { latitude, longitude } = props.caller;
    window.open(`https://www.google.com/maps?q=${latitude},${longitude}`, '_blank', 'noopener');
}
</script>

<template>
    <Teleport to="body">
        <div
            id="modal-call"
            class="modal fade emergency-alert"
            tabindex="-1"
            role="alertdialog"
            aria-labelledby="incoming-call-title"
            aria-modal="true"
            data-bs-backdrop="static"
            data-bs-keyboard="false"
            style="z-index: 1075"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content emergency-alert__content" :class="{ 'emergency-alert__content--active': active }">
                    <div class="emergency-alert__header">
                        <span class="emergency-alert__pulse" aria-hidden="true">
                            <i class="bi bi-telephone-inbound-fill"></i>
                        </span>
                        <div>
                            <div class="emergency-alert__eyebrow">Emergency · Voice call</div>
                            <h5 id="incoming-call-title" class="emergency-alert__title">
                                {{ active ? 'Call in progress' : 'Incoming call' }}
                            </h5>
                        </div>
                    </div>

                    <div class="modal-body">
                        <dl class="emergency-alert__facts">
                            <dt><i class="bi bi-person-fill"></i> Caller</dt>
                            <dd>
                                {{ caller.name || 'Unknown caller' }}
                                <a v-if="caller.phone" :href="`tel:${caller.phone}`" class="ms-1">{{ caller.phone }}</a>
                            </dd>

                            <dt><i class="bi bi-geo-alt-fill"></i> Location</dt>
                            <dd>{{ location }}</dd>

                            <template v-if="reportedAt">
                                <dt><i class="bi bi-clock-fill"></i> Reported</dt>
                                <dd>{{ reportedAt }}</dd>
                            </template>
                        </dl>

                        <div class="emergency-alert__message" role="status">{{ status }}</div>
                    </div>

                    <div class="modal-footer border-top-0 d-flex justify-content-between">
                        <button
                            v-if="caller.latitude && caller.longitude"
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            @click="openMap"
                        >
                            <i class="bi bi-map"></i> Open map
                        </button>
                        <span v-else></span>
                        <div class="d-flex gap-2">
                            <template v-if="!active">
                                <button type="button" class="btn btn-danger" @click="$emit('reject')">
                                    <i class="bi bi-telephone-x-fill"></i> Reject
                                </button>
                                <button type="button" class="btn btn-success" @click="$emit('answer')">
                                    <i class="bi bi-telephone-fill"></i> Answer
                                </button>
                            </template>
                            <button v-else type="button" class="btn btn-danger" @click="$emit('end')">
                                <i class="bi bi-telephone-x-fill"></i> End call
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
