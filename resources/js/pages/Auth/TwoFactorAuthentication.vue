<script setup>
import { ref, onMounted } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue'

const page = usePage()

const enabled = ref(
    Boolean(page.props.auth?.user?.two_factor_enabled)
)

const confirming = ref(false)
const qrCode = ref(null)
const secretKey = ref(null)
const recoveryCodes = ref([])
const confirmationCode = ref('')
const loading = ref(false)
const error = ref(null)

const runProtectedAction = async (action, request) => {
    const response = await request()

    if (response.status === 423) {
        sessionStorage.setItem('2fa_pending_action', action)

        router.visit('/user/confirm-password', {
            replace: true,
        })

        return null
    }

    return response
}

const enableTwoFactor = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await runProtectedAction(
            'enable',
            () =>
                fetch('/user/two-factor-authentication', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content'),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
        )

        if (!response) {
            return
        }

        if (!response.ok) {
            throw new Error(
                'Unable to enable two-factor authentication.'
            )
        }

        await loadSetupData()

        confirming.value = true
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

const loadSetupData = async () => {
    const [qrResponse, secretResponse, recoveryResponse] =
        await Promise.all([
            fetch('/user/two-factor-qr-code', {
                headers: {
                    Accept: 'application/json',
                },
            }),
            fetch('/user/two-factor-secret-key', {
                headers: {
                    Accept: 'application/json',
                },
            }),
            fetch('/user/two-factor-recovery-codes', {
                headers: {
                    Accept: 'application/json',
                },
            }),
        ])

    if (!qrResponse.ok || !secretResponse.ok || !recoveryResponse.ok) {
        throw new Error('Unable to load two-factor authentication setup.')
    }

    const qrData = await qrResponse.json()
    const secretData = await secretResponse.json()
    const recoveryData = await recoveryResponse.json()

    qrCode.value = qrData.svg
    secretKey.value = secretData.secretKey
    recoveryCodes.value = recoveryData
}

const confirmTwoFactor = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await fetch(
            '/user/confirmed-two-factor-authentication',
            {
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
                    code: confirmationCode.value,
                }),
            }
        )

        if (!response.ok) {
            const data = await response.json().catch(() => ({}))

            throw new Error(
                data.message ?? 'The authentication code is invalid.'
            )
        }

        enabled.value = true
        confirming.value = false
        confirmationCode.value = ''
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

const regenerateRecoveryCodes = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await runProtectedAction(
            'regenerate',
            () =>
                fetch('/user/two-factor-recovery-codes', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content'),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
        )

        if (!response.ok) {
            throw new Error(
                'Unable to regenerate recovery codes.'
            )
        }

        const codesResponse = await fetch(
            '/user/two-factor-recovery-codes',
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }
        )

        if (!codesResponse.ok) {
            throw new Error(
                'Recovery codes were regenerated, but could not be loaded.'
            )
        }

        recoveryCodes.value = await codesResponse.json()

    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

const disableTwoFactor = async () => {
    loading.value = true
    error.value = null

    try {
        const response = await runProtectedAction(
            'disable',
            () =>
                fetch('/user/two-factor-authentication', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content'),
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
        )

        if (!response) {
            return
        }

        if (!response.ok) {
            throw new Error('Unable to disable two-factor authentication.')
        }

        enabled.value = false
        confirming.value = false
        qrCode.value = null
        secretKey.value = null
        recoveryCodes.value = []
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}


onMounted(() => {
    const pendingAction = sessionStorage.getItem('2fa_pending_action')

    if (!pendingAction) {
        return
    }

    sessionStorage.removeItem('2fa_pending_action')

    if (pendingAction === 'enable') {
        enableTwoFactor()
    }

    if (pendingAction === 'regenerate') {
        regenerateRecoveryCodes()
    }

    if (pendingAction === 'disable') {
        disableTwoFactor()
    }
})
</script>

<template>
    <Head title="Two-Factor Authentication" />

    <AuthenticatedLayout>
        <template #header>
            Security Settings
        </template>

        <div class="max-w-3xl space-y-6">
            <div>
                <p class="app-section-kicker">ACCOUNT SECURITY</p>
                <h2 class="app-page-title mt-1 text-xl font-semibold text-gray-900">
                    Two-Factor Authentication
                </h2>

                <p class="mt-2 text-sm leading-6 text-gray-600">
                    Add an extra layer of security to your account using an
                    authenticator application.
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm sm:p-8">

                <div v-if="error" class="mt-6 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ error }}
                </div>

                <!-- Disabled -->
                <div v-if="!enabled && !confirming" class="mt-8">
                    <div class="rounded border p-5">
                        <h2 class="font-medium">
                            Two-factor authentication is disabled
                        </h2>

                        <p class="mt-2 text-sm text-gray-600">
                            Enable it to require an authentication code when
                            signing in.
                        </p>

                        <button type="button" :disabled="loading"
                            class="mt-5 rounded bg-black px-4 py-2 text-sm text-white disabled:opacity-50"
                            @click="enableTwoFactor">
                            Enable Two-Factor Authentication
                        </button>
                    </div>
                </div>

                <!-- Setup -->
                <div v-if="confirming" class="mt-8 space-y-8">

                    <div>
                        <h2 class="font-medium">
                            1. Scan the QR code
                        </h2>

                        <p class="mt-2 text-sm text-gray-600">
                            Open your authenticator application and scan this
                            QR code.
                        </p>

                        <div v-if="qrCode" class="mt-5 flex justify-center rounded border p-6" v-html="qrCode" />
                    </div>

                    <div>
                        <h2 class="font-medium">
                            2. Manual setup key
                        </h2>

                        <p class="mt-2 text-sm text-gray-600">
                            If you cannot scan the QR code, enter this key
                            manually in your authenticator application.
                        </p>

                        <code class="mt-3 block break-all rounded bg-gray-100 p-3 text-sm">
                            {{ secretKey }}
                        </code>
                    </div>

                    <div>
                        <h2 class="font-medium">
                            3. Confirm your authenticator
                        </h2>

                        <p class="mt-2 text-sm text-gray-600">
                            Enter the six-digit code generated by your
                            authenticator.
                        </p>

                        <input v-model="confirmationCode" type="text" inputmode="numeric" maxlength="6"
                            autocomplete="one-time-code" class="mt-3 w-full rounded border px-3 py-2" />

                        <button type="button" :disabled="loading || confirmationCode.length !== 6"
                            class="mt-4 rounded bg-black px-4 py-2 text-sm text-white disabled:opacity-50"
                            @click="confirmTwoFactor">
                            {{ loading ? 'Confirming...' : 'Confirm 2FA' }}
                        </button>
                    </div>

                    <div v-if="recoveryCodes.length">
                        <h2 class="font-medium">
                            Recovery codes
                        </h2>

                        <p class="mt-2 text-sm text-gray-600">
                            Store these somewhere safe. Each recovery code can
                            be used to sign in if you lose access to your
                            authenticator.
                        </p>

                        <div class="mt-4 space-y-2 rounded bg-gray-100 p-4">
                            <code v-for="code in recoveryCodes" :key="code" class="block text-sm">
                                {{ code }}
                            </code>
                        </div>
                    </div>
                </div>

                <!-- Enabled -->
                <div v-if="enabled && !confirming" class="mt-8">

                    <div class="rounded border border-green-200 bg-green-50 p-5">
                        <h2 class="font-medium text-green-800">
                            Two-factor authentication is enabled
                        </h2>

                        <p class="mt-2 text-sm text-green-700">
                            Your account requires an authenticator code during
                            sign in.
                        </p>
                    </div>

                    <div class="mt-8">
                        <h2 class="font-medium">
                            Recovery codes
                        </h2>

                        <p class="mt-2 text-sm text-gray-600">
                            You can generate a new set of recovery codes.
                            Previous codes will no longer work.
                        </p>

                        <button type="button" :disabled="loading"
                            class="mt-4 rounded border px-4 py-2 text-sm disabled:opacity-50"
                            @click="regenerateRecoveryCodes">
                            Regenerate Recovery Codes
                        </button>

                        <div v-if="recoveryCodes.length" class="mt-4 space-y-2 rounded bg-gray-100 p-4">
                            <code v-for="code in recoveryCodes" :key="code" class="block text-sm">
                                {{ code }}
                            </code>
                        </div>
                    </div>

                    <div class="mt-8 border-t pt-6">
                        <h2 class="font-medium">
                            Disable two-factor authentication
                        </h2>

                        <p class="mt-2 text-sm text-gray-600">
                            This removes the additional authentication
                            requirement from your account.
                        </p>

                        <button type="button" :disabled="loading"
                            class="mt-4 rounded border border-red-300 px-4 py-2 text-sm text-red-700 disabled:opacity-50"
                            @click="disableTwoFactor">
                            Disable 2FA
                        </button>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>