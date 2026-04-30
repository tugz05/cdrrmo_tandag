<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Set a new password" />

        <h2 class="auth-title">Set a new password</h2>
        <p class="auth-lead mb-0">Choose a strong password for your CDRRMO account.</p>

        <form class="mt-3" @submit.prevent="submit">
            <div class="row g-2">
                <div class="col-12">
                    <JFloatingInput
                        label="Email"
                        type="email"
                        v-model="form.email"
                        :error="form.errors.email"
                        required
                        autocomplete="username"
                    />
                </div>
                <div class="col-12">
                    <JFloatingInput
                        label="New password"
                        type="password"
                        v-model="form.password"
                        :error="form.errors.password"
                        required
                        autocomplete="new-password"
                    />
                </div>
                <div class="col-12">
                    <JFloatingInput
                        label="Confirm new password"
                        type="password"
                        v-model="form.password_confirmation"
                        :error="form.errors.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                </div>
            </div>

            <button
                type="submit"
                class="btn btn-primary w-100 rounded-pill py-2 fw-bold mt-2"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status" />
                {{ form.processing ? 'Updating…' : 'Update password' }}
            </button>
        </form>
    </GuestLayout>
</template>
