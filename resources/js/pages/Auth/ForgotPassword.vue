<script setup>
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3'
import AuthLayout from '../../layouts/AuthLayout.vue'

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
})

const page = usePage();
const successMessage = computed(() => page.props.flash?.success);

const form = useForm({
    email: '',
})

const submit = () => {
    form.post(route('password.email'), {
        onSuccess: () => {
            form.reset()
        },
    })
}
</script>

<template>
    <AuthLayout title="Recover your account" description="Enter the email address associated with your account and we'll send a password reset link." eyebrow="ACCOUNT RECOVERY" :back-href="route('login')" back-label="Back to sign in">

            <div v-if="successMessage" class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3">
                <p class="text-sm font-medium text-green-800">
                    {{ successMessage }}
                </p>
            </div>

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

                <button type="submit" :disabled="form.processing"
                    class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50">
                    {{ form.processing ? 'Sending...' : 'Send Reset Link' }}
                </button>
            </form>

    </AuthLayout>
</template>