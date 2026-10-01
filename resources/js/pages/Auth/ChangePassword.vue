<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '../../layouts/AuthLayout.vue'

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.put(route('password.update'))
}
</script>

<template>
    <AuthLayout title="Update your password" description="Choose a new password to keep your account secure." eyebrow="ACCOUNT SECURITY" :back-href="route('dashboard')" back-label="Back to dashboard">

            <form @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Current Password
                    </label>

                    <input v-model="form.current_password" type="password" autocomplete="current-password"
                        class="mt-1 w-full rounded-lg border-gray-300" />

                    <p v-if="form.errors.current_password" class="mt-1 text-sm text-red-600">
                        {{ form.errors.current_password }}
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
                    {{ form.processing ? 'Updating...' : 'Change Password' }}
                </button>
            </form>
    </AuthLayout>
</template>