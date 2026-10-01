<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '../../layouts/AuthLayout.vue';


const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const submit = () => {
    form.post('/login');
};
</script>

<template>

    <AuthLayout title="Welcome back" description="Sign in to access your audit workspace." eyebrow="IS AUDIT / SECURE ACCESS">

            <div v-if="successMessage" class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3">
                <p class="text-sm font-medium text-green-800">
                    {{ successMessage }}
                </p>
            </div>

            <form @submit.prevent="submit">
                <div>
                    <label for="email" class="block mb-1">
                        Email
                    </label>

                    <input id="email" v-model="form.email" type="email" autocomplete="email"
                        class="w-full rounded border px-3 py-2" />

                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block mb-1">
                        Password
                    </label>

                    <input id="password" v-model="form.password" type="password" autocomplete="current-password"
                        class="w-full rounded border px-3 py-2" />
                </div>

                <label class="auth-remember">
                    <input v-model="form.remember" type="checkbox" />

                    <span>Remember me</span>
                </label>

                <button type="submit" :disabled="form.processing"
                    class="w-full rounded bg-black px-4 py-2 text-white disabled:opacity-50">
                    {{ form.processing ? 'Signing in...' : 'Sign in' }}
                </button>

                <Link :href="route('password.request')" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                    Forgot your password?
                </Link>
            </form>
    </AuthLayout>
</template>