<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    audit: {
        type: Object,
        required: true,
    },

    departments: {
        type: Array,
        required: true,
    },

    users: {
        type: Array,
        required: true,
    },
});

const leadAuditor = props.audit.auditors.find(
    user => user.pivot?.assignment_type === 'LEAD_AUDITOR'
);

const supportingAuditors = props.audit.auditors
    .filter(
        user => user.pivot?.assignment_type === 'SUPPORTING_AUDITOR'
    )
    .map(user => user.id);

const auditees = props.audit.auditors
    .filter(
        user => user.pivot?.assignment_type === 'AUDITEE'
    )
    .map(user => user.id);

const form = useForm({
    title: props.audit.title ?? '',
    objective: props.audit.objective ?? '',
    scope: props.audit.scope ?? '',

    department_id: props.audit.department_id ?? '',
    audit_mode: props.audit.audit_mode ?? '',
    methodology: props.audit.methodology ?? '',
    location: props.audit.location ?? '',
    previous_audit_reference:
        props.audit.previous_audit_reference ?? '',

    start_date: props.audit.start_date
        ? props.audit.start_date.substring(0, 10)
        : '',

    end_date: props.audit.end_date
        ? props.audit.end_date.substring(0, 10)
        : '',

    lead_auditor_id: leadAuditor?.id ?? '',
    supporting_auditor_ids: supportingAuditors,
    auditee_ids: auditees,
});

const leadAuditors = computed(() => {
    return props.users.filter(
        user => user.role?.slug === 'lead-auditor'
    );
});

const supportingAuditorUsers = computed(() => {
    return props.users.filter(
        user => user.role?.slug === 'supporting-auditor'
    );
});

const auditeeUsers = computed(() => {
    return props.users.filter(
        user => user.role?.slug === 'auditee'
    );
});

const submit = () => {
    form.put(route('audits.update', { audit: props.audit.id }));
};
</script>

<template>

    <Head :title="`Edit ${audit.title}`" />

    <div class="min-h-screen bg-gray-100 p-8">
        <div class="mx-auto max-w-4xl">

            <div class="mb-6">
                <Link :href="route('audits.show', { audit: audit.id })" class="text-sm text-gray-600 hover:text-black">
                    ← Back to Audit
                </Link>

                <h1 class="mt-3 text-3xl font-semibold">
                    Edit Audit
                </h1>

                <p class="mt-1 text-gray-600">
                    Update the audit context and assigned team.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-8 rounded-lg bg-white p-8 shadow">

                <!-- Reference -->
                <section>
                    <h2 class="text-xl font-semibold">
                        Audit Information
                    </h2>

                    <div class="mt-5 space-y-5">

                        <div>
                            <label class="mb-1 block font-medium">
                                Reference
                            </label>

                            <input :value="audit.reference" type="text" disabled
                                class="w-full rounded border bg-gray-100 px-3 py-2 text-gray-600" />

                            <p class="mt-1 text-xs text-gray-500">
                                Audit references cannot be changed after creation.
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

                <!-- Context -->
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

                            <input id="location" v-model="form.location" type="text"
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
                                class="w-full rounded border px-3 py-2" />

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
                        Audit Team
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Update the people assigned to this audit.
                    </p>

                    <div class="mt-5 space-y-5">

                        <!-- Lead -->
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

                        <!-- Supporting -->
                        <div>
                            <label for="supporting_auditor_ids" class="mb-1 block font-medium">
                                Supporting Auditors
                            </label>

                            <select id="supporting_auditor_ids" v-model="form.supporting_auditor_ids" multiple
                                class="min-h-32 w-full rounded border px-3 py-2">
                                <option v-for="user in supportingAuditorUsers" :key="user.id" :value="user.id">
                                    {{ user.name }} — {{ user.email }}
                                </option>
                            </select>

                            <p class="mt-1 text-xs text-gray-500">
                                Hold Ctrl (Windows/Linux) or Command (Mac)
                                to select multiple users.
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
                                <option v-for="user in auditeeUsers" :key="user.id" :value="user.id">
                                    {{ user.name }} — {{ user.email }}
                                </option>
                            </select>

                            <p class="mt-1 text-xs text-gray-500">
                                Hold Ctrl (Windows/Linux) or Command (Mac)
                                to select multiple users.
                            </p>

                            <p v-if="form.errors.auditee_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.auditee_ids }}
                            </p>
                        </div>

                    </div>
                </section>

                <!-- Actions -->
                <div class="flex justify-end gap-3 border-t pt-6">

                    <Link :href="route('audits.show', { audit: audit.id })" class="rounded border px-4 py-2">
                        Cancel
                    </Link>

                    <button type="submit" :disabled="form.processing"
                        class="rounded bg-black px-5 py-2 text-white disabled:opacity-50">
                        {{
                            form.processing
                                ? 'Saving...'
                                : 'Save Changes'
                        }}
                    </button>

                </div>

            </form>
        </div>
    </div>
</template>