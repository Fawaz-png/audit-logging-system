<script setup>
import { computed, nextTick, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue';

const props = defineProps({
    departments: {
        type: Array,
        required: true,
    },

    users: {
        type: Array,
        required: true,
    },
});

const form = useForm({
    reference: '',
    title: '',
    objective: '',
    scope: '',

    department_id: '',
    audit_mode: '',
    methodology: '',
    location: '',
    previous_audit_reference: '',

    start_date: '',
    end_date: '',

    lead_auditor_id: '',
    supporting_auditor_ids: [],
    auditee_ids: [],
});

const activeStep = ref(0);
const stepError = ref('');
const steps = [
    { id: 'audit-information', number: '01', label: 'Information' },
    { id: 'audit-context', number: '02', label: 'Context & schedule' },
    { id: 'audit-team', number: '03', label: 'Team' },
];

const leadAuditors = computed(() => {
    return props.users.filter(
        user => user.role?.slug === 'auditor'
    );
});

const supportingAuditors = computed(() => {
    return props.users.filter(
        user => user.role?.slug === 'auditor'
            && Number(user.id) !== Number(form.lead_auditor_id)
    );
});

const auditees = computed(() => {
    const departmentId = form.department_id ? Number(form.department_id) : null;

    return props.users.filter(user => {
        if (user.role?.slug !== 'auditee') {
            return false;
        }

        if (Number(user.id) === Number(form.lead_auditor_id)) {
            return false;
        }

        if (form.supporting_auditor_ids.includes(user.id)) {
            return false;
        }

        if (departmentId !== null && Number(user.department_id) !== departmentId) {
            return false;
        }

        return true;
    });
});

const normalizeTeamAssignments = () => {
    form.supporting_auditor_ids = form.supporting_auditor_ids.filter(
        userId => Number(userId) !== Number(form.lead_auditor_id)
    );
    form.auditee_ids = form.auditee_ids.filter(userId => {
        return Number(userId) !== Number(form.lead_auditor_id)
            && !form.supporting_auditor_ids.some(supportingId => Number(supportingId) === Number(userId));
    });
};

const submit = () => {
    const requiredSteps = [
        ['reference', 'title', 'objective', 'scope'],
        ['department_id', 'audit_mode', 'methodology', 'start_date'],
        ['lead_auditor_id'],
    ];
    const requiredValues = {
        reference: form.reference,
        title: form.title,
        objective: form.objective,
        scope: form.scope,
        department_id: form.department_id,
        audit_mode: form.audit_mode,
        methodology: form.methodology,
        start_date: form.start_date,
        lead_auditor_id: form.lead_auditor_id,
    };

    const firstIncompleteStep = requiredSteps.findIndex(fields =>
        fields.some(field => !String(requiredValues[field] ?? '').trim())
    );

    if (firstIncompleteStep !== -1) {
        activeStep.value = firstIncompleteStep;
        stepError.value = 'Complete the required fields in this section before creating the audit.';
        return;
    }

    stepError.value = '';
    form.post(route('audits.store'), {
        onError: async () => {
            await nextTick();
            const fieldByStep = {
                reference: 0, title: 0, objective: 0, scope: 0,
                department_id: 1, audit_mode: 1, methodology: 1,
                start_date: 1, end_date: 1,
                lead_auditor_id: 2, supporting_auditor_ids: 2, auditee_ids: 2,
            };
            const firstError = Object.keys(form.errors)[0]?.split('.')[0];
            const errorStep = fieldByStep[firstError];

            if (errorStep !== undefined) {
                activeStep.value = errorStep;
                await nextTick();
                document.querySelector('[aria-invalid="true"]')?.focus();
            }
        },
    });
};

const selectStep = (step) => {
    activeStep.value = step;
    stepError.value = '';
};

const continueStep = () => {
    const requiredByStep = [
        ['reference', 'title', 'objective', 'scope'],
        ['department_id', 'audit_mode', 'methodology', 'start_date'],
        ['lead_auditor_id'],
    ];
    const values = {
        reference: form.reference,
        title: form.title,
        objective: form.objective,
        scope: form.scope,
        department_id: form.department_id,
        audit_mode: form.audit_mode,
        methodology: form.methodology,
        start_date: form.start_date,
        lead_auditor_id: form.lead_auditor_id,
    };
    const missingField = requiredByStep[activeStep.value].find(field => !String(values[field] ?? '').trim());

    if (missingField) {
        stepError.value = 'Complete the required fields in this section before continuing.';
        nextTick(() => document.getElementById(missingField)?.focus());
        return;
    }

    if (activeStep.value < steps.length - 1) {
        selectStep(activeStep.value + 1);
    }
};
</script>

<template>

    <Head title="Create Audit" />

    <AuthenticatedLayout>
        <template #header>
            Create Audit
        </template>

        <div class="max-w-5xl space-y-6">
            <div class="space-y-3">
                <Link :href="route('audits.index')" class="app-back-link">
                    <span aria-hidden="true">←</span> Audit register
                </Link>
                <div>
                    <p class="app-section-kicker">NEW ENGAGEMENT</p>
                    <h2 class="app-page-title mt-1 text-xl font-semibold text-gray-900">Create Audit</h2>
                    <p class="mt-1 text-sm text-gray-600">Capture the purpose, plan, and initial team for this audit.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="audit-form space-y-5">
                <nav class="audit-form-steps" aria-label="Audit form sections">
                    <button v-for="(step, index) in steps" :key="step.id" type="button"
                        :aria-current="activeStep === index ? 'step' : undefined"
                        :class="{ 'is-active': activeStep === index }" @click="selectStep(index)">
                        <span>{{ step.number }}</span> {{ step.label }}
                    </button>
                </nav>

                <div v-if="stepError" class="audit-error-summary" role="alert" aria-live="assertive">
                    <p class="text-sm font-semibold">Some details need your attention</p>
                    <p class="mt-1 text-xs">{{ stepError }}</p>
                </div>

                <!-- Basic information -->
                <section v-if="activeStep === 0" id="audit-information">
                    <div class="audit-section-heading">
                        <div>
                            <h2 class="text-xl font-semibold">Audit Information</h2>
                            <p class="mt-1 text-sm text-gray-600">Give the engagement a clear identity and purpose.</p>
                        </div>
                        <span class="audit-required-note">Required fields marked by validation</span>
                    </div>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div>
                            <label for="reference" class="mb-1 block font-medium">
                                Reference <span class="text-red-700">*</span>
                            </label>

                            <input id="reference" v-model="form.reference" type="text" required :aria-invalid="Boolean(form.errors.reference)" placeholder="AUD-2026-001"
                                class="w-full rounded border px-3 py-2" />

                            <p v-if="form.errors.reference" class="mt-1 text-sm text-red-600">
                                {{ form.errors.reference }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="title" class="mb-1 block font-medium">
                                Title <span class="text-red-700">*</span>
                            </label>

                            <input id="title" v-model="form.title" type="text" required :aria-invalid="Boolean(form.errors.title)"
                                class="w-full rounded border px-3 py-2" />

                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label for="objective" class="mb-1 block font-medium">
                                Objective <span class="text-red-700">*</span>
                            </label>

                            <textarea id="objective" v-model="form.objective" rows="3" required :aria-invalid="Boolean(form.errors.objective)"
                                class="w-full rounded border px-3 py-2"></textarea>

                            <p v-if="form.errors.objective" class="mt-1 text-sm text-red-600">
                                {{ form.errors.objective }}
                            </p>
                        </div>

                        <div>
                            <label for="scope" class="mb-1 block font-medium">
                                Scope <span class="text-red-700">*</span>
                            </label>

                            <textarea id="scope" v-model="form.scope" rows="3" required :aria-invalid="Boolean(form.errors.scope)"
                                class="w-full rounded border px-3 py-2"></textarea>

                            <p v-if="form.errors.scope" class="mt-1 text-sm text-red-600">
                                {{ form.errors.scope }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Audit context -->
                <section v-if="activeStep === 1" id="audit-context">
                    <div class="audit-section-heading">
                        <div>
                            <h2 class="text-xl font-semibold">Context & schedule</h2>
                            <p class="mt-1 text-sm text-gray-600">Set where the work happens, how it will be conducted, and its planned dates.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-5">
                        <div>
                            <label for="department_id" class="mb-1 block font-medium">
                                Audited Department <span class="text-red-700">*</span>
                            </label>

                            <select id="department_id" v-model="form.department_id" required :aria-invalid="Boolean(form.errors.department_id)"
                                class="w-full rounded border px-3 py-2">
                                <option value="">
                                    Select department
                                </option>

                                <option v-for="department in departments" :key="department.id" :value="department.id">
                                    {{ department.name }}
                                    ({{ department.code }})
                                </option>
                            </select>

                            <p v-if="form.errors.department_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.department_id }}
                            </p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="audit_mode" class="mb-1 block font-medium">
                                    Audit Mode <span class="text-red-700">*</span>
                                </label>

                                <select id="audit_mode" v-model="form.audit_mode" required :aria-invalid="Boolean(form.errors.audit_mode)"
                                    class="w-full rounded border px-3 py-2">
                                    <option value="">
                                        Select mode
                                    </option>

                                    <option value="ONSITE">
                                        Onsite
                                    </option>

                                    <option value="REMOTE">
                                        Remote
                                    </option>

                                    <option value="HYBRID">
                                        Hybrid
                                    </option>
                                </select>

                                <p v-if="form.errors.audit_mode" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.audit_mode }}
                                </p>
                            </div>

                            <div>
                                <label for="methodology" class="mb-1 block font-medium">
                                    Methodology <span class="text-red-700">*</span>
                                </label>

                                <select id="methodology" v-model="form.methodology" required :aria-invalid="Boolean(form.errors.methodology)"
                                    class="w-full rounded border px-3 py-2">
                                    <option value="">
                                        Select methodology
                                    </option>

                                    <option value="RISK_BASED">
                                        Risk Based
                                    </option>

                                    <option value="COMPLIANCE_BASED">
                                        Compliance Based
                                    </option>

                                    <option value="HYBRID">
                                        Hybrid
                                    </option>
                                </select>

                                <p v-if="form.errors.methodology" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.methodology }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="location" class="mb-1 block font-medium">
                                Location
                            </label>

                            <input id="location" v-model="form.location" type="text" placeholder="e.g. Head Office"
                                class="w-full rounded border px-3 py-2" />

                            <p v-if="form.errors.location" class="mt-1 text-sm text-red-600">
                                {{ form.errors.location }}
                            </p>
                        </div>

                        <div>
                            <label for="previous_audit_reference" class="mb-1 block font-medium">
                                Previous Audit Reference
                            </label>

                            <input id="previous_audit_reference" v-model="form.previous_audit_reference" type="text"
                                placeholder="e.g. AUD-2025-004" class="w-full rounded border px-3 py-2" />

                            <p v-if="form.errors.previous_audit_reference" class="mt-1 text-sm text-red-600">
                                {{ form.errors.previous_audit_reference }}
                            </p>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="start_date" class="mb-1 block font-medium">
                                    Start Date <span class="text-red-700">*</span>
                                </label>

                                <input id="start_date" v-model="form.start_date" type="date" required :aria-invalid="Boolean(form.errors.start_date)"
                                    class="w-full rounded border px-3 py-2" />

                                <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.start_date }}
                                </p>
                            </div>

                            <div>
                                <label for="end_date" class="mb-1 block font-medium">
                                    End Date
                                </label>

                                <input id="end_date" v-model="form.end_date" type="date"
                                    :min="form.start_date || undefined" class="w-full rounded border px-3 py-2" />

                                <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.end_date }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Team -->
                <section v-if="activeStep === 2" id="audit-team">
                    <div class="audit-section-heading">
                        <div>
                            <h2 class="text-xl font-semibold">Initial audit team</h2>
                            <p class="mt-1 text-sm text-gray-600">Choose a lead auditor, then add supporting auditors and auditees as needed.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-5">
                        <!-- Lead auditor -->
                        <div>
                            <label for="lead_auditor_id" class="mb-1 block font-medium">
                                Lead Auditor <span class="text-red-700">*</span>
                            </label>

                            <select id="lead_auditor_id" v-model="form.lead_auditor_id" required :aria-invalid="Boolean(form.errors.lead_auditor_id)" @change="normalizeTeamAssignments"
                                class="w-full rounded border px-3 py-2">
                                <option value="" disabled>
                                    Select lead auditor
                                </option>

                                <option v-for="user in leadAuditors" :key="user.id" :value="user.id">
                                    {{ user.name }} — {{ user.email }}
                                </option>
                            </select>

                            <p v-if="form.errors.lead_auditor_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.lead_auditor_id }}
                            </p>
                            <p v-if="!leadAuditors.length" class="mt-2 text-sm text-amber-700">
                                No active auditors are available to assign as lead.
                            </p>
                        </div>

                        <!-- Supporting auditors -->
                        <div>
                            <label for="supporting_auditor_ids" class="mb-1 block font-medium">
                                Supporting Auditors
                            </label>

                            <div class="audit-member-picker">
                                <label v-for="user in supportingAuditors" :key="user.id" class="audit-member-option">
                                    <input v-model="form.supporting_auditor_ids" type="checkbox" :value="user.id" @change="normalizeTeamAssignments" />
                                    <span class="audit-member-initial">{{ user.name?.charAt(0)?.toUpperCase() ?? '?' }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-gray-800">{{ user.name }}</span>
                                        <span class="block truncate text-xs text-gray-500">{{ user.email }}</span>
                                    </span>
                                    <span class="audit-selected-mark" aria-hidden="true">✓</span>
                                </label>
                                <p v-if="!supportingAuditors.length" class="p-4 text-sm text-gray-500">No active supporting auditors available.</p>
                            </div>

                            <p v-if="form.errors.supporting_auditor_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.supporting_auditor_ids }}
                            </p>
                        </div>

                        <!-- Auditees -->
                        <div>
                            <label for="auditee_ids" class="mb-1 block font-medium">
                                Auditees
                            </label>

                            <div class="audit-member-picker">
                                <label v-for="user in auditees" :key="user.id" class="audit-member-option">
                                    <input v-model="form.auditee_ids" type="checkbox" :value="user.id" @change="normalizeTeamAssignments" />
                                    <span class="audit-member-initial">{{ user.name?.charAt(0)?.toUpperCase() ?? '?' }}</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-gray-800">{{ user.name }}</span>
                                        <span class="block truncate text-xs text-gray-500">{{ user.email }}</span>
                                    </span>
                                    <span class="audit-selected-mark" aria-hidden="true">✓</span>
                                </label>
                                <p v-if="!auditees.length" class="p-4 text-sm text-gray-500">No active auditees available.</p>
                            </div>

                            <p v-if="form.errors.auditee_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.auditee_ids }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Actions -->
                <div class="app-form-actions audit-form-actions">
                    <div class="audit-form-action-navigation">
                        <button v-if="activeStep > 0" type="button" class="app-action-secondary"
                            :disabled="form.processing" @click="selectStep(activeStep - 1)">
                            <span aria-hidden="true">←</span> Previous
                        </button>
                        <Link :href="route('audits.index')" class="app-action-secondary">
                            Cancel
                        </Link>
                    </div>

                    <button v-if="activeStep < steps.length - 1" type="button" :disabled="form.processing"
                        class="app-action-primary" @click="continueStep">
                        Continue <span aria-hidden="true">→</span>
                    </button>

                    <button v-else type="submit" :disabled="form.processing || !leadAuditors.length"
                        class="app-action-primary">
                        {{ form.processing ? 'Creating...' : 'Create audit' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.audit-form-steps {
    position: sticky;
    top: 76px;
    z-index: 10;
    display: flex;
    gap: 4px;
    overflow-x: auto;
    border: 1px solid #dce3db;
    border-radius: 5px;
    background: rgb(255 254 251 / 94%);
    padding: 7px;
    backdrop-filter: blur(10px);
}

.audit-form-steps button {
    display: inline-flex;
    min-height: 40px;
    flex: 1 0 auto;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border-radius: 4px;
    border: 0;
    background: transparent;
    padding: 7px 12px;
    color: #526258;
    font-size: 12px;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
}

.audit-form-steps button:hover,
.audit-form-steps button.is-active { background: #edf2e7; color: #204b3d; }
.audit-form-steps button:focus-visible { outline: 2px solid #315d47; outline-offset: 2px; }
.audit-form-steps button span { color: #54705f; font-size: 12px; font-variant-numeric: tabular-nums; font-weight: 700; }

.audit-error-summary {
    border: 1px solid #e8c6b9;
    border-left: 3px solid #b65e42;
    border-radius: 5px;
    background: #fbf1ed;
    padding: 12px 15px;
    color: #884632;
}

.audit-section-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
}

.audit-required-note { color: #718078; font-size: 11px; }

.audit-member-picker {
    display: grid;
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #dce3db;
    border-radius: 5px;
    background: #fffefb;
}

.audit-member-option {
    display: flex !important;
    min-width: 0;
    align-items: center;
    gap: 10px;
    margin: 0 !important;
    border-bottom: 1px solid #edf0eb;
    padding: 10px 12px;
    cursor: pointer;
    transition: background-color 120ms ease;
}

.audit-member-option:last-of-type { border-bottom: 0; }
.audit-member-option:hover { background: #f6f8f3; }
.audit-member-option:has(input:checked) { background: #edf2e7; }

.audit-member-option input {
    width: 15px;
    height: 15px;
    flex: 0 0 auto;
    accent-color: #315d47;
}

.audit-member-initial {
    display: grid;
    width: 31px;
    height: 31px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid #dce3db;
    border-radius: 50%;
    background: #fffefb;
    color: #315d47;
    font-size: 11px;
    font-weight: 700;
}

.audit-selected-mark { color: #315d47; font-size: 15px; opacity: 0; }
.audit-member-option:has(input:checked) .audit-selected-mark { opacity: 1; }

.audit-form-actions {
    position: sticky;
    bottom: 0;
    z-index: 5;
    margin: 0 -8px;
    border-top: 1px solid #dce3db;
    background: rgb(255 254 251 / 94%);
    padding: 8px 10px max(8px, env(safe-area-inset-bottom));
    justify-content: space-between;
    backdrop-filter: blur(12px);
}

.audit-form-action-navigation {
    display: flex;
    align-items: center;
    gap: 8px;
}

#audit-information,
#audit-context,
#audit-team { scroll-margin-top: 140px; }

@media (max-width: 639px) {
    .audit-form-steps { top: 70px; }
    .audit-form-steps button { justify-content: flex-start; padding-inline: 8px; }
    .audit-section-heading { flex-direction: column; }
    .audit-form-actions { margin-inline: -4px; gap: 6px; flex-wrap: wrap; }
    .audit-form-action-navigation { flex: 1 1 auto; }
}
</style>