<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { dataTable } from '@/Helpers/JHelper';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted } from 'vue';
import JButton from '@/Components/JButton.vue';
import JDropdownMenu from '@/Components/JDropdownMenu.vue';
import JDropdownItem from '@/Components/JDropdownItem.vue';
import JOffCanvas from '@/Components/JOffCanvas.vue';
import JOffCanvasHeader from '@/Components/JOffCanvasHeader.vue';
import JModal from '@/Components/JModal.vue';
import JModalButtonClose from '@/Components/JModalButtonClose.vue';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import { useUser } from '@/Composables/cdrrmo'
import { timeAgo } from '@/Helpers/JTimeAgo';
import { toggleModal } from '@/Helpers/JModal';
defineOptions({ layout: AuthenticatedLayout });

const page = usePage();
const isSuperAdmin = computed(() => page.props.auth?.isSuperAdmin === true);

const props = defineProps({
    users: {
        type: Array,
        default: () => ([])
    }
})

const { viewUser, shouldShowUser, userData, isVerified, verify } = useUser()

const residentForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const openResidentModal = () => {
    residentForm.reset();
    residentForm.clearErrors();
    toggleModal('Add resident account', 'modal-resident');
};

const submitResident = () => {
    residentForm.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            residentForm.reset();
            toggleModal('', 'modal-resident');
        },
    });
};

onMounted(() => {
    mountDataTable()
})


const mountDataTable = () => {
    nextTick(() => {
        dataTable("datatable-users", {
            columns: [
                { orderable: false },
                { orderable: true },
                { orderable: true },
                { orderable: true },
                { orderable: false },
                { orderable: false },
                // { orderable: true },
                // { orderable: true },
                // { orderable: true },
            ],
            pageLength: 10,
        })
    })
}


</script>

<template>
    <Head title="User Accounts" />

    <JHeaderTitle title="User Accounts" :breadcrumb-items="[{title: 'User Accounts' }]"/>

        <div class="d-flex align-items-center justify-content-end mb-4">
            <JButton
                v-if="isSuperAdmin"
                primary
                icon="person-plus"
                text="Add resident account"
                @click="openResidentModal"
            />
        </div>

        <JModal sm id="modal-resident">
            <form id="form-resident" @submit.prevent="submitResident">
                <p class="small text-muted mb-3">
                    Creates a resident account with the <strong>user</strong> role (mobile app / profile)—same as public
                    registration.
                </p>
                <JFloatingInput
                    label="Full name"
                    v-model="residentForm.name"
                    :error="residentForm.errors.name"
                    required
                />
                <JFloatingInput
                    label="Email"
                    type="email"
                    v-model="residentForm.email"
                    :error="residentForm.errors.email"
                    required
                />
                <JFloatingInput
                    label="Password"
                    type="password"
                    v-model="residentForm.password"
                    :error="residentForm.errors.password"
                    required
                />
                <JFloatingInput
                    label="Confirm password"
                    type="password"
                    v-model="residentForm.password_confirmation"
                    :error="residentForm.errors.password_confirmation"
                    required
                />
            </form>
            <template #footerend>
                <JModalButtonClose v-if="!residentForm.processing" />
                <JButton
                    type="submit"
                    primary
                    form="form-resident"
                    :processing="residentForm.processing"
                    text="Create account"
                />
            </template>
        </JModal>

        <div>
            <div class="table-responsive">
                <table class="table table-stripe table-hover table-striped bg-transparent datatable-users">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Joined</th>
                            <th style="width: 6rem"></th>
                            <th style="width: 2rem"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(user, i) in users" :key="user.id">
                            <td>{{ i + 1 }}</td>
                            <td>
                                <b>{{ user.fullname }}</b>
                                <div class="opacity-50" style="font-size:small">{{ user.email }}</div>
                            </td>
                            <td>{{ user.address }}</td>
                            <td>{{ timeAgo(user.created_at) }}</td>
                            <td>
                                <div class="d-flex align-items-center justify-content-center gap-4">
                                    <span>
                                        <JButton v-if="user.confirmed_at != null" success sm @click="viewUser(user)" class="d-flex" icon="check-circle" text="Verified"/>
                                        <JButton v-else warning outline sm @click="viewUser(user)" class="d-flex" icon="check-circle" text="Unverified"/>
                                    </span>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <JButton subtle icon="three-dots-vertical" data-bs-toggle="dropdown"/>
                                    <JDropdownMenu style="min-width:8rem">
                                        <JDropdownItem :is-danger="true" icon="trash3" text="Delete"/>
                                    </JDropdownMenu>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <JOffCanvas id="offcanvas-users" end backdrop>
            <template #header>
                <JOffCanvasHeader>
                    <template v-if="isVerified">
                        <span class="text-primary">
                            Verified <i class="bi bi-check-circle"></i>
                        </span>
                    </template>
                    <template v-else>
                        <span class="text-danger">
                            Unverified User
                        </span>
                        <div class="fs-sm opacity-50 mt-1">Waiting for confirmation ...</div>
                    </template>
                </JOffCanvasHeader>
            </template>
            <div class="row g-7" v-if="shouldShowUser">
                <div class="col-12">
                    <span class="avatar" style="height:70px; width: 70px">{{
                        userData?.fullname?.[0] ?? '?'
                    }}</span>
                </div>
                <div class="col-12">
                    <h5>{{ userData?.fullname }}</h5>
                    <p class="m-0">
                        <!-- <a href="#"><i class="bi bi-geo-fill"></i> My Address here</a> -->
                        <a
                            v-if="userData?.latitude != null && userData?.longitude != null"
                            :href="`https://www.google.com/maps/@${userData.latitude},${userData.longitude},13z`"
                            target="_blank"
                            ><i class="bi bi-geo-alt"></i> My Address</a
                        >
                        <span v-else class="text-muted"><i class="bi bi-geo-alt"></i> Location not set</span>

                    </p>
                </div>
                <div class="col-12">
                    <div><i class="bi bi-phone"></i> {{ userData?.phone ?? '--' }}</div>
                    <div><i class="bi bi-envelope-at"></i> {{ userData?.email ?? '--' }}</div>
                </div>
                <div class="col-12">
                    <div v-if="(userData?.valid_ids?.length ?? 0) > 0" class="d-flex gap-3 flex-wrap">
                        <a
                            v-for="(validId, i) in (userData?.valid_ids ?? [])"
                            :key="i"
                            :href="validId"
                            target="_blank"
                        >
                            <img :src="validId" alt="Valid Image" height="100px" width="auto">
                        </a>
                    </div>
                    <div v-else>
                        <i>No valid images found.</i>
                    </div>
                </div>
                <div class="col-12">
                    <div class="border-top pt-5">
                        <JButton v-if="isVerified" @click="verify()" danger text="Unverify user"/>
                        <JButton v-else @click="verify()" success text="Verify this user?"/>
                    </div>
                </div>
            </div>
        </JOffCanvas>
</template>
