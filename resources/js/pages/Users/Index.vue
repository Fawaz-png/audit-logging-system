<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue'

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
})

const page = usePage()
const canManageUsers = computed(() => page.props.auth?.user?.permissions?.includes('user.manage') ?? false)
const searchQuery = ref('')
const selectedDepartment = ref('all')
const selectedRole = ref('all')
const selectedStatus = ref('all')
const sortBy = ref('newest')

const departments = computed(() => [...new Set(props.users.map(user => user.department?.name).filter(Boolean))].sort())
const roles = computed(() => [...new Set(props.users.map(user => user.role?.name).filter(Boolean))].sort())

const filteredUsers = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase()

    return props.users
        .filter(user => {
            const matchesQuery = !query || `${user.name} ${user.email}`.toLocaleLowerCase().includes(query)
            const matchesDepartment = selectedDepartment.value === 'all'
                || user.department?.name === selectedDepartment.value
            const matchesRole = selectedRole.value === 'all'
                || user.role?.name === selectedRole.value
            const matchesStatus = selectedStatus.value === 'all'
                || (selectedStatus.value === 'active' ? Boolean(user.active) : !user.active)

            return matchesQuery && matchesDepartment && matchesRole && matchesStatus
        })
        .sort((first, second) => {
            if (sortBy.value === 'name-asc') return first.name.localeCompare(second.name)
            if (sortBy.value === 'name-desc') return second.name.localeCompare(first.name)
            if (sortBy.value === 'department') return (first.department?.name ?? '').localeCompare(second.department?.name ?? '')

            return new Date(second.created_at) - new Date(first.created_at)
        })
})

const resetFilters = () => {
    searchQuery.value = ''
    selectedDepartment.value = 'all'
    selectedRole.value = 'all'
    selectedStatus.value = 'all'
    sortBy.value = 'newest'
}
</script>

<template>

    <Head title="Users" />

    <AuthenticatedLayout>
        <template #header>
            Users
        </template>

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="app-page-title text-xl font-semibold text-gray-900">
                        People & access
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ filteredUsers.length }} of {{ users.length }} accounts · Manage account access and assignments.
                    </p>
                </div>

                <Link v-if="canManageUsers" :href="route('users.create')"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    <span class="text-base leading-none" aria-hidden="true">+</span> Add user
                </Link>
            </div>

            <div class="user-filter-bar">
                <label class="user-search-field">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0">
                        <circle cx="8.75" cy="8.75" r="5.75" stroke="currentColor" stroke-width="1.5" />
                        <path d="m13 13 4 4" stroke="currentColor" stroke-linecap="round" stroke-width="1.5" />
                    </svg>
                    <input v-model="searchQuery" type="search" placeholder="Search name or email" aria-label="Search users" />
                </label>

                <label class="user-filter-control">
                    <span>Department</span>
                    <select v-model="selectedDepartment" aria-label="Filter users by department">
                        <option value="all">All departments</option>
                        <option v-for="department in departments" :key="department" :value="department">{{ department }}</option>
                    </select>
                </label>

                <label class="user-filter-control">
                    <span>Role</span>
                    <select v-model="selectedRole" aria-label="Filter users by role">
                        <option value="all">All roles</option>
                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                    </select>
                </label>

                <label class="user-filter-control">
                    <span>Status</span>
                    <select v-model="selectedStatus" aria-label="Filter users by status">
                        <option value="all">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>

                <label class="user-filter-control user-sort-control">
                    <span>Sort</span>
                    <select v-model="sortBy" aria-label="Sort users">
                        <option value="newest">Recently added</option>
                        <option value="name-asc">Name A to Z</option>
                        <option value="name-desc">Name Z to A</option>
                        <option value="department">Department</option>
                    </select>
                </label>
            </div>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div class="overflow-x-auto">
                <table class="min-w-[680px] w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                User
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Role
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Department
                            </th>

                            <th
                                class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="user in filteredUsers" :key="user.id">
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <span class="user-avatar grid h-9 w-9 shrink-0 place-items-center text-xs font-semibold">
                                        {{ user.name?.charAt(0)?.toUpperCase() ?? '?' }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium text-gray-900">{{ user.name }}</span>
                                        <span class="mt-0.5 block truncate text-sm text-gray-500">{{ user.email }}</span>
                                    </span>
                                </div>
                            </td>

                            <td class="px-5 py-4 text-sm text-gray-700 sm:px-6">
                                <span class="user-role">{{ user.role?.name ?? 'Unassigned' }}</span>
                            </td>

                            <td class="px-5 py-4 text-sm text-gray-700 sm:px-6">
                                {{ user.department?.name ?? '—' }}
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <span class="user-status" :class="user.active ? 'is-active' : 'is-inactive'">
                                    {{ user.active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-700">
                                    {{ users.length ? 'No matching users' : 'No users have been created yet.' }}
                                </p>
                                <button v-if="users.length" type="button" class="user-reset-button mt-2" @click="resetFilters">
                                    Clear filters
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.user-avatar {
    border: 1px solid #dce3db;
    border-radius: 50%;
    background: #edf2e7;
    color: #315d47;
}

.user-role,
.user-status {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 4px 9px;
    font-size: 12px;
    font-weight: 600;
}

.user-role {
    background: #f0f3ed;
    color: #526258;
}

.user-status.is-active {
    background: #e6eee5;
    color: #315d47;
}

.user-status.is-inactive {
    background: #f0efeb;
    color: #77736b;
}

.user-filter-bar {
    display: grid;
    gap: 10px;
    border: 1px solid #dce3db;
    border-radius: 6px;
    background: #fffefb;
    padding: 12px;
}

.user-search-field {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 9px;
    border: 1px solid #dce3db;
    border-radius: 5px;
    padding: 0 10px;
    color: #668071;
}

.user-search-field input {
    width: 100%;
    min-height: 38px;
    border: 0;
    outline: 0;
    background: transparent;
    color: #263830;
    font-size: 12px;
}

.user-search-field input::placeholder { color: #9aa69d; }

.user-filter-control {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 4px;
    color: #89958d;
    font-size: 11px;
    font-weight: 600;
}

.user-filter-control select {
    width: 100%;
    min-height: 36px;
    border: 1px solid #dce3db;
    border-radius: 4px;
    background: #fffefb;
    padding: 0 8px;
    color: #34483e;
    font-size: 12px;
}

.user-reset-button {
    color: #315d47;
    font-size: 12px;
    font-weight: 650;
}

.user-reset-button:hover { text-decoration: underline; text-underline-offset: 3px; }

@media (min-width: 640px) {
    .user-filter-bar {
        grid-template-columns: minmax(200px, 1.4fr) repeat(3, minmax(120px, 0.8fr)) minmax(145px, 0.9fr);
        align-items: end;
        padding: 10px;
    }
}
</style>