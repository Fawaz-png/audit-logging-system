<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    sessionPolicy: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    name: props.profile.name,
    preferences: {
        timezone: props.profile.preferences?.timezone ?? 'UTC',
        live_notification_alerts: props.profile.preferences?.live_notification_alerts ?? true,
    },
})
const revokeSessionsForm = useForm({})

const timezones = [
    'UTC',
    'Africa/Lagos',
    'Africa/Nairobi',
    'Europe/London',
    'Europe/Paris',
    'America/New_York',
    'America/Chicago',
    'America/Los_Angeles',
    'Asia/Dubai',
    'Asia/Singapore',
    'Asia/Tokyo',
]

const submit = () => form.put(route('profile.update'), { preserveScroll: true })
const revokeOtherSessions = () => revokeSessionsForm.post(route('profile.sessions.revoke-others'), { preserveScroll: true })
</script>

<template>
    <Head title="My Profile & Preferences" />

    <AuthenticatedLayout>
        <template #header>My Profile</template>

        <div class="mx-auto max-w-4xl space-y-6">
            <header>
                <p class="app-section-kicker">ACCOUNT / PERSONAL SETTINGS</p>
                <h2 class="app-page-title mt-1 text-xl font-semibold text-gray-900">My profile &amp; preferences</h2>
                <p class="mt-1 text-sm text-gray-600">Update how your account appears and how the workspace behaves for you.</p>
            </header>

            <form class="profile-settings" @submit.prevent="submit">
                <section class="profile-settings-section" aria-labelledby="profile-details-heading">
                    <div>
                        <h3 id="profile-details-heading" class="text-sm font-semibold text-[#17231e]">Profile details</h3>
                        <p class="mt-1 text-xs text-gray-500">Your email address is managed by account activation.</p>
                    </div>

                    <label class="profile-field" for="profile-name">
                        <span>Display name</span>
                        <input id="profile-name" v-model="form.name" autocomplete="name" required />
                        <span v-if="form.errors.name" class="profile-error">{{ form.errors.name }}</span>
                    </label>

                    <label class="profile-field" for="profile-email">
                        <span>Email address</span>
                        <input id="profile-email" :value="profile.email" type="email" autocomplete="email" disabled />
                    </label>
                </section>

                <section class="profile-settings-section" aria-labelledby="session-security-heading">
                    <div>
                        <h3 id="session-security-heading" class="text-sm font-semibold text-[#17231e]">Signed-in sessions</h3>
                        <p class="mt-1 text-xs leading-5 text-gray-500">
                            Sessions end after {{ sessionPolicy.idleTimeoutMinutes }} minutes without activity or {{ Math.round(sessionPolicy.absoluteTimeoutMinutes / 60) }} hours, whichever comes first. Closing the browser or stopping a local server does not sign you out.
                        </p>
                    </div>
                    <div>
                        <button type="button" class="profile-save-button" :disabled="revokeSessionsForm.processing" @click="revokeOtherSessions">
                            {{ revokeSessionsForm.processing ? 'Revoking sessions...' : 'Sign out other sessions' }}
                        </button>
                        <p class="mt-2 text-xs text-gray-500">This keeps your current session active and requires other devices to sign in again.</p>
                    </div>
                </section>

                <section class="profile-settings-section" aria-labelledby="preferences-heading">
                    <div>
                        <h3 id="preferences-heading" class="text-sm font-semibold text-[#17231e]">Workspace preferences</h3>
                        <p class="mt-1 text-xs text-gray-500">Used for dashboard time and new in-app notification alerts.</p>
                    </div>

                    <label class="profile-field" for="profile-timezone">
                        <span>Time zone</span>
                        <select id="profile-timezone" v-model="form.preferences.timezone">
                            <option v-for="timezone in timezones" :key="timezone" :value="timezone">{{ timezone }}</option>
                        </select>
                        <span v-if="form.errors['preferences.timezone']" class="profile-error">{{ form.errors['preferences.timezone'] }}</span>
                    </label>

                    <label class="profile-toggle">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-gray-800">Live notification alerts</span>
                            <span class="mt-1 block text-xs leading-5 text-gray-500">Show a brief pop-up when a new workflow notification arrives.</span>
                        </span>
                        <input v-model="form.preferences.live_notification_alerts" type="checkbox" />
                    </label>
                    <span v-if="form.errors['preferences.live_notification_alerts']" class="profile-error">{{ form.errors['preferences.live_notification_alerts'] }}</span>
                </section>

                <footer class="flex flex-col-reverse gap-3 border-t border-[#e5eae3] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p v-if="form.recentlySuccessful" class="text-sm font-medium text-[#315d47]">Preferences saved.</p>
                    <span v-else></span>
                    <button type="submit" class="profile-save-button" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Save changes' }}
                    </button>
                </footer>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.profile-settings {
    border: 1px solid #dce3db;
    background: #fffefb;
}

.profile-settings-section {
    display: grid;
    gap: 20px;
    border-bottom: 1px solid #e5eae3;
    padding: 22px 20px;
}

.profile-field {
    display: grid;
    max-width: 520px;
    gap: 7px;
    color: #526258;
    font-size: 12px;
    font-weight: 600;
}

.profile-field input,
.profile-field select {
    min-height: 42px;
    width: 100%;
    border: 1px solid #d5ddd4;
    border-radius: 3px;
    background: white;
    padding: 9px 11px;
    color: #17231e;
    font-size: 14px;
    font-weight: 400;
}

.profile-field input:disabled {
    background: #f4f6f1;
    color: #77837b;
}

.profile-toggle {
    display: flex;
    max-width: 620px;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    border-top: 1px solid #edf0eb;
    padding-top: 16px;
}

.profile-toggle input {
    width: 18px;
    height: 18px;
    flex: 0 0 auto;
    accent-color: #315d47;
}

.profile-error {
    color: #b54737;
    font-size: 12px;
    font-weight: 400;
}

.profile-save-button {
    min-height: 40px;
    border-radius: 3px;
    background: #315d47;
    padding: 9px 15px;
    color: white;
    font-size: 13px;
    font-weight: 600;
}

.profile-save-button:disabled {
    cursor: wait;
    opacity: 0.6;
}
</style>