<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: { type: String, default: null },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Verify email" />

        <h2 class="auth-title">Verify your email</h2>
        <p class="auth-lead mb-0">
            Thanks for registering. Please check your inbox and click the verification link we sent you. If you did not
            get the email, you can request another below.
        </p>

        <div v-if="verificationLinkSent" class="alert alert-success auth-alert mt-3" role="status">
            A new verification link has been sent to the email address you provided.
        </div>

        <form class="mt-3" @submit.prevent="submit">
            <button
                type="submit"
                class="btn btn-primary w-100 rounded-pill py-2 fw-bold"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status" />
                {{ form.processing ? 'Sending…' : 'Resend verification email' }}
            </button>
        </form>

        <div class="text-center mt-3">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="btn btn-link auth-link p-0 border-0 text-decoration-none"
            >
                Sign out
            </Link>
        </div>
    </GuestLayout>
</template>
