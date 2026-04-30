<script setup>
import JCard from '@/Components/JCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import JButton from '@/Components/JButton.vue';
import JDropdownMenu from '@/Components/JDropdownMenu.vue';
import JDropdownItem from '@/Components/JDropdownItem.vue';
import { useAdministrators } from '@/Composables/useAdministrators';
import JModal from '@/Components/JModal.vue';
import JModalButtonClose from '@/Components/JModalButtonClose.vue';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import JFormBodyTitle from '@/Components/JFormBodyTitle.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    administrators: {
        type: Array,
        default: () => [],
    },
});

const { form, create, store, edit, update, destroy } = useAdministrators();

const passwordVisibility = ref(false);
const passwordInputType = ref('password');
const passwordIcon = ref('bi bi-eye');

const showInputPassword = () => {
    passwordVisibility.value = !passwordVisibility.value;
    passwordInputType.value = passwordVisibility.value ? 'text' : 'password';
    passwordIcon.value = passwordVisibility.value ? 'bi bi-eye-slash' : 'bi bi-eye';
};

const roleLabel = (role) =>
    role === 'super_admin' ? 'Super administrator' : 'Administrator';
</script>

<template>
    <Head title="Staff accounts" />
    <JHeaderTitle
        title="Staff & super administrators"
        :breadcrumb-items="[{ title: 'Staff accounts' }]"
    />

    <div class="d-flex align-items-center justify-content-between mb-10">
        <div>Found {{ administrators.length }} staff account(s)</div>
        <JButton primary icon="plus-lg" text="Create staff account" @click="create()" />
    </div>
    <div class="row g-4 w-100">
        <div v-for="admin in administrators" :key="admin.id" class="col-sm-12 col-md-6 col-lg-4">
            <JCard>
                <div class="d-flex gap-3 justify-content-between">
                    <div class="d-flex gap-3 align-items-center">
                        <div>
                            <span class="avatar rounded-4 text-bg-secondary">{{ admin.name.substring(0, 1) }}</span>
                        </div>
                        <div>
                            <b>{{ admin.name }}</b>
                            <div class="fs-xsm">{{ admin.email }}</div>
                            <span
                                class="badge rounded-pill mt-1"
                                :class="admin.role === 'super_admin' ? 'text-bg-primary' : 'text-bg-secondary'"
                            >
                                {{ roleLabel(admin.role) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="dropdown">
                            <JButton default outline sm icon="three-dots-vertical" data-bs-toggle="dropdown" />
                            <JDropdownMenu class="dropdown-menu-end" style="min-width: 7rem">
                                <JDropdownItem icon="pencil-square" text="Edit" @click="edit(admin)" />
                                <JDropdownItem
                                    icon="trash3"
                                    text="Delete"
                                    :is-danger="true"
                                    @click="destroy(admin)"
                                />
                            </JDropdownMenu>
                        </div>
                    </div>
                </div>
            </JCard>
        </div>
    </div>

    <JModal sm>
        <form id="form-administrator" @submit.prevent="form.id !== '' ? update() : store()">
            <JFloatingInput
                label="Name"
                v-model="form.name"
                :error="form.errors.name"
                required
            />
            <JFloatingInput
                label="Email"
                v-model="form.email"
                :error="form.errors.email"
                required
            />

            <div v-if="form.id === ''" class="mb-3 mt-2">
                <label class="form-label small text-muted mb-1">Role</label>
                <select v-model="form.role" class="form-select" required>
                    <option value="admin">Administrator</option>
                    <option value="super_admin">Super administrator</option>
                </select>
                <div v-if="form.errors.role" class="text-danger small mt-1">{{ form.errors.role }}</div>
            </div>

            <JFormBodyTitle v-if="form.id === ''" title="Password" />
            <JFormBodyTitle v-else title="Update password (optional)" />
            <JFloatingInput
                class="position-relative"
                label="Password"
                :type="passwordInputType"
                v-model="form.password"
                :error="form.errors.password"
                :required="form.id === ''"
            >
                <button
                    v-if="form.password.trim()"
                    type="button"
                    class="btn btn-link position-absolute end-0 top-0 me-3 py-0 border-0"
                    style="margin-top: 1rem"
                    aria-label="Toggle password visibility"
                    @click="showInputPassword"
                >
                    <i :class="passwordIcon" />
                </button>
            </JFloatingInput>
            <JFloatingInput
                class="position-relative"
                label="Confirm password"
                :type="passwordInputType"
                v-model="form.password_confirmation"
                :error="form.errors.password_confirmation"
                :required="form.id === ''"
            >
                <button
                    v-if="form.password_confirmation.trim()"
                    type="button"
                    class="btn btn-link position-absolute end-0 top-0 me-3 py-0 border-0"
                    style="margin-top: 1rem"
                    aria-label="Toggle password visibility"
                    @click="showInputPassword"
                >
                    <i :class="passwordIcon" />
                </button>
            </JFloatingInput>
        </form>
        <template #footerend>
            <JModalButtonClose v-if="!form.processing" />
            <JButton type="submit" primary form="form-administrator" :processing="form.processing" text="Save" />
        </template>
    </JModal>
</template>
