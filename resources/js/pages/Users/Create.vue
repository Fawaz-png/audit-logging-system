<script setup>
import { computed, nextTick } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue'

const page = usePage()
const props = defineProps({
    departments: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
})

const form = useForm({
    name: '',
    email: '',
    department_id: '',
    role_id: '',
})

const hasErrors = computed(() => Object.keys(form.errors).length > 0)

const submit = () => {
    form.post(route('users.store'), {
        onSuccess: () => {
            form.reset()
        },
        onError: async () => {
            await nextTick()
            document.querySelector('[aria-invalid="true"]')?.focus()
        },
    })
}
</script>

<template>
    <Head title="Create User" />

    <AuthenticatedLayout>
        <template #header>
            Create User
        </template>

        <div class="max-w-4xl space-y-6">
            <div class="space-y-3">
                <Link :href="route('users.index')" class="app-back-link">
                    <span aria-hidden="true">←</span> People & access
                </Link>
                <div>
                    <p class="app-section-kicker">ACCOUNT PROVISIONING</p>
                    <h2 class="app-page-title mt-1 text-xl font-semibold text-slate-900">Add a user</h2>
                    <p class="mt-1 text-sm text-slate-600">Create an account and set its department and access role.</p>
                </div>
            </div>

            <div v-if="page.props.flash?.success" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3">
                <p class="text-sm font-medium text-green-800">
                    {{ page.props.flash.success }}
                </p>
            </div>

            <form @submit.prevent="submit" class="user-create-form">
                <div v-if="hasErrors" class="user-form-error-summary" role="alert">
                    <p class="text-sm font-semibold">Check the highlighted details</p>
                    <p class="mt-1 text-xs">Your account wasn’t created yet. Correct the fields below and submit again.</p>
                </div>

                <section class="user-form-section">
                    <div class="user-form-section-heading">
                        <span class="user-form-step">01</span>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Identity</h3>
                            <p class="mt-1 text-sm text-slate-500">The name and email used to identify this account.</p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <!-- Name -->
                    <div>
                        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">
                            Full name <span class="text-red-700">*</span>
                        </label>

                        <input id="name" v-model="form.name" type="text" autocomplete="name" required :aria-invalid="Boolean(form.errors.name)"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            :class="{ 'border-red-400': form.errors.name }" placeholder="Enter user's full name" />

                        <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">
                            Work email <span class="text-red-700">*</span>
                        </label>

                        <input id="email" v-model="form.email" type="email" autocomplete="email" required :aria-invalid="Boolean(form.errors.email)"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                            :class="{ 'border-red-400': form.errors.email }" placeholder="user@example.com" />

                        <p v-if="form.errors.email" class="mt-1.5 text-sm text-red-600">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    </div>
                </section>

                <section class="user-form-section">
                    <div class="user-form-section-heading">
                        <span class="user-form-step">02</span>
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Organization & access</h3>
                            <p class="mt-1 text-sm text-slate-500">Assign the user to the right team and role.</p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <!-- Department -->
                        <div>
                            <label for="department_id" class="mb-2 block text-sm font-medium text-slate-700">
                                Department <span class="text-red-700">*</span>
                            </label>
                            <select id="department_id" v-model="form.department_id" required :aria-invalid="Boolean(form.errors.department_id)"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                :class="{ 'border-red-400': form.errors.department_id }">
                                <option value="" disabled>
                                    Select department
                                </option>

                                <option v-for="department in props.departments" :key="department.id"
                                    :value="department.id">
                                    {{ department.name }}
                                </option>
                            </select>

                            <p v-if="form.errors.department_id" class="mt-1.5 text-sm text-red-600">
                                {{ form.errors.department_id }}
                            </p>
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="role_id" class="mb-2 block text-sm font-medium text-slate-700">
                                Access role <span class="text-red-700">*</span>
                            </label>

                            <select id="role_id" v-model="form.role_id" required :aria-invalid="Boolean(form.errors.role_id)"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200"
                                :class="{ 'border-red-400': form.errors.role_id }">
                                <option value="" disabled>
                                    Select role
                                </option>

                                <option v-for="role in props.roles" :key="role.id" :value="role.id">
                                    {{ role.name }}
                                </option>
                            </select>

                            <p v-if="form.errors.role_id" class="mt-1.5 text-sm text-red-600">
                                {{ form.errors.role_id }}
                            </p>
                        </div>
                    </div>

                    <div class="user-role-note mt-5">
                        <p class="text-sm font-semibold text-[#34483e]">Role determines access</p>
                        <p class="mt-1 text-sm leading-6 text-[#64716a]">
                            Assign the least-privileged role needed for the person’s work. They can set a password after activating their account.
                        </p>
                    </div>
                </section>

                    <!-- Activation notice -->
                    <div class="user-activation-note">
                        <span class="user-activation-mark" aria-hidden="true">✓</span>
                        <div>
                        <p class="text-sm font-medium text-slate-700">
                            Activation email
                        </p>

                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            The account will initially be inactive. An activation email
                            will be sent to the user so they can create their password
                            and activate the account.
                        </p>
                        </div>
                    </div>

                    <!-- Form actions -->
                    <div class="app-form-actions user-form-actions">
                        <Link :href="route('users.index')"
                            class="app-action-secondary"
                            :class="{ 'pointer-events-none opacity-60': form.processing }">
                            Cancel
                        </Link>

                        <button type="submit" :disabled="form.processing"
                            class="app-action-primary">
                            {{ form.processing ? 'Creating User...' : 'Create User' }}
                        </button>
                    </div>
                </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.user-create-form {
    display: grid;
    gap: 16px;
    border: 1px solid #dce3db;
    border-radius: 6px;
    background: #fffefb;
    padding: 16px;
}

.user-form-section {
    border: 1px solid #e1e7df;
    border-radius: 5px;
    background: #fff;
    padding: 16px;
}

.user-form-section-heading {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    border-bottom: 1px solid #e8ede6;
    padding-bottom: 13px;
}

.user-form-step {
    display: grid;
    width: 30px;
    height: 30px;
    flex: 0 0 auto;
    place-items: center;
    border-radius: 50%;
    background: #edf2e7;
    color: #315d47;
    font-size: 12px;
    font-variant-numeric: tabular-nums;
    font-weight: 700;
}

.user-form-error-summary {
    border: 1px solid #e8c6b9;
    border-left: 3px solid #b65e42;
    border-radius: 5px;
    background: #fbf1ed;
    padding: 12px 15px;
    color: #884632;
}

.user-role-note {
    border-left: 2px solid #d17b4c;
    background: #f6f8f3;
    padding: 11px 14px;
}

.user-activation-note {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    border: 1px solid #dce3db;
    border-radius: 5px;
    background: #f3f6f0;
    padding: 14px;
}

.user-activation-mark {
    display: grid;
    width: 25px;
    height: 25px;
    flex: 0 0 auto;
    place-items: center;
    border-radius: 50%;
    background: #e2ecdf;
    color: #315d47;
    font-size: 12px;
}

.user-form-actions {
    position: sticky;
    bottom: 0;
    z-index: 5;
    margin-inline: -8px;
    border-top: 1px solid #dce3db;
    background: rgb(255 254 251 / 94%);
    padding: 8px 10px max(8px, env(safe-area-inset-bottom));
    backdrop-filter: blur(12px);
}

@media (min-width: 640px) {
    .user-create-form { gap: 18px; padding: 22px; }
    .user-form-section { padding: 22px; }
}
</style>