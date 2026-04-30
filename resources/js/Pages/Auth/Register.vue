<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Create an account" />

        <h2 class="auth-title">Create an account</h2>
        <p class="auth-lead mb-0">
            Register to use the CDRRMO services on the web and mobile app, including reports and your profile.
        </p>

        <form class="mt-3" @submit.prevent="submit">
            <div class="row g-2">
                <div class="col-12">
                    <JFloatingInput
                        label="Full name"
                        type="text"
                        v-model="form.name"
                        :error="form.errors.name"
                        required
                        autocomplete="name"
                    />
                </div>
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
                        label="Password"
                        type="password"
                        v-model="form.password"
                        :error="form.errors.password"
                        required
                        autocomplete="new-password"
                    />
                </div>
                <div class="col-12">
                    <JFloatingInput
                        label="Confirm password"
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
                {{ form.processing ? 'Creating account…' : 'Create account' }}
            </button>

            <div class="text-center mt-3">
                <span class="small text-muted">Already have an account? </span>
                <Link :href="route('login')" class="auth-link small">Sign in</Link>
            </div>
        </form>
    </GuestLayout>
</template>
