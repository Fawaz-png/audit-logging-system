<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'

const props = defineProps({
    summary: { type: Object, required: true },
    departmentExposure: { type: Array, required: true },
    riskBreakdown: { type: Array, required: true },
    statusBreakdown: { type: Array, required: true },
    reportScope: { type: String, required: true },
})

const maxDepartmentFindings = computed(() => Math.max(1, ...props.departmentExposure.map(item => Number(item.finding_count))))
const maxRiskTotal = computed(() => Math.max(1, ...props.riskBreakdown.map(item => Number(item.total))))

const barWidth = (value, maximum) => `${Math.max(6, Math.round((Number(value) / maximum) * 100))}%`
const label = (value) => value?.replaceAll('_', ' ').toLocaleLowerCase().replace(/\b\w/g, character => character.toUpperCase())
</script>

<template>
    <Head title="Reports & Insights" />

    <AuthenticatedLayout>
        <template #header>Reports &amp; Insights</template>

        <div class="mx-auto max-w-[1500px] space-y-8">
            <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="app-section-kicker">ASSURANCE INTELLIGENCE / {{ reportScope }}</p>
                    <h2 class="app-page-title mt-1 text-xl font-semibold text-gray-900">Reports &amp; Insights</h2>
                    <p class="mt-1 text-sm text-gray-600">Portfolio exposure, remediation pressure, and workflow distribution.</p>
                </div>
                <Link :href="route('audits.index')" class="report-action-link">Open audit register <span aria-hidden="true">↗</span></Link>
            </header>

            <section aria-label="Portfolio indicators" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="report-metric"><span>Active audits</span><strong>{{ summary.active_audits }}</strong></div>
                <div class="report-metric"><span>Open findings</span><strong>{{ summary.open_findings }}</strong></div>
                <div class="report-metric report-metric--risk"><span>High / critical open</span><strong>{{ summary.high_risk_findings }}</strong></div>
                <div class="report-metric report-metric--overdue"><span>Past-due actions</span><strong>{{ summary.overdue_actions }}</strong><small>Open actions with an agreed date before today.</small></div>
            </section>

            <section class="grid gap-8 xl:grid-cols-[1.3fr_1fr]">
                <div>
                    <div class="mb-4">
                        <p class="app-section-kicker">DEPARTMENT EXPOSURE</p>
                        <h3 class="mt-1 text-base font-semibold text-gray-900">Findings by audited unit</h3>
                    </div>
                    <div v-if="departmentExposure.length" class="divide-y divide-[#dce3db] border-y border-[#dce3db]">
                        <div v-for="department in departmentExposure" :key="department.id" class="report-row">
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="truncate text-sm font-medium text-gray-800">{{ department.department_name }}</span>
                                <span class="shrink-0 text-xs tabular-nums text-gray-500">{{ department.finding_count }} findings · {{ department.high_risk_count }} high risk</span>
                            </div>
                            <div class="report-bar-track"><span class="report-bar-fill" :style="{ width: barWidth(department.finding_count, maxDepartmentFindings) }"></span></div>
                        </div>
                    </div>
                    <p v-else class="border-y border-[#dce3db] py-6 text-sm text-gray-500">No findings are visible in this report scope.</p>
                </div>

                <div class="space-y-8">
                    <section>
                        <div class="mb-4">
                            <p class="app-section-kicker">RISK DISTRIBUTION</p>
                            <h3 class="mt-1 text-base font-semibold text-gray-900">Findings by rating</h3>
                        </div>
                        <div class="divide-y divide-[#dce3db] border-y border-[#dce3db]">
                            <div v-for="risk in riskBreakdown" :key="risk.risk_rating" class="report-row">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-medium text-gray-800">{{ label(risk.risk_rating) }}</span>
                                    <span class="text-sm font-semibold tabular-nums text-gray-900">{{ risk.total }}</span>
                                </div>
                                <div class="report-bar-track"><span class="report-bar-fill" :class="`is-${risk.risk_rating.toLowerCase()}`" :style="{ width: barWidth(risk.total, maxRiskTotal) }"></span></div>
                            </div>
                            <p v-if="!riskBreakdown.length" class="py-4 text-sm text-gray-500">No risk-rated findings yet.</p>
                        </div>
                    </section>

                    <section>
                        <div class="mb-4">
                            <p class="app-section-kicker">WORKFLOW HEALTH</p>
                            <h3 class="mt-1 text-base font-semibold text-gray-900">Current finding stages</h3>
                        </div>
                        <dl class="divide-y divide-[#dce3db] border-y border-[#dce3db]">
                            <div v-for="stage in statusBreakdown" :key="stage.status" class="flex items-center justify-between gap-4 py-2.5">
                                <dt class="text-sm text-gray-600">{{ label(stage.status) }}</dt>
                                <dd class="text-sm font-semibold tabular-nums text-gray-900">{{ stage.total }}</dd>
                            </div>
                            <p v-if="!statusBreakdown.length" class="py-4 text-sm text-gray-500">No workflow activity yet.</p>
                        </dl>
                    </section>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.report-metric {
    display: flex;
    min-height: 112px;
    flex-direction: column;
    justify-content: space-between;
    border: 1px solid #dce3db;
    background: #fffefb;
    padding: 16px;
}

.report-metric span {
    color: #65736a;
    font-size: 12px;
}

.report-metric strong {
    color: #204b3d;
    font-family: 'Instrument Serif', Georgia, serif;
    font-size: 34px;
    font-weight: 400;
    line-height: 1;
}

.report-metric--risk strong {
    color: #a64f3c;
}

.report-metric--overdue strong {
    color: #a96c31;
}

.report-row {
    display: grid;
    gap: 10px;
    padding: 13px 2px;
}

.report-bar-track {
    height: 5px;
    overflow: hidden;
    background: #edf0eb;
}

.report-bar-fill {
    display: block;
    height: 100%;
    background: #315d47;
    transition: width 200ms ease;
}

.report-bar-fill.is-low {
    background: #6e927b;
}

.report-bar-fill.is-medium {
    background: #c29445;
}

.report-bar-fill.is-high {
    background: #ce7a48;
}

.report-bar-fill.is-critical {
    background: #a94f3b;
}

.report-action-link {
    display: inline-flex;
    min-height: 38px;
    align-items: center;
    gap: 10px;
    border: 1px solid #dce3db;
    padding: 8px 11px;
    color: #315d47;
    font-size: 12px;
    font-weight: 600;
}
</style>