<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '../../layouts/AuthLayout.vue'

const props = defineProps({
    token: {
        type: String,
        required: true,
    },

    email: {
        type: String,
        default: '',
    },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.store'))
}
</script>

<template>
    <AuthLayout title="Set a new password" description="Choose a new password for your account." eyebrow="ACCOUNT RECOVERY" :back-href="route('login')" back-label="Back to sign in">

            <form @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Email Address
                    </label>

                    <input v-model="form.email" type="email" autocomplete="email"
                        class="mt-1 w-full rounded-lg border-gray-300" />

                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        New Password
                    </label>

                    <input v-model="form.password" type="password" autocomplete="new-password"
                        class="mt-1 w-full rounded-lg border-gray-300" />

                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Confirm New Password
                    </label>

                    <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
                        class="mt-1 w-full rounded-lg border-gray-300" />
                </div>

                <button type="submit" :disabled="form.processing"
                    class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50">
                    {{ form.processing ? 'Resetting...' : 'Reset Password' }}
                </button>
            </form>
    </AuthLayout>
</template>