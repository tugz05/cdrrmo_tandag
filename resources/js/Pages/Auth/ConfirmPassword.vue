<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm password" />

        <h2 class="auth-title">Confirm your password</h2>
        <p class="auth-lead mb-0">
            For your security, please enter your password again to continue.
        </p>

        <form class="mt-3" @submit.prevent="submit">
            <JFloatingInput
                label="Password"
                type="password"
                v-model="form.password"
                :error="form.errors.password"
                required
                autocomplete="current-password"
            />

            <button
                type="submit"
                class="btn btn-primary w-100 rounded-pill py-2 fw-bold mt-1"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status" />
                {{ form.processing ? 'Please wait…' : 'Continue' }}
            </button>
        </form>
    </GuestLayout>
</template>
