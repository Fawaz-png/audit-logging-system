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
        required: true,
    },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('account.activate.store'))
}
</script>

<template>
    <AuthLayout title="Activate your account" description="Set a password to activate your IS Audit account." eyebrow="ACCOUNT ACTIVATION" :back-href="route('login')" back-label="Back to sign in">

            <form @submit.prevent="submit">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email
                    </label>

                    <input id="email" type="email" :value="form.email" disabled
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100" />

                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>

                    <input id="password" v-model="form.password" type="password" autocomplete="new-password"
                        class="mt-1 block w-full rounded-md border-gray-300" />

                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirm password
                    </label>

                    <input id="password_confirmation" v-model="form.password_confirmation" type="password"
                        autocomplete="new-password" class="mt-1 block w-full rounded-md border-gray-300" />

                    <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-600">
                        {{ form.errors.password_confirmation }}
                    </p>
                </div>

                <button type="submit" :disabled="form.processing"
                    class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                    {{ form.processing ? 'Activating...' : 'Activate account' }}
                </button>
            </form>
    </AuthLayout>
</template>