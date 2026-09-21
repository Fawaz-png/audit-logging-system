<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    audit: {
        type: Object,
        required: true,
    },
});
</script>

<template>

    <Head :title="audit.title" />

    <div class="min-h-screen bg-gray-100 p-8">
        <div class="mx-auto max-w-5xl">
            <div class="mb-6 flex items-center justify-between">
                <Link :href="route('audits.index')" class="text-sm text-gray-600 hover:text-black">
                    ← Audits
                </Link>

                <div class="flex gap-3">
                    <Link :href="route('audits.edit', { audit: audit.id })"
                        class="rounded border bg-white px-4 py-2 text-sm">
                        Edit Audit
                    </Link>

                    <Link :href="route('audits.create')" class="rounded bg-black px-4 py-2 text-sm text-white">
                        Create Audit
                    </Link>
                </div>
            </div>

            <div class="rounded-lg bg-white p-8 shadow">
                <!-- Header -->
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <p class="text-sm text-gray-500">
                            {{ audit.reference }}
                        </p>

                        <h1 class="mt-1 text-3xl font-semibold">
                            {{ audit.title }}
                        </h1>
                    </div>

                    <span class="rounded bg-gray-100 px-3 py-1 text-sm">
                        {{ audit.status }}
                    </span>
                </div>

                <!-- Audit context -->
                <section class="mt-8">
                    <h2 class="text-xl font-semibold">
                        Audit Information
                    </h2>

                    <div class="mt-5 grid gap-6 md:grid-cols-2">
                        <div>
                            <h3 class="font-medium text-gray-600">
                                Audited Department
                            </h3>

                            <p class="mt-1 text-gray-900">
                                {{ audit.department?.name ?? 'Not specified' }}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-medium text-gray-600">
                                Audit Mode
                            </h3>

                            <p class="mt-1 text-gray-900">
                                {{ audit.audit_mode }}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-medium text-gray-600">
                                Methodology
                            </h3>

                            <p class="mt-1 text-gray-900">
                                {{ audit.methodology }}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-medium text-gray-600">
                                Location
                            </h3>

                            <p class="mt-1 text-gray-900">
                                {{ audit.location ?? 'Not specified' }}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-medium text-gray-600">
                                Previous Audit
                            </h3>

                            <p class="mt-1 text-gray-900">
                                {{
                                    audit.previous_audit_reference
                                    ?? 'None recorded'
                                }}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-medium text-gray-600">
                                Created By
                            </h3>

                            <p class="mt-1 text-gray-900">
                                {{ audit.creator?.name ?? 'Unknown' }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Objective -->
                <section class="mt-8 border-t pt-8">
                    <h2 class="text-xl font-semibold">
                        Objective
                    </h2>

                    <p class="mt-2 whitespace-pre-line text-gray-700">
                        {{ audit.objective }}
                    </p>
                </section>

                <!-- Scope -->
                <section class="mt-8 border-t pt-8">
                    <h2 class="text-xl font-semibold">
                        Scope
                    </h2>

                    <p class="mt-2 whitespace-pre-line text-gray-700">
                        {{ audit.scope }}
                    </p>
                </section>

                <!-- Dates -->
                <section class="mt-8 border-t pt-8">
                    <h2 class="text-xl font-semibold">
                        Audit Dates
                    </h2>

                    <div class="mt-5 grid gap-6 md:grid-cols-2">
                        <div>
                            <h3 class="font-medium text-gray-600">
                                Start Date
                            </h3>

                            <p class="mt-1 text-gray-900">
                                {{ audit.start_date }}
                            </p>
                        </div>

                        <div>
                            <h3 class="font-medium text-gray-600">
                                End Date
                            </h3>

                            <p class="mt-1 text-gray-900">
                                {{ audit.end_date ?? 'Not set' }}
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Team -->
                <section class="mt-8 border-t pt-8">
                    <h2 class="text-xl font-semibold">
                        Audit Team
                    </h2>

                    <!-- Lead Auditor -->
                    <div class="mt-5">
                        <h3 class="font-medium text-gray-600">
                            Lead Auditor
                        </h3>

                        <div v-for="auditor in audit.auditors.filter(
                            user =>
                                user.pivot?.assignment_type ===
                                'LEAD_AUDITOR'
                        )" :key="auditor.id" class="mt-2 rounded border p-4">
                            <p class="font-medium">
                                {{ auditor.name }}
                            </p>

                            <p class="text-sm text-gray-600">
                                {{ auditor.email }}
                            </p>
                        </div>

                        <p v-if="
                            !audit.auditors.some(
                                user =>
                                    user.pivot?.assignment_type ===
                                    'LEAD_AUDITOR'
                            )
                        " class="mt-2 text-gray-500">
                            No lead auditor assigned.
                        </p>
                    </div>

                    <!-- Supporting Auditors -->
                    <div class="mt-6">
                        <h3 class="font-medium text-gray-600">
                            Supporting Auditors
                        </h3>

                        <div v-for="auditor in audit.auditors.filter(
                            user =>
                                user.pivot?.assignment_type ===
                                'SUPPORTING_AUDITOR'
                        )" :key="auditor.id" class="mt-2 rounded border p-4">
                            <p class="font-medium">
                                {{ auditor.name }}
                            </p>

                            <p class="text-sm text-gray-600">
                                {{ auditor.email }}
                            </p>
                        </div>

                        <p v-if="
                            !audit.auditors.some(
                                user =>
                                    user.pivot?.assignment_type ===
                                    'SUPPORTING_AUDITOR'
                            )
                        " class="mt-2 text-gray-500">
                            No supporting auditors assigned.
                        </p>
                    </div>

                    <!-- Auditees -->
                    <div class="mt-6">
                        <h3 class="font-medium text-gray-600">
                            Auditees
                        </h3>

                        <div v-for="auditee in audit.auditors.filter(
                            user =>
                                user.pivot?.assignment_type ===
                                'AUDITEE'
                        )" :key="auditee.id" class="mt-2 rounded border p-4">
                            <p class="font-medium">
                                {{ auditee.name }}
                            </p>

                            <p class="text-sm text-gray-600">
                                {{ auditee.email }}
                            </p>
                        </div>

                        <p v-if="
                            !audit.auditors.some(
                                user =>
                                    user.pivot?.assignment_type ===
                                    'AUDITEE'
                            )
                        " class="mt-2 text-gray-500">
                            No auditees assigned.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>