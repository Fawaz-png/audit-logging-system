<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue'
import { formatDate } from '../../utils/formatDate.js'

const props = defineProps({
    audit: {
        type: Object,
        required: true,
    },
})

const page = usePage()
const permissions = computed(() => page.props.auth?.user?.permissions ?? [])
const canUpdateAudit = computed(() => permissions.value.includes('audit.update'))
const canCreateFinding = computed(() => permissions.value.includes('finding.create'))

const teamGroups = computed(() => [
    {
        title: 'Lead auditor',
        assignment: 'LEAD_AUDITOR',
        members: props.audit.auditors?.filter(user => user.pivot?.assignment_type === 'LEAD_AUDITOR') ?? [],
    },
    {
        title: 'Supporting auditors',
        assignment: 'SUPPORTING_AUDITOR',
        members: props.audit.auditors?.filter(user => user.pivot?.assignment_type === 'SUPPORTING_AUDITOR') ?? [],
    },
    {
        title: 'Auditees',
        assignment: 'AUDITEE',
        members: props.audit.auditors?.filter(user => user.pivot?.assignment_type === 'AUDITEE') ?? [],
    },
])

const displayValue = value => {
    if (!value) {
        return 'Not specified'
    }

    return String(value)
        .toLowerCase()
        .replaceAll('_', ' ')
        .replace(/\b\w/g, character => character.toUpperCase())
}

const statusClass = value => {
    const status = String(value ?? 'unknown').toLowerCase().replace(/[^a-z0-9]+/g, '-')
    return `audit-status-${status}`
}

const findingCount = computed(() => props.audit.findings?.length ?? 0)
const teamCount = computed(() => props.audit.auditors?.length ?? 0)
</script>

<template>
    <Head :title="audit.title" />

    <AuthenticatedLayout>
        <template #header>
            Audit Details
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Link :href="route('audits.index')" class="app-back-link">
                    <span aria-hidden="true">←</span> Audit register
                </Link>

                <div class="flex flex-wrap gap-2">
                    <Link v-if="canUpdateAudit" :href="route('audits.edit', { audit: audit.id })" class="audit-action-secondary">
                        Edit audit
                    </Link>
                    <Link v-if="canCreateFinding" :href="route('findings.create', audit.id)" class="audit-action-primary">
                        <span aria-hidden="true">+</span> Register finding
                    </Link>
                </div>
            </div>

            <section class="audit-hero relative overflow-hidden">
                <div class="audit-hero-lines" aria-hidden="true"></div>
                <div class="audit-hero-content relative z-10">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="audit-reference">{{ audit.reference }}</span>
                        <span class="audit-status" :class="statusClass(audit.status)">
                            {{ displayValue(audit.status) }}
                        </span>
                    </div>

                    <h2 class="audit-title mt-4 font-display text-3xl leading-tight sm:text-4xl">
                        {{ audit.title }}
                    </h2>
                    <p class="mt-3 text-sm text-white/70">
                        {{ audit.department?.name ?? 'Department not set' }}
                        <span v-if="audit.location"> · {{ audit.location }}</span>
                    </p>

                    <div class="audit-hero-stats mt-8 flex flex-wrap gap-x-8 gap-y-4">
                        <div>
                            <p class="audit-stat-label">FINDINGS</p>
                            <p class="mt-1 font-display text-2xl text-white">{{ findingCount }}</p>
                        </div>
                        <div>
                            <p class="audit-stat-label">TEAM MEMBERS</p>
                            <p class="mt-1 font-display text-2xl text-white">{{ teamCount }}</p>
                        </div>
                        <div>
                            <p class="audit-stat-label">AUDIT MODE</p>
                            <p class="mt-2 text-sm font-medium text-white">{{ displayValue(audit.audit_mode) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="audit-dates" aria-label="Audit schedule">
                <div class="audit-date-item">
                    <span class="audit-date-marker" aria-hidden="true">01</span>
                    <div>
                        <p class="audit-date-label">START DATE</p>
                        <p class="mt-1 text-sm font-semibold text-[#20382e]">{{ formatDate(audit.start_date) }}</p>
                    </div>
                </div>
                <span class="audit-date-connector" aria-hidden="true"></span>
                <div class="audit-date-item">
                    <span class="audit-date-marker" aria-hidden="true">02</span>
                    <div>
                        <p class="audit-date-label">END DATE</p>
                        <p class="mt-1 text-sm font-semibold text-[#20382e]">
                            {{ audit.end_date ? formatDate(audit.end_date) : 'Not scheduled' }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.62fr)]">
                <div class="space-y-5">
                    <section class="audit-panel">
                        <div class="audit-panel-heading">
                            <p class="audit-kicker">PURPOSE</p>
                            <h3 class="mt-1 text-lg font-semibold text-[#20382e]">Objective</h3>
                        </div>
                        <p class="audit-prose">{{ audit.objective || 'No objective has been recorded.' }}</p>
                    </section>

                    <section class="audit-panel">
                        <div class="audit-panel-heading">
                            <p class="audit-kicker">BOUNDARIES</p>
                            <h3 class="mt-1 text-lg font-semibold text-[#20382e]">Scope</h3>
                        </div>
                        <p class="audit-prose">{{ audit.scope || 'No scope has been recorded.' }}</p>
                    </section>
                </div>

                <aside class="audit-panel audit-context-panel">
                    <div class="audit-panel-heading">
                        <p class="audit-kicker">ENGAGEMENT PROFILE</p>
                        <h3 class="mt-1 text-lg font-semibold text-[#20382e]">Audit context</h3>
                    </div>

                    <dl class="audit-context-list">
                        <div>
                            <dt>Department</dt>
                            <dd>{{ audit.department?.name ?? 'Not specified' }}</dd>
                        </div>
                        <div>
                            <dt>Methodology</dt>
                            <dd>{{ displayValue(audit.methodology) }}</dd>
                        </div>
                        <div>
                            <dt>Location</dt>
                            <dd>{{ audit.location || 'Not specified' }}</dd>
                        </div>
                        <div>
                            <dt>Previous audit</dt>
                            <dd>{{ audit.previous_audit_reference || 'None recorded' }}</dd>
                        </div>
                        <div>
                            <dt>Created by</dt>
                            <dd>{{ audit.creator?.name ?? 'Unknown' }}</dd>
                        </div>
                    </dl>
                </aside>
            </div>

            <section class="audit-panel">
                <div class="audit-section-heading">
                    <div>
                        <p class="audit-kicker">PEOPLE</p>
                        <h3 class="mt-1 text-lg font-semibold text-[#20382e]">Audit team</h3>
                    </div>
                    <span class="audit-count">{{ teamCount }} assigned</span>
                </div>

                <div class="audit-team-grid">
                    <section v-for="group in teamGroups" :key="group.assignment" class="audit-team-group">
                        <h4 class="audit-team-label">{{ group.title }}</h4>
                        <div v-if="group.members.length" class="mt-3 space-y-3">
                            <div v-for="member in group.members" :key="member.id" class="flex min-w-0 items-center gap-3">
                                <span class="audit-person-avatar">{{ member.name?.charAt(0)?.toUpperCase() ?? '?' }}</span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-[#263830]">{{ member.name }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-[#718078]">{{ member.email }}</span>
                                </span>
                            </div>
                        </div>
                        <p v-else class="mt-3 text-sm text-[#89958d]">No one assigned</p>
                    </section>
                </div>
            </section>

            <section class="audit-panel audit-findings-panel">
                <div class="audit-section-heading">
                    <div>
                        <p class="audit-kicker">ISSUES & FOLLOW-UP</p>
                        <h3 class="mt-1 text-lg font-semibold text-[#20382e]">Findings</h3>
                        <p class="mt-1 text-sm text-[#718078]">Findings recorded against this engagement.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="audit-count">{{ findingCount }} total</span>
                        <Link v-if="canCreateFinding" :href="route('findings.create', audit.id)" class="audit-action-primary">
                            <span aria-hidden="true">+</span> Register finding
                        </Link>
                    </div>
                </div>

                <div v-if="audit.findings?.length" class="audit-findings-list">
                    <article v-for="finding in audit.findings" :key="finding.id" class="audit-finding-row">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                <span class="audit-finding-reference">{{ finding.reference }}</span>
                                <span class="finding-risk" :class="statusClass(finding.risk_rating)">
                                    {{ displayValue(finding.risk_rating) }} risk
                                </span>
                                <span class="finding-state" :class="statusClass(finding.status)">
                                    {{ displayValue(finding.status) }}
                                </span>
                            </div>
                            <h4 class="mt-2 text-sm font-semibold text-[#263830]">{{ finding.title }}</h4>
                            <p class="mt-1 text-xs text-[#718078]">{{ finding.process || 'Process not specified' }}</p>
                        </div>

                        <Link :href="route('findings.show', finding.id)" class="audit-row-link">
                            View finding <span aria-hidden="true">↗</span>
                        </Link>
                    </article>
                </div>

                <div v-else class="audit-findings-empty">
                    <span class="audit-empty-mark" aria-hidden="true">0</span>
                    <div>
                        <p class="text-sm font-semibold text-[#34483e]">No findings recorded</p>
                        <p class="mt-1 text-sm text-[#718078]">Register a finding to track an issue and its corrective action.</p>
                    </div>
                    <Link v-if="canCreateFinding" :href="route('findings.create', audit.id)" class="audit-row-link">
                        Register finding <span aria-hidden="true">↗</span>
                    </Link>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.audit-action-primary,
.audit-action-secondary,
.audit-row-link {
    display: inline-flex;
    min-height: 40px;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 5px;
    padding: 8px 13px;
    font-size: 12px;
    font-weight: 650;
    text-decoration: none;
    transition: background-color 140ms ease, border-color 140ms ease, color 140ms ease, transform 140ms ease;
}

.audit-action-primary {
    border: 1px solid #183d30;
    background: #183d30;
    color: #fff;
}

.audit-action-primary:hover {
    background: #24553f;
    color: #fff;
    transform: translateY(-1px);
}

.audit-action-secondary,
.audit-row-link {
    border: 1px solid #cbd5cc;
    background: #fffefb;
    color: #315d47;
}

.audit-action-secondary:hover,
.audit-row-link:hover {
    border-color: #91a996;
    background: #edf2e7;
    color: #173d2e;
}

.audit-hero {
    border-radius: 7px;
    background: linear-gradient(120deg, #204b3d 0%, #16372e 66%, #102a24 100%);
    color: #fff;
    isolation: isolate;
}

.audit-hero::before {
    position: absolute;
    inset: 0;
    z-index: -1;
    background-image: linear-gradient(rgb(255 255 255 / 3%) 1px, transparent 1px), linear-gradient(90deg, rgb(255 255 255 / 3%) 1px, transparent 1px);
    background-size: 38px 38px;
    content: '';
    mask-image: linear-gradient(125deg, black, transparent 78%);
}

.audit-hero-lines {
    position: absolute;
    top: -44%;
    right: -4%;
    z-index: -1;
    width: min(44vw, 440px);
    aspect-ratio: 1;
    border: 1px solid rgb(255 255 255 / 12%);
    transform: rotate(38deg);
}

.audit-hero-lines::before,
.audit-hero-lines::after {
    position: absolute;
    border: 1px solid rgb(255 255 255 / 9%);
    content: '';
}

.audit-hero-lines::before { inset: 13%; }
.audit-hero-lines::after { inset: 27%; }

.audit-hero-content {
    padding: 26px;
}

.audit-reference,
.audit-stat-label,
.audit-kicker,
.audit-date-label,
.audit-team-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.14em;
}

.audit-reference {
    color: #f1c09d;
    font-variant-numeric: tabular-nums;
}

.audit-title {
    max-width: 840px;
    color: #fff;
}

.audit-status,
.finding-risk,
.finding-state,
.audit-count {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    white-space: nowrap;
}

.audit-status {
    border: 1px solid rgb(255 255 255 / 28%);
    background: rgb(255 255 255 / 9%);
    padding: 5px 10px;
    color: #fff;
    font-size: 11px;
    font-weight: 650;
}

.audit-status-planned { color: #e9ce93; }
.audit-status-in-progress { color: #a9d3b0; }
.audit-status-completed { color: #c5dfb7; }
.audit-status-cancelled { color: #edb6a6; }

.audit-hero-stats {
    border-top: 1px solid rgb(255 255 255 / 17%);
    padding-top: 18px;
}

.audit-stat-label {
    color: rgb(255 255 255 / 53%);
    font-size: 11px;
}

.audit-dates {
    display: flex;
    align-items: center;
    gap: 20px;
    border: 1px solid #dce3db;
    border-radius: 6px;
    background: #fffefb;
    padding: 16px 20px;
}

.audit-date-item {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 11px;
}

.audit-date-marker {
    display: grid;
    width: 30px;
    height: 30px;
    flex: 0 0 auto;
    place-items: center;
    border-radius: 50%;
    background: #edf2e7;
    color: #315d47;
    font-size: 11px;
    font-variant-numeric: tabular-nums;
}

.audit-date-label {
    color: #89958d;
    font-size: 11px;
}

.audit-date-connector {
    width: min(14vw, 96px);
    height: 1px;
    flex: 0 1 auto;
    background: #cbd5cc;
}

.audit-panel {
    min-width: 0;
    border: 1px solid #dce3db;
    border-radius: 6px;
    background: #fffefb;
    padding: 20px;
}

.audit-panel-heading {
    border-bottom: 1px solid #e5eae3;
    padding-bottom: 13px;
}

.audit-kicker {
    color: #718078;
    font-size: 11px;
}

.audit-prose {
    margin-top: 15px;
    color: #526258;
    font-size: 13px;
    line-height: 1.8;
    white-space: pre-line;
}

.audit-context-list > div {
    display: flex;
    flex-direction: column;
    gap: 4px;
    border-bottom: 1px solid #edf0eb;
    padding: 12px 0;
}

.audit-context-list > div:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.audit-context-list dt {
    color: #89958d;
    font-size: 11px;
}

.audit-context-list dd {
    color: #34483e;
    font-size: 13px;
    font-weight: 600;
}

.audit-section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.audit-count {
    border: 1px solid #dce3db;
    background: #f3f6f0;
    padding: 5px 10px;
    color: #64716a;
    font-size: 11px;
    font-weight: 600;
}

.audit-team-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin-top: 18px;
}

.audit-team-group {
    min-width: 0;
    padding: 0 18px;
}

.audit-team-group:first-child { padding-left: 0; }
.audit-team-group + .audit-team-group { border-left: 1px solid #e5eae3; }

.audit-team-label {
    color: #718078;
    font-size: 11px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.audit-person-avatar {
    display: grid;
    width: 34px;
    height: 34px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid #dce3db;
    border-radius: 50%;
    background: #edf2e7;
    color: #315d47;
    font-size: 11px;
    font-weight: 700;
}

.audit-findings-panel {
    padding-bottom: 8px;
}

.audit-findings-list {
    margin-top: 17px;
}

.audit-finding-row {
    display: flex;
    align-items: center;
    gap: 20px;
    border-top: 1px solid #e5eae3;
    padding: 16px 0;
}

.audit-finding-reference {
    color: #54705f;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.06em;
}

.finding-risk,
.finding-state {
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 700;
}

.finding-risk { background: #f0f3ed; color: #64716a; }
.audit-status-low { background: #e6eee5; color: #315d47; }
.audit-status-medium { background: #f6efdc; color: #86682a; }
.audit-status-high { background: #f7e8dc; color: #a3542e; }
.audit-status-critical { background: #f5e2df; color: #a54236; }
.finding-state { background: #edf2e7; color: #315d47; }
.audit-status-open { background: #f6efdc; color: #86682a; }
.audit-status-closed,
.audit-status-resolved { background: #e6eee5; color: #315d47; }

.audit-findings-empty {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-top: 18px;
    border-top: 1px solid #e5eae3;
    padding: 20px 0 12px;
}

.audit-empty-mark {
    display: grid;
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid #dce3db;
    border-radius: 50%;
    color: #54705f;
    font-family: 'Iowan Old Style', 'Palatino Linotype', 'Book Antiqua', Georgia, serif;
    font-size: 16px;
}

.audit-findings-empty > div {
    flex: 1;
}

@media (min-width: 640px) {
    .audit-hero-content { padding: 34px 38px; }
    .audit-panel { padding: 24px; }
}

@media (max-width: 639px) {
    .audit-hero-content { padding: 23px 20px; }
    .audit-hero-lines { top: -20%; right: -30%; width: 70vw; }
    .audit-dates { gap: 10px; padding: 14px 12px; }
    .audit-date-item { gap: 7px; }
    .audit-date-marker { width: 25px; height: 25px; }
    .audit-date-connector { width: 12px; }
    .audit-team-grid { grid-template-columns: 1fr; gap: 16px; }
    .audit-team-group,
    .audit-team-group:first-child { padding: 0; }
    .audit-team-group + .audit-team-group { border-top: 1px solid #e5eae3; border-left: 0; padding-top: 15px; }
    .audit-section-heading { align-items: flex-start; flex-direction: column; }
    .audit-findings-panel .audit-section-heading > div:last-child { width: 100%; justify-content: space-between; }
    .audit-finding-row { align-items: flex-start; flex-direction: column; gap: 12px; }
    .audit-findings-empty { align-items: flex-start; flex-wrap: wrap; }
    .audit-findings-empty .audit-row-link { width: 100%; }
}
</style>