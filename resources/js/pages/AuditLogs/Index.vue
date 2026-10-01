<script setup>
import { ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'

const props = defineProps({
    logs: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
})

const page = usePage()
const search = ref(props.filters.search ?? '')
const formatTime = (value) => new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: page.props.auth?.user?.preferences?.timezone ?? 'UTC',
}).format(new Date(value))

const submitSearch = () => {
    router.get(route('audit-logs.index'), { search: search.value }, {
        preserveState: true,
        replace: true,
    })
}

const recordReference = (log) => log.target.reference
    ?? `${log.target.type} #${log.target.id}`
</script>

<template>
    <Head title="Audit Logs" />

    <AuthenticatedLayout>
        <template #header>Audit Logs</template>

        <div class="mx-auto max-w-[1500px] space-y-6">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="app-section-kicker">GOVERNANCE / EVIDENCE</p>
                    <h2 class="app-page-title mt-1 text-xl font-semibold text-gray-900">Audit log</h2>
                    <p class="mt-1 text-sm text-gray-600">Immutable record of changes made across the assurance workspace.</p>
                </div>
                <p class="text-sm tabular-nums text-gray-500">{{ logs.total }} events</p>
            </header>

            <form class="audit-log-search" role="search" @submit.prevent="submitSearch">
                <label for="audit-log-search" class="sr-only">Search audit logs</label>
                <input id="audit-log-search" v-model="search" type="search" autocomplete="off"
                    placeholder="Search action, record, or user" />
                <button type="submit" aria-label="Search audit logs" title="Search audit logs">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                        <circle cx="8.75" cy="8.75" r="5.75" stroke="currentColor" stroke-width="1.5" />
                        <path d="m13 13 4 4" stroke="currentColor" stroke-linecap="round" stroke-width="1.5" />
                    </svg>
                </button>
            </form>

            <div class="overflow-hidden border-y border-[#dce3db] bg-white">
                <div class="overflow-x-auto">
                    <table class="min-w-[720px] w-full divide-y divide-[#e5eae3]">
                        <thead class="bg-[#f4f6f1]">
                            <tr>
                                <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Event</th>
                                <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Actor</th>
                                <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Record</th>
                                <th class="px-5 py-3 text-left text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#edf0eb]">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-[#fafbf8]">
                                <td class="px-5 py-4">
                                    <span class="audit-log-action">{{ log.action.replaceAll('_', ' ') }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-900">{{ log.user?.name ?? 'System' }}</p>
                                    <p v-if="log.user?.email" class="mt-0.5 text-xs text-gray-500">{{ log.user.email }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-800">{{ recordReference(log) }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ log.target.type }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ formatTime(log.created_at) }}</td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">
                                    {{ filters.search ? 'No events match this search.' : 'No audit events have been recorded yet.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <nav v-if="logs.links.length > 3" aria-label="Audit log pages" class="flex flex-wrap justify-end gap-1">
                <Link v-for="link in logs.links" :key="`${link.label}-${link.url}`" :href="link.url ?? '#'
                    " class="audit-log-page-link" :class="{ 'is-current': link.active, 'is-disabled': !link.url }"
                    :aria-current="link.active ? 'page' : undefined" :aria-disabled="!link.url">
                    {{ link.label.replace('&laquo;', '‹').replace('&raquo;', '›') }}
                </Link>
            </nav>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.audit-log-search {
    display: flex;
    max-width: 540px;
    align-items: center;
    border: 1px solid #dce3db;
    background: #fffefb;
}

.audit-log-search:focus-within {
    border-color: #315d47;
    box-shadow: 0 0 0 2px rgb(49 93 71 / 12%);
}

.audit-log-search input {
    min-width: 0;
    flex: 1;
    border: 0;
    outline: 0;
    background: transparent;
    padding: 10px 12px;
    color: #17231e;
    font-size: 13px;
}

.audit-log-search button {
    display: grid;
    width: 40px;
    height: 40px;
    flex: 0 0 auto;
    place-items: center;
    color: #315d47;
}

.audit-log-action {
    display: inline-block;
    border-left: 2px solid #d88b5d;
    padding-left: 9px;
    color: #315d47;
    font-size: 11px;
    font-weight: 700;
}

.audit-log-page-link {
    min-width: 34px;
    border: 1px solid #dce3db;
    padding: 7px 9px;
    color: #315d47;
    font-size: 12px;
    text-align: center;
}

.audit-log-page-link.is-current {
    background: #315d47;
    color: white;
}

.audit-log-page-link.is-disabled {
    pointer-events: none;
    color: #a2aaa3;
}
</style>