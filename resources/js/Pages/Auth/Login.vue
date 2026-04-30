<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import JFloatingInput from '@/Components/JFloatingInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { nextTick } from 'vue';

defineProps({
    canResetPassword: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: false },
    status: { type: String, default: null },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const passwordVisibility = ref(false);
const passwordInputType = ref('password');
const passwordIcon = ref('bi bi-eye');
const emailInputRef = ref(null);

onMounted(() => {
    nextTick(() => {
        emailInputRef.value?.focus?.();
    });
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const togglePasswordVisible = () => {
    passwordVisibility.value = !passwordVisibility.value;
    passwordInputType.value = passwordVisibility.value ? 'text' : 'password';
    passwordIcon.value = passwordVisibility.value ? 'bi bi-eye-slash' : 'bi bi-eye';
};
</script>

<template>
    <GuestLayout>
        <Head title="Sign in" />

        <h2 class="auth-title">Sign in</h2>
        <p class="auth-lead mb-0">
            Use your email and password for your CDRRMO account—the same sign-in works on this website and in the mobile
            app.
        </p>

        <div v-if="status" class="alert alert-success auth-alert mt-3" role="alert">
            {{ status }}
        </div>

        <form class="mt-3" @submit.prevent="submit">
            <div class="row g-2">
                <div class="col-12">
                    <JFloatingInput
                        ref="emailInputRef"
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
                        :type="passwordInputType"
                        v-model="form.password"
                        :error="form.errors.password"
                        required
                        autocomplete="current-password"
                    >
                        <button
                            type="button"
                            class="btn btn-link position-absolute end-0 top-0 me-3 py-0 border-0 shadow-none auth-link"
                            style="margin-top: 1rem"
                            aria-label="Toggle password visibility"
                            @click="togglePasswordVisible"
                        >
                            <i :class="passwordIcon" />
                        </button>
                    </JFloatingInput>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2 mb-3">
                <label class="d-flex align-items-center gap-2 mb-0 small text-muted">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span>Remember this device</span>
                </label>
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="auth-link small"
                >
                    Forgot password?
                </Link>
            </div>

            <button
                type="submit"
                class="btn btn-primary w-100 rounded-pill py-2 fw-bold"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status" />
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
            </button>

            <div v-if="canRegister" class="text-center mt-3">
                <span class="small text-muted">New to CDRRMO? </span>
                <Link :href="route('register')" class="auth-link small">Create an account</Link>
            </div>
        </form>
    </GuestLayout>
</template>
