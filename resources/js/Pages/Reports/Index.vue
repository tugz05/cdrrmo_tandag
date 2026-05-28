<script setup>
import JModal from '@/Components/JModal.vue';
import PagePrintBar from '@/Components/PagePrintBar.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { dataTable, toggleModal, toggleOffCanvas } from '@/Helpers/JHelper';
import { Head, router } from '@inertiajs/vue3';
import { nextTick, onMounted, watchEffect, ref, watch } from 'vue';
import JButton from '@/Components/JButton.vue';
import JDropdownMenu from '@/Components/JDropdownMenu.vue';
import JDropdownItem from '@/Components/JDropdownItem.vue';
import JOffCanvas from '@/Components/JOffCanvas.vue';
import JOffCanvasHeader from '@/Components/JOffCanvasHeader.vue';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import JFormBodyTitle from '@/Components/JFormBodyTitle.vue';
import JModalButtonClose from '@/Components/JModalButtonClose.vue';
import { useReport } from '@/Composables/useReport';
import { timeAgo } from '@/Helpers/JTimeAgo';
import JDropdownDivider from '@/Components/JDropdownDivider.vue';
import JDropdownHeader from '@/Components/JDropdownHeader.vue';
defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    reports: {
        type: Object,
        default: () => ({})
    },
    active_status: {
        type: String,
        default: null
    },
    active_type: {
        type: String,
        default: null
    },
    stats: {
        type: Object,
        default: () => ({})
    },
    search: {
        type: String,
        default: '',
    },
})

watch(
    () => props.search,
    (v) => {
        searchQ.value = v ?? '';
    }
);

const { form, reportImages, create, store, edit, updateStatus, destroy, details } = useReport()

onMounted(() => {
    mountDataTable()
})


const mountDataTable = () => {
    nextTick(() => {
        dataTable("datatable-users", {
            // columns: [
            //     { orderable: false },
            //     { orderable: true },
            //     { orderable: true },
            //     { orderable: true },
            //     { orderable: false },
            //     // { orderable: true },
            //     // { orderable: true },
            //     // { orderable: true },
            // ],
            pageLength: 10,
        })
    })
}

const verify = () => {
    toggleOffCanvas('offcanvas-users')
}

const filter_type = ref(props.active_type);
const filter_status = ref(props.active_status);
const searchQ = ref(props.search ?? '');

let searchDebounce = null;

const filterStatus = async () => {
    await router.get(
        route('reports.index', {
            type: filter_type.value,
            status: filter_status.value || undefined,
        }),
        {
            q: searchQ.value?.trim() ? searchQ.value.trim() : undefined,
        },
        { preserveState: true, replace: true }
    );
};

const onSearchInput = () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        void filterStatus();
    }, 400);
};

// Use watch to monitor changes
watch(filter_type, async (newType, oldType) => {
    // Reset filter_status when filter_type changes
    if (newType !== oldType) {
        filter_status.value = ''; // Reset to default value
    }
    await filterStatus(); // Call filterStatus with updated values
});

// Also watch filter_status if needed
watch(filter_status, async () => {
    await filterStatus(); // Call filterStatus when filter_status changes
});

const addReport = () => {
    toggleModal('Add Report')
}

const formatDateTime = (datetime) => {
    const date = new Date(datetime);
  
    const options = { 
        year: 'numeric', 
        month: '2-digit', 
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true 
    };
    
    return date.toLocaleString('en-US', options)
                .replace(/,/, ' at');
}
</script>

<template>
    <Head title="Reports" />
    <JHeaderTitle title="Reports" :breadcrumb-items="[{ title: 'Reports' }]" />

        <div class="no-print d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <PagePrintBar label="Print report list" />
            <div class="flex-grow-1" style="max-width: 22rem">
                <label class="visually-hidden" for="reports-search">Search reports</label>
                <input
                    id="reports-search"
                    v-model="searchQ"
                    type="search"
                    class="form-control form-control-sm"
                    placeholder="Search details, address, reporter…"
                    autocomplete="off"
                    @input="onSearchInput"
                />
            </div>
        </div>

        <!-- <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template> -->
        <!-- <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">You're logged in!</div>
                </div>
            </div>
        </div> -->

        <div class="no-print d-flex justify-content-between gap-4 align-items-center mb-10">
            <div class="d-flex gap-5">
                <div class="btn-group text-nowrap" role="group" aria-label="Basic example">
                    <button @click="filter_type = 'All'" type="button" class="btn btn-default" :class="{'active' : active_type === 'All'}">
                        <span class="badge text-bg-secondary">{{ stats.type.all }}</span> All</button>
                    <button @click="filter_type = 'Message'" type="button" class="btn btn-default" :class="{'active' : active_type === 'Message'}">
                        <span class="badge text-bg-secondary">{{ stats.type.messages }}</span> Messages</button>
                    <button @click="filter_type = 'Call'" type="button" class="btn btn-default" :class="{'active' : active_type === 'Call'}">
                        <span class="badge text-bg-secondary">{{ stats.type.calls }}</span> Calls</button>
                </div>
                <div class="btn-group text-nowrap" role="group" aria-label="Basic example">
                    <button @click="filter_status = null" class="btn btn-default" :class="{'active' : active_status == null}" type="button">
                        <span class="badge text-bg-secondary">{{ stats.status.all }}</span> All</button>
                    <button @click="filter_status = 'Pending'" class="btn btn-default" :class="{'active' : active_status == 'Pending'}" type="button">
                        <span class="badge text-bg-primary">{{ stats.status.pending }}</span> Pending</button>
                    <button @click="filter_status = 'In Progress'"  class="btn btn-default" :class="{'active' : active_status == 'In Progress'}" type="button">
                        <span class="badge text-bg-warning">{{ stats.status.in_progress }}</span> In Progress</button>
                    <button @click="filter_status = 'Rescued'" class="btn btn-default" :class="{'active' : active_status == 'Rescued'}" type="button">
                        <span class="badge text-bg-success">{{ stats.status.rescued }}</span> Rescued</button>
                </div>

            </div>
            <JButton primary @click="create()" class="text-nowrap" icon="plus-lg" text="Add Report"/>
        </div>

        <div class="print-root">
            <div class="table-responsive">
                <table class="table table-stripe bg-transparent datatable-users">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Reported by</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Date Received</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(report, i) in reports" :key="i">
                            <td>{{ i + 1 }}</td>
                            <td>
                                <div class="d-flex gap-4 align-items-center">
                                    <div style="width: 1em;">
                                        <span v-if="report?.type === 'Message'">
                                            <i class="bi bi-chat-fill text-success"></i>
                                        </span>
                                        <span v-if="report?.type === 'Call'">
                                            <i class="bi bi-telephone-inbound-fill text-primary"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <b v-if="report.user?.fname">{{ report.user?.fname ?? '--' }} {{ report.user?.mname }} {{ report.user?.lname }} {{ report.user?.suffix }}</b>
                                        <b v-if="report.reported_by">{{ report.reported_by ?? '--' }}</b>
                                        <div class="text-muted" style="font-size:x-small">{{ report.user?.email ?? '--' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span v-html="report.details ?? '<i>No Details</i>'"></span>
                                    <div v-if="report.address" class="text-muted">{{ report.address }}</div>
                                </div>
                                <div v-if="report.latitude && report.longitude" class="text-muted pt-1" style="font-size: smaller">
                                    <a :href="`https://www.google.com/maps/place/${report.latitude},${report.longitude}/@${report.latitude},${report.longitude},15z`" target="_blank">
                                        <i class="bi bi-geo-alt"></i> View Location
                                      </a>
                                </div>
                            </td>
                            <td>
                                <span class="badge" :class="[
                                    {'text-bg-primary' : report.status === 'Pending'},
                                    {'text-bg-warning' : report.status === 'In Progress'},
                                    {'text-bg-success' : report.status === 'Rescued'},
                                ]">
                                    {{ report.status }}
                                </span>
                            </td>
                            <td>
                                {{ formatDateTime(report.created_at) }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-4">
                                    <span>
                                        <JButton success outline sm @click="details(report)" class="d-flex">
                                            Details
                                            </JButton>
                                    </span>
                                    <div class="dropdown">
                                        <JButton subtle icon="three-dots-vertical" data-bs-toggle="dropdown"/>
                                        <JDropdownMenu>
                                            <JDropdownHeader text="Update Status"/>
                                            <JDropdownItem @click="updateStatus(report.id, 'Pending')" :disabled="report.status === 'Pending'">
                                                <i class="bi text-primary" :class="report.status === 'Pending' ? 'bi-circle' : 'bi-circle-fill'"></i> Pending
                                            </JDropdownItem>
                                            <JDropdownItem @click="updateStatus(report.id, 'In Progress')" :disabled="report.status === 'In Progress'">
                                                <i class="bi bi-circle-fill text-warning" :class="report.status === 'In Progress' ? 'bi-circle' : 'bi-circle-fill'"></i> In Progress
                                            </JDropdownItem>
                                            <JDropdownItem @click="updateStatus(report.id, 'Rescued')" :disabled="report.status === 'Rescued'">
                                                <i class="bi bi-circle-fill text-success" :class="report.status === 'Rescued' ? 'bi-circle' : 'bi-circle-fill'"></i> Rescued
                                            </JDropdownItem>
                                            <JDropdownDivider/>
                                            <JDropdownItem @click="destroy(report.id)" icon="trash3" :is-danger="true" text="Delete"/>
                                        </JDropdownMenu>
                                    </div>
                                </div>
                            </td>
                        
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <JOffCanvas id="offcanvas-reports" end backdrop class="border-start" style="width: 400px">
            <template #header>
                <JOffCanvasHeader>
                    <div>More Details</div>
                </JOffCanvasHeader>
            </template>
            <div class="row g-7">
                <div class="col-12 text-center border-top border-bottom py-3">
                    <!-- <span class="opacity-50 pe-2 fs-sm">Status:</span> -->
                    <span class="badge" :class="[
                        {'text-bg-primary' : form.status === 'Pending'},
                        {'text-bg-warning' : form.status === 'In Progress'},
                        {'text-bg-success' : form.status === 'Rescued'},
                    ]" style="font-size: 1rem">
                        {{ form.status }}
                    </span>
                    <div class="fs-xsm text-muted mt-1">
                        <span v-if="form.status === 'Pending'">Waiting for rescue.</span>
                        <span v-if="form.status === 'In Progress'">Rescue team is on the way.</span>
                        <span v-if="form.status === 'Rescued'">Already been rescued.</span>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-between gap-3 fs-sm text-muted">
                    <a v-if="form.latitude && form.longitude" :href="`https://www.google.com/maps/@${form.latitude},${form.longitude},13z`" target="_blank"><i class="bi bi-geo-alt"></i> View Location</a>
                    <span v-else>N/A</span>
                    <div><i class="bi bi-clock"></i> {{ timeAgo(form.created_at) }}</div>
                </div>
                <div class="col-12">
                    <p v-if="form.details" class="m-0">{{ form.details }}</p>
                    <p v-else class="m-0"><i>No Details.</i></p>
                    <p v-if="form.address" class="m-0 text-muted mt-2">{{ form.address }}</p>
                </div>

                <div class="col-12 d-flex gap-3 flex-wrap">
                    <template v-if="reportImages.processing == false && reportImages.data.length > 0">
                        <a v-for="(image, i) in reportImages.data" :key="i" :href="image" target="_blank">
                            <img :src="image" alt="Valid Image" height="100px" width="auto">
                        </a>
                    </template>
                    <template v-else-if="reportImages.processing">
                        <small class="opacity-50">
                            <i>loading images..</i>
                        </small>
                    </template>
                    <template v-else>
                        <small>No images found.</small>
                    </template>
                </div>

                <div class="col-12 border-top pt-4">
                    <div class="fs-sm opacity-50">Reported By:</div>
                    <p class="fw-bold">{{ form.user?.fname }} {{ form.user?.mname }} {{ form.user?.lname}} {{ form.user?.suffix }}</p>
                    <p v-if="form.reported_by">{{ form.reported_by }}</p>
                    <p v-if="form.reporters_address">{{ form.reporters_address }}</p>
                </div>
            </div>
        </JOffCanvas>

        <JModal sm>
            <div>
                <form @submit.prevent="store" id="form-report">
                    <JFloatingInput v-model="form.details" type="textarea" label="What?" :error="form.errors.details"/>
                    <JFloatingInput v-model="form.address"  type="text" label="Where?" :error="form.errors.address"/>
                    <JFloatingInput v-model="form.created_at" type="date" label="When?" :error="form.errors.created_at"/>
                    <br>
                    <JFloatingInput v-model="form.status" type="select" label="Status" :error="form.errors.status">
                        <template #option>
                            <option value="" selected hidden>Select</option>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Rescued">Rescued</option>
                        </template>
                    </JFloatingInput>
                    <br>
                    <JFormBodyTitle title="Reported By"/>
                    <JFloatingInput v-model="form.reported_by" type="text" label="Name" :error="form.errors.reported_by"/>
                    <JFloatingInput v-model="form.reporters_address" type="text" label="Address" :error="form.errors.reporters_address"/>
                </form>
            </div>
            <template #footerend>
                <JModalButtonClose/>
                <JButton primary type="submit" form="form-report" text="Save"/>
            </template>
        </JModal>
</template>
