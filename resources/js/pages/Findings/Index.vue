<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue'

const props = defineProps({
    findings: {
        type: Object,
        required: true,
    },
})

const statusLabel = status => String(status ?? '')
    .replaceAll('_', ' ')
    .toLowerCase()
    .replace(/\b\w/g, character => character.toUpperCase())

const statusClass = status => ({
    OPEN: 'finding-status-open',
    RESPONSE_RECEIVED: 'finding-status-response',
    ACTION_PENDING_APPROVAL: 'finding-status-pending',
    REMEDIATION_IN_PROGRESS: 'finding-status-remediation',
    READY_FOR_VERIFICATION: 'finding-status-ready',
    CLOSURE_REQUESTED: 'finding-status-closure',
    CLOSURE_REJECTED: 'finding-status-rejected',
    CLOSED: 'finding-status-closed',
}[status] ?? 'finding-status-closed')
</script>

<template>
    <Head title="Findings Register" />

    <AuthenticatedLayout>
        <template #header>
            Findings Register
        </template>

        <div class="mx-auto max-w-7xl space-y-6">
            <header class="flex flex-col gap-3 border-b border-[#dce3db] pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="app-section-kicker">ASSURANCE FOLLOW-UP</p>
                    <h2 class="app-page-title mt-1 text-xl font-semibold text-gray-900">Findings</h2>
                    <p class="mt-1 text-sm text-gray-600">Findings visible within your audit portfolio or department.</p>
                </div>
                <p class="text-sm tabular-nums text-gray-500">{{ findings.total }} records</p>
            </header>

            <div v-if="findings.data.length" class="overflow-x-auto border-y border-[#dce3db] bg-white">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-[#dce3db] bg-[#f6f8f4] text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Finding</th>
                            <th class="px-4 py-3 font-semibold">Audit / department</th>
                            <th class="px-4 py-3 font-semibold">Risk</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf0eb]">
                        <tr v-for="finding in findings.data" :key="finding.id" class="transition hover:bg-[#f8faf6]">
                            <td class="px-4 py-3">
                                <Link :href="route('findings.show', finding.id)" class="font-semibold text-[#204b3d] hover:underline">
                                    {{ finding.title }}
                                </Link>
                                <p class="mt-1 text-xs text-gray-500">{{ finding.reference }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ finding.audit?.reference }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ finding.audit?.department?.name }}</p>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ finding.risk_rating }}</td>
                            <td class="px-4 py-3">
                                <span class="finding-status" :class="statusClass(finding.status)">{{ statusLabel(finding.status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ new Date(finding.updated_at).toLocaleDateString() }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="border-y border-[#dce3db] py-10 text-center text-sm text-gray-500">No findings are visible yet.</p>

            <nav v-if="findings.links?.length > 3" aria-label="Findings pages" class="flex items-center justify-between">
                <Link v-if="findings.prev_page_url" :href="findings.prev_page_url" class="app-action-secondary">Previous</Link>
                <span v-else></span>
                <p class="text-sm tabular-nums text-gray-500">Page {{ findings.current_page }} of {{ findings.last_page }}</p>
                <Link v-if="findings.next_page_url" :href="findings.next_page_url" class="app-action-secondary">Next</Link>
                <span v-else></span>
            </nav>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.finding-status {
    display: inline-flex;
    border-radius: 999px;
    padding: 4px 9px;
    font-size: 11px;
    font-weight: 650;
    white-space: nowrap;
}

.finding-status-open { background: #eef2ee; color: #42564a; }
.finding-status-response { background: #eaf2f7; color: #345a70; }
.finding-status-pending { background: #fff3dd; color: #805a13; }
.finding-status-remediation { background: #fceee4; color: #8c4e28; }
.finding-status-ready { background: #edf0fa; color: #48577f; }
.finding-status-closure { background: #fff0dd; color: #80500f; }
.finding-status-rejected { background: #fae9e6; color: #8b3e35; }
.finding-status-closed { background: #e8f3eb; color: #356445; }
</style>
