<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '../../layouts/AuthLayout.vue'

const recoveryMode = ref(false)

const form = useForm({
    code: '',
    recovery_code: '',
})

const submit = () => {
    form.post('/two-factor-challenge')
}

const switchMode = () => {
    recoveryMode.value = !recoveryMode.value
    form.reset()
    form.clearErrors()
}
</script>

<template>
    <AuthLayout title="Verify your identity" :description="recoveryMode ? 'Enter one of your recovery codes.' : 'Enter the six-digit code from your authenticator app.'" eyebrow="TWO-FACTOR SECURITY" :back-href="route('login')" back-label="Back to sign in">

            <form @submit.prevent="submit">
                <div v-if="!recoveryMode">
                    <label for="code" class="mb-1 block">
                        Authentication Code
                    </label>

                    <input id="code" v-model="form.code" type="text" inputmode="numeric" autocomplete="one-time-code"
                        maxlength="6" class="w-full rounded border px-3 py-2" />

                    <p v-if="form.errors.code" class="mt-1 text-sm text-red-600">
                        {{ form.errors.code }}
                    </p>
                </div>

                <div v-else>
                    <label for="recovery_code" class="mb-1 block">
                        Recovery Code
                    </label>

                    <input id="recovery_code" v-model="form.recovery_code" type="text" autocomplete="one-time-code"
                        class="w-full rounded border px-3 py-2" />

                    <p v-if="form.errors.recovery_code" class="mt-1 text-sm text-red-600">
                        {{ form.errors.recovery_code }}
                    </p>
                </div>

                <button type="submit" :disabled="form.processing"
                    class="w-full rounded bg-black px-4 py-2 text-white disabled:opacity-50">
                    {{ form.processing ? 'Verifying...' : 'Verify' }}
                </button>
            </form>

            <button type="button" class="mt-5 text-sm font-medium text-gray-700 underline" @click="switchMode">
                {{
                    recoveryMode
                        ? 'Use authenticator code instead'
                        : 'Use a recovery code instead'
                }}
            </button>
    </AuthLayout>
</template>