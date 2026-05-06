<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    report: {
        type: Object,
        required: true,
    },
});

function yn(v) {
    return v ? 'Yes' : 'No';
}

function printUrl() {
    return route('situational-incident-reports.print', { situational_incident_report: props.report.id });
}
</script>

<template>
    <Head :title="`SIR #${report.id}`" />

    <JHeaderTitle
        :title="`Situational incident report #${report.id}`"
        :breadcrumb-items="[
            { title: 'Situational incident reports', route: 'situational-incident-reports.index' },
            { title: `Report #${report.id}` },
        ]"
    />

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
        <Link :href="route('situational-incident-reports.index')" class="btn btn-outline-secondary btn-sm">
            Back to list
        </Link>
        <a :href="printUrl()" class="btn btn-primary btn-sm" target="_blank" rel="noopener">Print</a>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header fw-bold">Incident information</div>
        <div class="card-body row g-3 small">
            <div class="col-md-6"><strong>Incident type</strong><div>{{ report.incident_type || '—' }}</div></div>
            <div class="col-md-6"><strong>Receiver</strong><div>{{ report.receiver || '—' }}</div></div>
            <div class="col-12"><strong>Caller / source of information</strong><div class="text-break whitespace-pre-line">{{ report.caller_source_of_information || '—' }}</div></div>
            <div class="col-md-6"><strong>Date &amp; time received</strong><div>{{ report.date_time_received || '—' }}</div></div>
            <div class="col-md-6"><strong>Time of response</strong><div>{{ report.time_of_response || '—' }}</div></div>
            <div class="col-12"><strong>Location</strong><div class="text-break whitespace-pre-line">{{ report.location || '—' }}</div></div>
            <div class="col-12"><strong>Landmark</strong><div class="text-break whitespace-pre-line">{{ report.landmark || '—' }}</div></div>
            <div class="col-12"><strong>Details of incident</strong><div class="text-muted small">(Victim name/s, age, sex, address, contact)</div><div class="text-break whitespace-pre-line">{{ report.details_of_incident || '—' }}</div></div>
            <div class="col-12"><strong class="text-danger">Vehicle/s involved</strong><div class="text-muted small">(Type, color, plate, etc.)</div><div class="text-break whitespace-pre-line">{{ report.vehicles_involved || '—' }}</div></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header fw-bold text-danger">Assessing responsiveness (AVPU)</div>
        <div class="card-body row g-2 small">
            <div class="col-6 col-md-3">A — Alert: <strong>{{ yn(report.is_alert_response) }}</strong></div>
            <div class="col-6 col-md-3">V — Verbal: <strong>{{ yn(report.is_verbal_response) }}</strong></div>
            <div class="col-6 col-md-3">P — Pain: <strong>{{ yn(report.is_pain_response) }}</strong></div>
            <div class="col-6 col-md-3">U — Unconscious: <strong>{{ yn(report.is_unconscious) }}</strong></div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header fw-bold">Patient head to toe (DCAP-BTLS)</div>
        <div class="card-body row g-2 small">
            <div class="col-6 col-md-4">D — Deformity: <strong>{{ yn(report.has_deformity) }}</strong></div>
            <div class="col-6 col-md-4">C — Contusion: <strong>{{ yn(report.has_contusion) }}</strong></div>
            <div class="col-6 col-md-4">A — Abrasion: <strong>{{ yn(report.has_abrasion) }}</strong></div>
            <div class="col-6 col-md-4">P — Puncture / penetration: <strong>{{ yn(report.has_puncture_penetration) }}</strong></div>
            <div class="col-6 col-md-4">T — Tenderness: <strong>{{ yn(report.has_tenderness) }}</strong></div>
            <div class="col-6 col-md-4">L — Laceration: <strong>{{ yn(report.has_laceration) }}</strong></div>
            <div class="col-6 col-md-4">S — Swelling: <strong>{{ yn(report.has_swelling) }}</strong></div>
            <div class="col-12 mt-2">
                <strong>Examination notes</strong>
                <div class="border rounded p-2 mt-1 bg-light text-break whitespace-pre-line min-h-120">{{ report.examination_notes || '—' }}</div>
            </div>
            <div class="col-12 text-center mt-2">
                <div class="small text-muted mb-1">Anatomical reference</div>
                <img :src="'/assets/images/human.jpg'" alt="Body diagram" class="img-fluid border" style="max-height: 420px" />
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-bold">Action &amp; disposition</div>
        <div class="card-body row g-3 small">
            <div class="col-12"><strong>Action taken</strong><div class="text-break whitespace-pre-line">{{ report.action_taken || '—' }}</div></div>
            <div class="col-md-4"><strong>Refer to hospital?</strong><div>{{ report.refer_to_hospital ? report.refer_to_hospital : '—' }}</div></div>
            <div class="col-md-4"><strong>Time transported</strong><div>{{ report.time_transported || '—' }}</div></div>
            <div class="col-md-4"><strong>Name of hospital</strong><div>{{ report.name_of_hospital || '—' }}</div></div>
            <div class="col-12"><strong>Name of responders</strong><div class="text-break whitespace-pre-line">{{ report.name_of_responders || '—' }}</div></div>
            <div class="col-12"><strong>Name of response vehicle</strong><div>{{ report.name_of_response_vehicle || '—' }}</div></div>
        </div>
    </div>
</template>

<style scoped>
.min-h-120 { min-height: 120px; }
</style>
