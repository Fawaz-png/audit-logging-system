<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'
import { formatDate } from '@/utils/formatDate.js'

const page = usePage()
const stats = computed(() => page.props.stats ?? {})
const recentFindings = computed(() => page.props.recentFindings ?? [])
const overdueActions = computed(() => page.props.overdueActions ?? [])
const statusBreakdown = computed(() => page.props.statusBreakdown ?? [])
const timeZone = computed(() => page.props.auth?.user?.preferences?.timezone ?? 'UTC')
const clockDate = ref('')
const clockTime = ref('')
let clockInterval

const updateClock = () => {
    const now = new Date()

    clockDate.value = new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        timeZone: timeZone.value,
    }).format(now)
    clockTime.value = new Intl.DateTimeFormat(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: timeZone.value,
    }).format(now)
}

onMounted(() => {
    updateClock()
    clockInterval = window.setInterval(updateClock, 60_000)
})

onUnmounted(() => {
    window.clearInterval(clockInterval)
})
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            Dashboard
        </template>

        <div class="mx-auto max-w-[1500px] space-y-8">
            <section class="dashboard-welcome relative overflow-hidden px-6 py-8 text-white sm:px-9 sm:py-10">
                <div class="dashboard-welcome-lines" aria-hidden="true"></div>
                <div class="relative z-10 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="mb-3 text-[10px] font-semibold tracking-[0.18em] text-[#e9a678]">
                            INTERNAL ASSURANCE / OVERVIEW
                        </p>
                        <h2 class="font-display text-3xl leading-tight sm:text-4xl">
                            Welcome, {{ page.props.auth.user.name }}
                        </h2>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-white/70">
                            Keep your assurance work moving with a clear view of audits, findings, and follow-up.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-end justify-between gap-x-8 gap-y-5 sm:justify-end">
                        <div class="dashboard-clock">
                            <p class="text-[10px] font-semibold tracking-[0.14em] text-white/55">YOUR ACCOUNT TIME</p>
                            <p class="mt-1 font-display text-2xl leading-none text-white">{{ clockTime }}</p>
                            <p class="mt-1 text-xs text-white/65">{{ clockDate }}</p>
                        </div>

                        <Link v-if="page.props.auth.user.permissions?.includes('audit.view')"
                            :href="route('audits.index')" class="dashboard-welcome-link inline-flex shrink-0 items-center gap-3 text-sm font-semibold">
                            Open audit register <span aria-hidden="true">↗</span>
                        </Link>
                    </div>
                </div>
            </section>

            <section aria-labelledby="overview-heading">
                <div class="mb-4">
                    <p class="app-section-kicker">PORTFOLIO SNAPSHOT</p>
                    <h2 id="overview-heading" class="mt-1 text-base font-semibold text-gray-900">
                        At a glance
                    </h2>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <div class="dashboard-metric">
                        <span class="dashboard-metric-index">01</span>
                        <p class="text-sm text-gray-600">Active Audits</p>
                        <p class="mt-4 font-display text-4xl text-gray-900">{{ stats.activeAudits ?? 0 }}</p>
                    </div>

                    <div class="dashboard-metric">
                        <span class="dashboard-metric-index">02</span>
                        <p class="text-sm text-gray-600">Open Findings</p>
                        <p class="mt-4 font-display text-4xl text-gray-900">{{ stats.openFindings ?? 0 }}</p>
                    </div>

                    <div class="dashboard-metric">
                        <span class="dashboard-metric-index">03</span>
                        <p class="text-sm text-gray-600">Pending Responses</p>
                        <p class="mt-4 font-display text-4xl text-gray-900">{{ stats.pendingResponses ?? 0 }}</p>
                    </div>

                    <div class="dashboard-metric">
                        <span class="dashboard-metric-index">04</span>
                        <p class="text-sm text-gray-600">Pending Closure Reviews</p>
                        <p class="mt-4 font-display text-4xl text-gray-900">{{ stats.pendingClosureRequests ?? 0 }}</p>
                    </div>

                    <div class="dashboard-metric">
                        <span class="dashboard-metric-index">05</span>
                        <p class="text-sm text-gray-600">Overdue SLAs</p>
                        <p class="mt-4 font-display text-4xl text-gray-900">{{ stats.overdueActions ?? 0 }}</p>
                    </div>

                    <div class="dashboard-metric">
                        <span class="dashboard-metric-index">06</span>
                        <p class="text-sm text-gray-600">High / Critical Open</p>
                        <p class="mt-4 font-display text-4xl text-gray-900">{{ stats.highRiskFindings ?? 0 }}</p>
                    </div>
                </div>
            </section>

            <section aria-labelledby="insights-heading" class="space-y-4">
                <div class="mb-2">
                    <p class="app-section-kicker">REPORTS &amp; INSIGHTS</p>
                    <h2 id="insights-heading" class="mt-1 text-base font-semibold text-gray-900">Operational intelligence</h2>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <Link v-if="page.props.auth.user.permissions?.includes('audit.view')" :href="route('reports.index')" class="dashboard-metric dashboard-metric--card">
                        <span class="dashboard-metric-index">A</span>
                        <p class="text-sm text-gray-600">Reports &amp; insights</p>
                        <p class="mt-4 text-base font-semibold text-gray-900">Review exposure and workflow health</p>
                    </Link>
                    <Link :href="route('notifications.index')" class="dashboard-metric dashboard-metric--card">
                        <span class="dashboard-metric-index">N</span>
                        <p class="text-sm text-gray-600">Notifications</p>
                        <p class="mt-4 text-base font-semibold text-gray-900">Monitor recent workflow updates</p>
                    </Link>
                    <Link v-if="page.props.auth.user.permissions?.includes('audit-log.view')" :href="route('audit-logs.index')" class="dashboard-metric dashboard-metric--card">
                        <span class="dashboard-metric-index">L</span>
                        <p class="text-sm text-gray-600">Audit logs</p>
                        <p class="mt-4 text-base font-semibold text-gray-900">Review immutable change history</p>
                    </Link>
                    <Link v-if="page.props.auth.user.permissions?.includes('user.manage')" :href="route('roles.index')" class="dashboard-metric dashboard-metric--card">
                        <span class="dashboard-metric-index">R</span>
                        <p class="text-sm text-gray-600">Roles &amp; permissions</p>
                        <p class="mt-4 text-base font-semibold text-gray-900">Manage controlled access grants</p>
                    </Link>
                </div>
            </section>

            <section class="grid gap-8 xl:grid-cols-[1.4fr_1fr]">
                <div>
                    <div class="mb-4">
                        <p class="app-section-kicker">RECENT ACTIVITY</p>
                        <h2 class="mt-1 text-base font-semibold text-gray-900">Recently updated findings</h2>
                    </div>

                    <div v-if="recentFindings.length" class="divide-y divide-[#dce3db] border-y border-[#dce3db]">
                        <Link v-for="finding in recentFindings" :key="finding.id"
                            :href="route('findings.show', finding.id)"
                            class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500">{{ finding.reference }} · {{ finding.audit?.reference }}</p>
                                <p class="mt-1 truncate text-sm font-semibold text-gray-900">{{ finding.title }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ finding.audit?.department?.name ?? 'Department' }}</p>
                            </div>
                            <span class="shrink-0 text-xs font-semibold text-[#315d47]">{{ finding.status.replaceAll('_', ' ') }}</span>
                        </Link>
                    </div>
                    <p v-else class="border-y border-[#dce3db] py-5 text-sm text-gray-500">No findings in this workspace yet.</p>
                </div>

                <div class="space-y-8">
                    <section>
                        <div class="mb-4">
                            <p class="app-section-kicker">SLA TRACKING</p>
                            <h2 class="mt-1 text-base font-semibold text-gray-900">Overdue actions</h2>
                        </div>
                        <div v-if="overdueActions.length" class="divide-y divide-red-100 border-y border-red-100">
                            <Link v-for="action in overdueActions" :key="action.id"
                                :href="route('findings.show', action.finding_id)" class="block py-3">
                                <p class="text-sm font-medium text-gray-900">{{ action.finding?.reference }} · {{ action.finding?.title }}</p>
                                <p class="mt-1 text-xs text-red-700">Due {{ formatDate(action.agreed_due_date) }} · {{ action.action }}</p>
                            </Link>
                        </div>
                        <p v-else class="border-y border-[#dce3db] py-4 text-sm text-gray-500">No overdue remediation actions.</p>
                    </section>

                    <section>
                        <div class="mb-4">
                            <p class="app-section-kicker">WORKFLOW MIX</p>
                            <h2 class="mt-1 text-base font-semibold text-gray-900">Findings by status</h2>
                        </div>
                        <dl class="divide-y divide-[#dce3db] border-y border-[#dce3db]">
                            <div v-for="item in statusBreakdown" :key="item.status" class="flex items-center justify-between py-2.5">
                                <dt class="text-sm text-gray-600">{{ item.status.replaceAll('_', ' ') }}</dt>
                                <dd class="text-sm font-semibold tabular-nums text-gray-900">{{ item.total }}</dd>
                            </div>
                            <p v-if="!statusBreakdown.length" class="py-3 text-sm text-gray-500">No workflow activity yet.</p>
                        </dl>
                    </section>
                </div>
            </section>

            <section aria-labelledby="workspaces-heading">
                <div class="mb-4">
                    <p class="app-section-kicker">YOUR WORK</p>
                    <h2 id="workspaces-heading" class="mt-1 text-base font-semibold text-gray-900">
                        Workspaces
                    </h2>
                </div>

                <div class="divide-y divide-[#dce3db] border-y border-[#dce3db]">
                    <div class="flex flex-col gap-4 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <span class="dashboard-work-index">A</span>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Audits</h3>
                                <p class="mt-1 text-sm text-gray-600">Manage and review your audit engagements.</p>
                            </div>
                        </div>
                        <Link v-if="page.props.auth.user.permissions?.includes('audit.view')"
                            :href="route('audits.index')" class="dashboard-work-link">
                            Browse audits <span aria-hidden="true">↗</span>
                        </Link>
                    </div>

                    <div class="flex flex-col gap-4 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <span class="dashboard-work-index">F</span>
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Findings</h3>
                                <p class="mt-1 text-sm text-gray-600">Review findings, discuss recommendations, and track remediation through closure.</p>
                            </div>
                        </div>
                        <Link v-if="recentFindings.length"
                            :href="route('findings.show', recentFindings[0].id)" class="dashboard-work-link">
                            Open latest finding <span aria-hidden="true">↗</span>
                        </Link>
                        <Link v-else-if="page.props.auth.user.permissions?.includes('audit.view')"
                            :href="route('audits.index')" class="dashboard-work-link">
                            Browse audits <span aria-hidden="true">↗</span>
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.dashboard-welcome {
    background: linear-gradient(125deg, #204b3d 0%, #16372e 67%, #102a24 100%);
}

.dashboard-welcome h2 {
    color: #fff;
}

.dashboard-clock {
    min-width: 110px;
    border-left: 1px solid rgb(255 255 255 / 22%);
    padding-left: 16px;
    font-variant-numeric: tabular-nums;
}

.dashboard-welcome-lines {
    position: absolute;
    top: -40%;
    right: 4%;
    width: min(40vw, 440px);
    aspect-ratio: 1;
    border: 1px solid rgb(255 255 255 / 13%);
    transform: rotate(38deg);
}

.dashboard-welcome-lines::before,
.dashboard-welcome-lines::after {
    position: absolute;
    content: '';
    border: 1px solid rgb(255 255 255 / 10%);
}

.dashboard-welcome-lines::before {
    inset: 12%;
}

.dashboard-welcome-lines::after {
    inset: 25%;
}

.dashboard-welcome-link,
.dashboard-work-link {
    color: #315d47;
    transition: color 140ms ease;
}

.dashboard-welcome-link {
    border-bottom: 1px solid rgb(233 166 120 / 70%);
    padding-bottom: 8px;
    color: #fff;
}

.dashboard-welcome-link:hover {
    color: #e9a678;
}

.dashboard-metric {
    position: relative;
    min-height: 132px;
    border: 1px solid #dce3db;
    background: #fffefb;
    padding: 18px 20px;
}

.dashboard-metric-index {
    position: absolute;
    top: 17px;
    right: 18px;
    color: #9aa69d;
    font-size: 12px;
    font-variant-numeric: tabular-nums;
}

.dashboard-work-index {
    display: grid;
    width: 36px;
    height: 36px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid #dce3db;
    color: #315d47;
    font-family: 'Iowan Old Style', 'Palatino Linotype', 'Book Antiqua', Georgia, serif;
    font-size: 15px;
}

.dashboard-work-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
}

.dashboard-work-link:hover {
    color: #173d2e;
}

@media (max-width: 639px) {
    .dashboard-welcome-lines {
        top: -12%;
        right: -25%;
        width: 60vw;
    }
}
</style>