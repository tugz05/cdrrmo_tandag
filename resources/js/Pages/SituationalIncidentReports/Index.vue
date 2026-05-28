<script setup>
import PagePrintBar from '@/Components/PagePrintBar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    reports: {
        type: Object,
        required: true,
    },
});

const rows = computed(() => props.reports?.data ?? []);

const filterText = ref('');

const filteredRows = computed(() => {
    const q = filterText.value.trim().toLowerCase();
    if (!q) {
        return rows.value;
    }

    return rows.value.filter((r) => {
        const id = String(r.id ?? '');
        const type = String(r.incident_type ?? '').toLowerCase();
        const user = String(r.user?.name ?? '').toLowerCase();
        const received = String(r.date_time_received ?? '').toLowerCase();

        return (
            id.includes(q) ||
            type.includes(q) ||
            user.includes(q) ||
            received.includes(q)
        );
    });
});

function printUrl(id) {
    return route('situational-incident-reports.print', { situational_incident_report: id });
}
</script>

<template>
    <Head title="Situational incident reports" />

    <JHeaderTitle
        compact
        title="Situational incident reports"
        :breadcrumb-items="[{ title: 'Situational incident reports' }]"
    />

    <div class="no-print d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <PagePrintBar label="Print table" />
        <div style="max-width: 22rem" class="flex-grow-1">
            <label class="visually-hidden" for="sir-filter">Filter list</label>
            <input
                id="sir-filter"
                v-model="filterText"
                type="search"
                class="form-control form-control-sm"
                placeholder="Filter by type, user, date, ID…"
                autocomplete="off"
            />
        </div>
    </div>

    <div class="print-root card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Incident type</th>
                            <th>User</th>
                            <th>Date received</th>
                            <th class="text-end no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in filteredRows" :key="r.id">
                            <td>{{ r.id }}</td>
                            <td>{{ r.incident_type || '—' }}</td>
                            <td>
                                <span v-if="r.user">{{ r.user.name }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td>
                                <span v-if="r.date_time_received">{{ r.date_time_received }}</span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td class="text-end text-nowrap no-print">
                                <Link
                                    :href="route('situational-incident-reports.show', { situational_incident_report: r.id })"
                                    class="btn btn-sm btn-outline-primary me-1"
                                >
                                    View
                                </Link>
                                <a
                                    :href="printUrl(r.id)"
                                    class="btn btn-sm btn-outline-secondary"
                                    target="_blank"
                                    rel="noopener"
                                >
                                    Print
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div v-if="reports.links?.length > 3" class="card-footer d-flex flex-wrap gap-1 justify-content-end no-print">
            <template v-for="(l, idx) in reports.links" :key="idx">
                <Link
                    v-if="l.url"
                    :href="l.url"
                    class="px-2 py-1 rounded small text-decoration-none"
                    :class="l.active ? 'bg-primary text-white' : 'text-primary border'"
                    preserve-scroll
                    v-html="l.label"
                />
                <span
                    v-else
                    class="px-2 py-1 rounded small text-muted"
                    v-html="l.label"
                />
            </template>
        </div>
    </div>
</template>
