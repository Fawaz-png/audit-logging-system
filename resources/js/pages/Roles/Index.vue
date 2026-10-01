<script setup>
import { computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'

const props = defineProps({
    roles: {
        type: Array,
        required: true,
    },
    permissions: {
        type: Array,
        required: true,
    },
})

const roleForms = Object.fromEntries(props.roles.map(role => [
    role.id,
    useForm({ permissions: role.permissions.map(permission => permission.id) }),
]))

const permissionGroups = computed(() => {
    const groups = new Map()

    for (const permission of props.permissions) {
        const [group] = permission.slug.split('.')
        groups.set(group, [...(groups.get(group) ?? []), permission])
    }

    return [...groups.entries()]
})

const isLockedPermission = (role, permission) => role.slug === 'system-administrator'
    || permission.slug === 'user.manage'

const saveRole = (role) => {
    roleForms[role.id].put(route('roles.permissions.update', role.id), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Roles & Permissions" />

    <AuthenticatedLayout>
        <template #header>Roles &amp; Permissions</template>

        <div class="mx-auto max-w-[1500px] space-y-6">
            <header>
                <p class="app-section-kicker">GOVERNANCE / ACCESS CONTROL</p>
                <h2 class="app-page-title mt-1 text-xl font-semibold text-gray-900">Roles &amp; permissions</h2>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-600">
                    Review role grants and keep privileged account-management access restricted to system administrators.
                </p>
            </header>

            <section v-for="role in roles" :key="role.id" class="role-panel">
                <form @submit.prevent="saveRole(role)">
                    <div class="flex flex-col gap-3 border-b border-[#e5eae3] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-[#17231e]">{{ role.name }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ role.slug }} · {{ roleForms[role.id].permissions.length }} grants</p>
                        </div>
                        <button v-if="role.slug !== 'system-administrator'" type="submit"
                            class="role-save-button" :disabled="roleForms[role.id].processing">
                            {{ roleForms[role.id].processing ? 'Saving...' : 'Save permissions' }}
                        </button>
                        <span v-else class="role-lock-indicator">Protected role</span>
                    </div>

                    <div class="grid gap-x-8 gap-y-5 px-5 py-5 md:grid-cols-2 xl:grid-cols-3">
                        <fieldset v-for="[group, permissions] in permissionGroups" :key="group" class="min-w-0">
                            <legend class="mb-2 text-[10px] font-semibold uppercase tracking-[0.12em] text-[#748178]">
                                {{ group.replaceAll('-', ' ') }}
                            </legend>
                            <label v-for="permission in permissions" :key="permission.id" class="role-permission-row"
                                :class="{ 'is-locked': isLockedPermission(role, permission) }">
                                <input v-model="roleForms[role.id].permissions" type="checkbox" :value="permission.id"
                                    :disabled="isLockedPermission(role, permission)" />
                                <span class="min-w-0">
                                    <span class="block text-xs font-medium text-gray-800">{{ permission.name }}</span>
                                    <span class="mt-0.5 block truncate text-[10px] text-gray-500">{{ permission.slug }}</span>
                                </span>
                                <span v-if="isLockedPermission(role, permission)" class="sr-only">Protected permission</span>
                            </label>
                        </fieldset>
                    </div>
                    <p v-if="roleForms[role.id].errors.permissions" class="px-5 pb-4 text-sm text-red-700">
                        {{ roleForms[role.id].errors.permissions }}
                    </p>
                </form>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-panel {
    border: 1px solid #dce3db;
    border-radius: 5px;
    background: #fffefb;
}

.role-permission-row {
    display: flex;
    min-height: 42px;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #edf0eb;
    padding: 6px 2px;
}

.role-permission-row input {
    width: 15px;
    height: 15px;
    accent-color: #315d47;
}

.role-permission-row.is-locked {
    opacity: 0.72;
}

.role-save-button {
    min-height: 36px;
    border-radius: 3px;
    background: #315d47;
    padding: 8px 12px;
    color: white;
    font-size: 12px;
    font-weight: 600;
}

.role-save-button:disabled {
    cursor: wait;
    opacity: 0.6;
}

.role-lock-indicator {
    color: #7a6655;
    font-size: 11px;
    font-weight: 600;
}
</style>