<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

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

const leadAuditors = computed(() => {
    return props.users.filter(
        user => user.role?.slug === 'lead-auditor'
    );
});

const supportingAuditors = computed(() => {
    return props.users.filter(
        user => user.role?.slug === 'supporting-auditor'
    );
});

const auditees = computed(() => {
    return props.users.filter(
        user => user.role?.slug === 'auditee'
    );
});

const submit = () => {
    form.post(route('audits.store'));
};
</script>

<template>

    <Head title="Create Audit" />

    <div class="min-h-screen bg-gray-100 p-8">
        <div class="mx-auto max-w-4xl">
            <div class="mb-6">
                <Link :href="route('audits.index')" class="text-sm text-gray-600 hover:text-black">
                    ← Audits
                </Link>

                <p>
                <Link :href="route('dashboard')" class="text-sm text-gray-600 hover:text-black">
                   ← Dashboard
                </Link>
                </p>

                <h1 class="mt-3 text-3xl font-semibold">
                    Create Audit
                </h1>

                <p class="mt-1 text-gray-600">
                    Record the audit context and initial team assignment.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-8 rounded-lg bg-white p-8 shadow">
                <!-- Basic information -->
                <section>
                    <h2 class="text-xl font-semibold">
                        Audit Information
                    </h2>

                    <div class="mt-5 space-y-5">
                        <div>
                            <label for="reference" class="mb-1 block font-medium">
                                Reference
                            </label>

                            <input id="reference" v-model="form.reference" type="text" placeholder="AUD-2026-001"
                                class="w-full rounded border px-3 py-2" />

                            <p v-if="form.errors.reference" class="mt-1 text-sm text-red-600">
                                {{ form.errors.reference }}
                            </p>
                        </div>

                        <div>
                            <label for="title" class="mb-1 block font-medium">
                                Title
                            </label>

                            <input id="title" v-model="form.title" type="text"
                                class="w-full rounded border px-3 py-2" />

                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                                {{ form.errors.title }}
                            </p>
                        </div>

                        <div>
                            <label for="objective" class="mb-1 block font-medium">
                                Objective
                            </label>

                            <textarea id="objective" v-model="form.objective" rows="4"
                                class="w-full rounded border px-3 py-2"></textarea>

                            <p v-if="form.errors.objective" class="mt-1 text-sm text-red-600">
                                {{ form.errors.objective }}
                            </p>
                        </div>

                        <div>
                            <label for="scope" class="mb-1 block font-medium">
                                Scope
                            </label>

                            <textarea id="scope" v-model="form.scope" rows="4"
                                class="w-full rounded border px-3 py-2"></textarea>

                            <p v-if="form.errors.scope" class="mt-1 text-sm text-red-600">
                                {{ form.errors.scope }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Audit context -->
                <section>
                    <h2 class="text-xl font-semibold">
                        Audit Context
                    </h2>

                    <div class="mt-5 space-y-5">
                        <div>
                            <label for="department_id" class="mb-1 block font-medium">
                                Audited Department
                            </label>

                            <select id="department_id" v-model="form.department_id"
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
                                    Audit Mode
                                </label>

                                <select id="audit_mode" v-model="form.audit_mode"
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
                                    Methodology
                                </label>

                                <select id="methodology" v-model="form.methodology"
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
                                    Start Date
                                </label>

                                <input id="start_date" v-model="form.start_date" type="date"
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
                                    class="w-full rounded border px-3 py-2" />

                                <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.end_date }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Team -->
                <section>
                    <h2 class="text-xl font-semibold">
                        Initial Audit Team
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Assign the people involved in this audit context.
                    </p>

                    <div class="mt-5 space-y-5">
                        <!-- Lead auditor -->
                        <div>
                            <label for="lead_auditor_id" class="mb-1 block font-medium">
                                Lead Auditor
                            </label>

                            <select id="lead_auditor_id" v-model="form.lead_auditor_id"
                                class="w-full rounded border px-3 py-2">
                                <option value="">
                                    Select lead auditor
                                </option>

                                <option v-for="user in leadAuditors" :key="user.id" :value="user.id">
                                    {{ user.name }} — {{ user.email }}
                                </option>
                            </select>

                            <p v-if="form.errors.lead_auditor_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.lead_auditor_id }}
                            </p>
                        </div>

                        <!-- Supporting auditors -->
                        <div>
                            <label for="supporting_auditor_ids" class="mb-1 block font-medium">
                                Supporting Auditors
                            </label>

                            <select id="supporting_auditor_ids" v-model="form.supporting_auditor_ids" multiple
                                class="min-h-32 w-full rounded border px-3 py-2">
                                <option v-for="user in supportingAuditors" :key="user.id" :value="user.id">
                                    {{ user.name }} — {{ user.email }}
                                </option>
                            </select>

                            <p class="mt-1 text-xs text-gray-500">
                                Hold Ctrl (Windows/Linux) or Command (Mac) to select multiple users.
                            </p>

                            <p v-if="form.errors.supporting_auditor_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.supporting_auditor_ids }}
                            </p>
                        </div>

                        <!-- Auditees -->
                        <div>
                            <label for="auditee_ids" class="mb-1 block font-medium">
                                Auditees
                            </label>

                            <select id="auditee_ids" v-model="form.auditee_ids" multiple
                                class="min-h-32 w-full rounded border px-3 py-2">
                                <option v-for="user in auditees" :key="user.id" :value="user.id">
                                    {{ user.name }} — {{ user.email }}
                                </option>
                            </select>

                            <p class="mt-1 text-xs text-gray-500">
                                Hold Ctrl (Windows/Linux) or Command (Mac) to select multiple users.
                            </p>

                            <p v-if="form.errors.auditee_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.auditee_ids }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Actions -->
                <div class="flex justify-end gap-3 border-t pt-6">
                    <Link :href="route('audits.index')" class="rounded border px-4 py-2">
                        Cancel
                    </Link>

                    <button type="submit" :disabled="form.processing"
                        class="rounded bg-black px-5 py-2 text-white disabled:opacity-50">
                        {{
                            form.processing
                                ? 'Creating...'
                                : 'Create Audit'
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>