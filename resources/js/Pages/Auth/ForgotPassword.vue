<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Forgot password" />

        <h2 class="auth-title">Reset your password</h2>
        <p class="auth-lead mb-0">
            Enter the email for your CDRRMO account. We will send you a link to choose a new password.
        </p>

        <div v-if="status" class="alert alert-success auth-alert mt-3" role="status">
            {{ status }}
        </div>

        <form class="mt-3" @submit.prevent="submit">
            <JFloatingInput
                label="Email"
                type="email"
                v-model="form.email"
                :error="form.errors.email"
                required
                autocomplete="username"
            />

            <button
                type="submit"
                class="btn btn-primary w-100 rounded-pill py-2 fw-bold mt-1"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status" />
                {{ form.processing ? 'Sending…' : 'Send reset link' }}
            </button>

            <div class="text-center mt-3">
                <Link :href="route('login')" class="auth-link small">Back to sign in</Link>
            </div>
        </form>
    </GuestLayout>
</template>
