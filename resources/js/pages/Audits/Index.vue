<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';
import { formatDate } from '../../utils/formatDate.js';

const props = defineProps({
    audits: {
        type: Array,
        required: true,
    },
});

const page = usePage();
const canCreateAudit = computed(() => page.props.auth?.user?.permissions?.includes('audit.create') ?? false);
const searchQuery = ref('');
const selectedStatus = ref('all');

const statusOptions = computed(() => {
    return [...new Set(props.audits.map(audit => audit.status).filter(Boolean))]
        .sort((left, right) => left.localeCompare(right));
});

const filteredAudits = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase();

    return props.audits.filter(audit => {
        const matchesStatus = selectedStatus.value === 'all'
            || audit.status === selectedStatus.value;
        const searchableText = [
            audit.reference,
            audit.title,
            audit.department?.name,
            audit.creator?.name,
        ].join(' ').toLocaleLowerCase();

        return matchesStatus && (!query || searchableText.includes(query));
    });
});

const summary = computed(() => [
    { label: 'Total engagements', count: props.audits.length, tone: 'total' },
    { label: 'Planned', count: props.audits.filter(audit => audit.status === 'PLANNED').length, tone: 'planned' },
    { label: 'In progress', count: props.audits.filter(audit => audit.status === 'IN_PROGRESS').length, tone: 'progress' },
    { label: 'Completed', count: props.audits.filter(audit => audit.status === 'COMPLETED').length, tone: 'completed' },
]);

const displayStatus = status => String(status ?? 'Unknown')
    .toLowerCase()
    .replaceAll('_', ' ')
    .replace(/\b\w/g, character => character.toUpperCase());

const statusClass = status => `status-${String(status ?? 'unknown').toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;

const resetFilters = () => {
    searchQuery.value = '';
    selectedStatus.value = 'all';
};
</script>

<template>

    <Head title="Audits" />

    <AuthenticatedLayout>
        <template #header>
            Audits
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <section class="audit-register-hero">
                <div>
                    <p class="audit-register-kicker">ASSURANCE / PORTFOLIO</p>
                    <h2 class="audit-register-title mt-2 font-display text-3xl sm:text-4xl">Audit register</h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-white/70">
                        One view of planned work, active engagements, and completed reviews.
                    </p>
                </div>
                <Link v-if="canCreateAudit" :href="route('audits.create')" class="audit-create-link">
                    <span aria-hidden="true">+</span> Create audit
                </Link>
                <div class="audit-register-lines" aria-hidden="true"></div>
            </section>

            <section class="grid grid-cols-2 border-y border-[#dce3db] bg-[#fffefb] sm:grid-cols-4" aria-label="Audit summary">
                <div v-for="item in summary" :key="item.label" class="audit-summary-item" :class="`tone-${item.tone}`">
                    <p class="audit-summary-label">{{ item.label }}</p>
                    <p class="mt-2 font-display text-3xl text-[#20382e]">{{ item.count }}</p>
                </div>
            </section>

            <section class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="app-section-kicker">ENGAGEMENTS</p>
                        <h3 class="mt-1 text-base font-semibold text-[#20382e]">All audits</h3>
                    </div>
                    <p class="text-xs text-[#718078]">Showing {{ filteredAudits.length }} of {{ audits.length }}</p>
                </div>

                <div class="audit-filter-bar">
                    <label class="audit-search-field">
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-4 w-4 shrink-0">
                            <circle cx="8.75" cy="8.75" r="5.75" stroke="currentColor" stroke-width="1.5" />
                            <path d="m13 13 4 4" stroke="currentColor" stroke-linecap="round" stroke-width="1.5" />
                        </svg>
                        <input v-model="searchQuery" type="search" placeholder="Search reference, title, department, owner" aria-label="Search audits" />
                    </label>

                    <label class="audit-status-filter">
                        <span>Status</span>
                        <select v-model="selectedStatus" aria-label="Filter audits by status">
                            <option value="all">All statuses</option>
                            <option v-for="status in statusOptions" :key="status" :value="status">
                                {{ displayStatus(status) }}
                            </option>
                        </select>
                    </label>
                </div>

                <div v-if="audits.length === 0" class="audit-empty-state">
                    <span class="audit-empty-mark" aria-hidden="true">A</span>
                    <div>
                        <p class="text-sm font-semibold text-[#34483e]">No audits yet</p>
                        <p class="mt-1 text-sm text-[#718078]">Create an engagement to start building the register.</p>
                    </div>
                    <Link v-if="canCreateAudit" :href="route('audits.create')" class="audit-row-link">Create audit <span aria-hidden="true">↗</span></Link>
                </div>

                <div v-else-if="filteredAudits.length" class="audit-register-list">
                    <article v-for="audit in filteredAudits" :key="audit.id" class="audit-register-row">
                        <div class="audit-row-main">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                <span class="audit-reference">{{ audit.reference }}</span>
                                <span class="audit-status" :class="statusClass(audit.status)">
                                    {{ displayStatus(audit.status) }}
                                </span>
                            </div>

                            <h4 class="mt-2 truncate text-base font-semibold text-[#20382e]">{{ audit.title }}</h4>
                            <div class="audit-row-meta mt-2">
                                <span>{{ audit.department?.name ?? 'Department not set' }}</span>
                                <span v-if="audit.creator?.name">Created by {{ audit.creator.name }}</span>
                                <span v-if="audit.start_date" class="audit-schedule">
                                    {{ formatDate(audit.start_date) }}<template v-if="audit.end_date"> – {{ formatDate(audit.end_date) }}</template>
                                </span>
                            </div>
                        </div>

                        <Link :href="route('audits.show', { audit: audit.id })" class="audit-row-link">
                            View details <span aria-hidden="true">↗</span>
                        </Link>
                    </article>
                </div>

                <div v-else class="audit-empty-state">
                    <div>
                        <p class="text-sm font-semibold text-[#34483e]">No matching audits</p>
                        <p class="mt-1 text-sm text-[#718078]">Try another search or clear the status filter.</p>
                    </div>
                    <button type="button" class="audit-reset-button" @click="resetFilters">Clear filters</button>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.audit-register-hero {
    position: relative;
    display: flex;
    min-height: 188px;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    overflow: hidden;
    border-radius: 7px;
    background: linear-gradient(120deg, #204b3d 0%, #16372e 66%, #102a24 100%);
    padding: 26px;
    isolation: isolate;
}

.audit-register-hero::before {
    position: absolute;
    inset: 0;
    z-index: -1;
    background-image: linear-gradient(rgb(255 255 255 / 3%) 1px, transparent 1px), linear-gradient(90deg, rgb(255 255 255 / 3%) 1px, transparent 1px);
    background-size: 38px 38px;
    content: '';
    mask-image: linear-gradient(125deg, black, transparent 78%);
}

.audit-register-kicker {
    color: #f1c09d;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.16em;
}

.audit-register-title { color: #fff; }

.audit-register-lines {
    position: absolute;
    top: -58%;
    right: 7%;
    z-index: -1;
    width: min(36vw, 340px);
    aspect-ratio: 1;
    border: 1px solid rgb(255 255 255 / 12%);
    transform: rotate(38deg);
}

.audit-register-lines::before,
.audit-register-lines::after {
    position: absolute;
    border: 1px solid rgb(255 255 255 / 9%);
    content: '';
}

.audit-register-lines::before { inset: 13%; }
.audit-register-lines::after { inset: 27%; }

.audit-create-link {
    display: inline-flex;
    min-height: 42px;
    flex: 0 0 auto;
    align-items: center;
    gap: 8px;
    border: 1px solid rgb(255 255 255 / 35%);
    border-radius: 5px;
    background: rgb(255 255 255 / 9%);
    padding: 9px 14px;
    color: #fff;
    font-size: 12px;
    font-weight: 650;
    text-decoration: none;
    transition: background-color 140ms ease;
}

.audit-create-link:hover { background: rgb(255 255 255 / 17%); color: #fff; }
.audit-create-link span { color: #f1c09d; font-size: 17px; }

.audit-summary-item { min-width: 0; padding: 16px 18px; }
.audit-summary-item + .audit-summary-item { border-left: 1px solid #e5eae3; }
.audit-summary-label { color: #718078; font-size: 11px; font-weight: 600; }

.audit-filter-bar {
    display: flex;
    align-items: center;
    gap: 12px;
    border: 1px solid #dce3db;
    border-radius: 6px;
    background: #fffefb;
    padding: 10px 12px;
}

.audit-search-field {
    display: flex;
    min-width: 0;
    flex: 1;
    align-items: center;
    gap: 10px;
    color: #668071;
}

.audit-search-field input {
    width: 100%;
    min-height: 36px;
    border: 0;
    outline: 0;
    background: transparent;
    color: #263830;
    font-size: 12px;
}

.audit-search-field input::placeholder { color: #9aa69d; }

.audit-status-filter {
    display: flex;
    align-items: center;
    gap: 8px;
    border-left: 1px solid #e5eae3;
    padding-left: 12px;
    color: #89958d;
    font-size: 11px;
}

.audit-status-filter select {
    max-width: 145px;
    border: 0;
    outline: 0;
    background: transparent;
    color: #34483e;
    font-size: 12px;
    font-weight: 600;
}

.audit-register-list {
    overflow: hidden;
    border: 1px solid #dce3db;
    border-radius: 6px;
    background: #fffefb;
}

.audit-register-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 22px;
    border-bottom: 1px solid #e5eae3;
    padding: 18px 20px;
    transition: background-color 140ms ease;
}

.audit-register-row:last-child { border-bottom: 0; }
.audit-register-row:hover { background: #f8faf6; }
.audit-row-main { min-width: 0; flex: 1; }

.audit-reference { color: #54705f; font-size: 11px; font-weight: 700; letter-spacing: 0.04em; }

.audit-status {
    display: inline-flex;
    align-items: center;
    border: 1px solid transparent;
    border-radius: 999px;
    padding: 4px 9px;
    font-size: 11px;
    font-weight: 700;
    white-space: nowrap;
}

.status-planned { border-color: #eee4c7; background: #f6efdc; color: #86682a; }
.status-in-progress { border-color: #d4e5d6; background: #e6eee5; color: #315d47; }
.status-completed { border-color: #d4e5d6; background: #e6eee5; color: #315d47; }
.status-cancelled { border-color: #eddad5; background: #f5e2df; color: #a54236; }

.audit-row-meta { display: flex; flex-wrap: wrap; gap: 7px 18px; color: #718078; font-size: 11px; }
.audit-schedule { color: #315d47; font-weight: 600; }

.audit-row-link {
    display: inline-flex;
    min-height: 36px;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid #cbd5cc;
    border-radius: 5px;
    padding: 7px 11px;
    color: #315d47;
    font-size: 12px;
    font-weight: 650;
    text-decoration: none;
    transition: border-color 140ms ease, background-color 140ms ease, color 140ms ease;
}

.audit-row-link:hover { border-color: #91a996; background: #edf2e7; color: #173d2e; }

.audit-empty-state {
    display: flex;
    align-items: center;
    gap: 14px;
    border: 1px solid #dce3db;
    border-radius: 6px;
    background: #fffefb;
    padding: 20px;
}

.audit-empty-mark {
    display: grid;
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid #dce3db;
    border-radius: 50%;
    color: #315d47;
    font-family: 'Iowan Old Style', 'Palatino Linotype', 'Book Antiqua', Georgia, serif;
}

.audit-reset-button { margin-left: auto; color: #315d47; font-size: 12px; font-weight: 650; }
.audit-reset-button:hover { text-decoration: underline; text-underline-offset: 3px; }

@media (max-width: 639px) {
    .audit-register-hero { min-height: 200px; align-items: flex-start; flex-direction: column; padding: 22px 20px; }
    .audit-register-lines { top: -18%; right: -28%; width: 58vw; }
    .audit-summary-item { padding: 13px 12px; }
    .audit-summary-item:nth-child(odd) { border-left: 0; }
    .audit-summary-item:nth-child(n + 3) { border-top: 1px solid #e5eae3; }
    .audit-filter-bar { align-items: stretch; flex-direction: column; }
    .audit-status-filter { justify-content: space-between; border-top: 1px solid #e5eae3; border-left: 0; padding: 9px 0 0; }
    .audit-status-filter select { max-width: 60%; }
    .audit-register-row { align-items: flex-start; flex-direction: column; gap: 14px; padding: 16px; }
    .audit-row-link { width: 100%; }
    .audit-empty-state { align-items: flex-start; flex-wrap: wrap; }
    .audit-empty-state .audit-row-link { margin-left: 52px; }
}
</style>