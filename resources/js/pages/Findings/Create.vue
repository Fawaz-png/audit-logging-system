<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue'

const props = defineProps({
    audit: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    reference: '',
    title: '',
    description: '',
    criteria: '',
    effect: '',
    recommendation: '',
    process: '',
    risk_rating: '',
    evidence_description: '',
    evidence_file: null,
})
const evidenceInput = ref(null)

const formatFileSize = (bytes) => bytes < 1024 * 1024
    ? `${Math.ceil(bytes / 1024)} KB`
    : `${(bytes / (1024 * 1024)).toFixed(1)} MB`

const selectEvidenceFile = (event) => {
    form.evidence_file = event.target.files?.[0] ?? null
    form.clearErrors('evidence_file')
}

const clearEvidenceFile = () => {
    form.evidence_file = null
    form.clearErrors('evidence_file')

    if (evidenceInput.value) {
        evidenceInput.value.value = ''
    }
}

const submit = () => {
    form.post(route('findings.store', props.audit.id), {
        forceFormData: true,
    })
}
</script>

<template>
    <Head title="Register Finding" />

    <AuthenticatedLayout>
        <template #header>
            Register Finding
        </template>

        <div class="mx-auto max-w-4xl space-y-6">
            <div>
                <Link :href="route('audits.show', audit.id)" class="text-sm text-gray-600 hover:text-black">
                    Back to audit
                </Link>
                <h1 class="app-page-title mt-3 text-xl font-semibold text-gray-900">
                    Register Finding
                </h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ audit.reference }} · {{ audit.title }}
                </p>
            </div>

            <form class="space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
                <section class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-base font-semibold text-gray-900">Finding details</h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label for="finding-reference" class="block text-sm font-medium text-gray-700">Reference</label>
                            <input id="finding-reference" v-model="form.reference" required maxlength="255" type="text"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                                placeholder="FND-2026-001" />
                            <p v-if="form.errors.reference" class="mt-1 text-xs text-red-600">{{ form.errors.reference }}</p>
                        </div>

                        <div>
                            <label for="finding-process" class="block text-sm font-medium text-gray-700">Process</label>
                            <input id="finding-process" v-model="form.process" required maxlength="255" type="text"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]" />
                            <p v-if="form.errors.process" class="mt-1 text-xs text-red-600">{{ form.errors.process }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="finding-title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input id="finding-title" v-model="form.title" required maxlength="255" type="text"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]" />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="finding-description" class="block text-sm font-medium text-gray-700">Description / condition</label>
                            <textarea id="finding-description" v-model="form.description" required rows="4"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="finding-criteria" class="block text-sm font-medium text-gray-700">Criteria</label>
                            <textarea id="finding-criteria" v-model="form.criteria" required rows="3"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                                placeholder="Policy, standard, or control requirement not met"></textarea>
                            <p v-if="form.errors.criteria" class="mt-1 text-xs text-red-600">{{ form.errors.criteria }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="finding-effect" class="block text-sm font-medium text-gray-700">Effect / implication</label>
                            <textarea id="finding-effect" v-model="form.effect" required rows="3"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"></textarea>
                            <p v-if="form.errors.effect" class="mt-1 text-xs text-red-600">{{ form.errors.effect }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="finding-recommendation" class="block text-sm font-medium text-gray-700">Auditor recommendation</label>
                            <textarea id="finding-recommendation" v-model="form.recommendation" required rows="3"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                                placeholder="Recommend the control outcome; the auditee will propose the corrective action."></textarea>
                            <p v-if="form.errors.recommendation" class="mt-1 text-xs text-red-600">{{ form.errors.recommendation }}</p>
                        </div>

                        <div>
                            <label for="finding-risk" class="block text-sm font-medium text-gray-700">Risk rating</label>
                            <select id="finding-risk" v-model="form.risk_rating" required
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]">
                                <option value="" disabled>Select risk rating</option>
                                <option value="LOW">Low</option>
                                <option value="MEDIUM">Medium</option>
                                <option value="HIGH">High</option>
                                <option value="CRITICAL">Critical</option>
                            </select>
                            <p v-if="form.errors.risk_rating" class="mt-1 text-xs text-red-600">{{ form.errors.risk_rating }}</p>
                        </div>
                    </div>
                </section>

                <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Finding evidence</h2>
                        <p class="mt-1 text-sm text-gray-600">Attach the audit workpaper or record supporting this finding.</p>
                    </div>

                    <div>
                        <label for="finding-evidence-file" class="block text-sm font-medium text-gray-700">Evidence file</label>
                        <input id="finding-evidence-file" ref="evidenceInput" required type="file"
                            accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt"
                            class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 file:ring-1 file:ring-gray-300"
                            @change="selectEvidenceFile" />
                        <p class="mt-1 text-xs text-gray-500">PDF, image, office document, CSV, or text file, up to 10 MB.</p>
                        <div v-if="form.evidence_file" class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded border border-gray-200 bg-gray-50 px-3 py-2">
                            <p class="min-w-0 break-all text-xs text-gray-700">
                                {{ form.evidence_file.name }} · {{ formatFileSize(form.evidence_file.size) }}
                            </p>
                            <button type="button" :disabled="form.processing" class="text-xs font-semibold text-[#315d47] underline underline-offset-2 disabled:opacity-50" @click="clearEvidenceFile">
                                Remove file
                            </button>
                        </div>
                        <p v-if="form.errors.evidence_file" class="mt-1 text-xs text-red-600">{{ form.errors.evidence_file }}</p>
                        <div v-if="form.progress" class="mt-3 space-y-1" role="status" aria-live="polite">
                            <div class="flex justify-between text-xs text-gray-600">
                                <span>Uploading evidence</span>
                                <span>{{ form.progress.percentage }}%</span>
                            </div>
                            <progress class="h-2 w-full accent-[#315d47]" :value="form.progress.percentage" max="100">
                                {{ form.progress.percentage }}%
                            </progress>
                        </div>
                    </div>

                    <div>
                        <label for="finding-evidence-description" class="block text-sm font-medium text-gray-700">Evidence description</label>
                        <textarea id="finding-evidence-description" v-model="form.evidence_description" rows="2" maxlength="2000"
                            class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                            placeholder="What does this workpaper demonstrate?"></textarea>
                        <p v-if="form.errors.evidence_description" class="mt-1 text-xs text-red-600">{{ form.errors.evidence_description }}</p>
                    </div>
                </section>

                <div class="app-form-actions">
                    <Link :href="route('audits.show', audit.id)" class="app-action-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing || !form.evidence_file" class="app-action-primary">
                        {{ form.processing ? 'Registering...' : 'Register finding' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
