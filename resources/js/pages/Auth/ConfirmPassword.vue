<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthLayout from '../../layouts/AuthLayout.vue'

const password = ref('')
const processing = ref(false)
const error = ref(null)

const submit = async () => {
    processing.value = true
    error.value = null

    try {
        const response = await fetch('/user/confirm-password', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content'),
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                password: password.value,
            }),
        })

        if (response.status === 422) {
            const data = await response.json().catch(() => ({}))

            error.value =
                data.errors?.password?.[0] ??
                'The password could not be confirmed.'

            return
        }

        if (!response.ok) {
            throw new Error(
                'Unable to confirm your password.'
            )
        }

        router.visit('/security/two-factor', {
            replace: true,
        })
    } catch (e) {
        error.value = e.message
    } finally {
        processing.value = false
    }
}
</script>

<template>
    <AuthLayout title="Confirm your password" description="For security, confirm your current password before changing your two-factor authentication settings." eyebrow="ACCOUNT SECURITY" :back-href="route('two-factor.settings')" back-label="Back to security settings">

            <div v-if="error" class="mb-5 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ error }}
            </div>

            <form @submit.prevent="submit">
                <div>
                    <label for="password" class="mb-1 block">
                        Current Password
                    </label>

                    <input id="password" v-model="password" type="password" autocomplete="current-password"
                        class="w-full rounded border px-3 py-2" />
                </div>

                <button type="submit" :disabled="processing"
                    class="w-full rounded bg-black px-4 py-2 text-white disabled:opacity-50">
                    {{ processing ? 'Confirming...' : 'Confirm Password' }}
                </button>
            </form>
    </AuthLayout>
</template>