<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login.store'));
};
</script>

<template>

    <Head title="Login" />

    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="w-full max-w-md rounded-lg bg-white p-8 shadow">
            <h1 class="text-2xl font-semibold mb-6">
                Audit Logging System
            </h1>

            <form @submit.prevent="submit" class="space-y-5">
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

                <label class="flex items-center gap-2">
                    <input v-model="form.remember" type="checkbox" />

                    <span>Remember me</span>
                </label>

                <button type="submit" :disabled="form.processing"
                    class="w-full rounded bg-black px-4 py-2 text-white disabled:opacity-50">
                    {{ form.processing ? 'Signing in...' : 'Sign in' }}
                </button>
            </form>
        </div>
    </div>
</template>