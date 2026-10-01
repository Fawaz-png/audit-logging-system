<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue'

const props = defineProps({
    finding: {
        type: Object,
        required: true,
    },
})

const form = useForm({
    title: props.finding.title,
    description: props.finding.description,
    criteria: props.finding.criteria,
    effect: props.finding.effect,
    recommendation: props.finding.recommendation,
    process: props.finding.process,
    risk_rating: props.finding.risk_rating,
})

const submit = () => form.put(route('findings.update', props.finding.id))
</script>

<template>
    <Head :title="`Edit ${finding.title}`" />

    <AuthenticatedLayout>
        <template #header>
            Edit Finding
        </template>

        <div class="mx-auto max-w-4xl space-y-6">
            <div>
                <Link :href="route('findings.show', finding.id)" class="text-sm text-gray-600 hover:text-black">Back to finding</Link>
                <h2 class="app-page-title mt-2 text-xl font-semibold text-gray-900">Edit Finding</h2>
                <p class="mt-1 text-sm text-gray-600">{{ finding.reference }} · {{ finding.audit?.reference }}</p>
            </div>

            <form class="space-y-5 rounded-lg border border-gray-200 bg-white p-6" @submit.prevent="submit">
                <div>
                    <label for="finding-title" class="block text-sm font-medium text-gray-700">Title</label>
                    <input id="finding-title" v-model="form.title" required maxlength="255" type="text"
                        class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]" />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label for="finding-process" class="block text-sm font-medium text-gray-700">Process</label>
                    <input id="finding-process" v-model="form.process" required maxlength="255" type="text"
                        class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]" />
                    <p v-if="form.errors.process" class="mt-1 text-xs text-red-600">{{ form.errors.process }}</p>
                </div>

                <div>
                    <label for="finding-risk" class="block text-sm font-medium text-gray-700">Risk rating</label>
                    <select id="finding-risk" v-model="form.risk_rating" required
                        class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]">
                        <option value="LOW">Low</option>
                        <option value="MEDIUM">Medium</option>
                        <option value="HIGH">High</option>
                        <option value="CRITICAL">Critical</option>
                    </select>
                    <p v-if="form.errors.risk_rating" class="mt-1 text-xs text-red-600">{{ form.errors.risk_rating }}</p>
                </div>

                <div v-for="field in [
                    { key: 'description', label: 'Description / condition' },
                    { key: 'criteria', label: 'Criteria' },
                    { key: 'effect', label: 'Effect / implication' },
                    { key: 'recommendation', label: 'Auditor recommendation' },
                ]" :key="field.key">
                    <label :for="`finding-${field.key}`" class="block text-sm font-medium text-gray-700">{{ field.label }}</label>
                    <textarea :id="`finding-${field.key}`" v-model="form[field.key]" required rows="4"
                        class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"></textarea>
                    <p v-if="form.errors[field.key]" class="mt-1 text-xs text-red-600">{{ form.errors[field.key] }}</p>
                </div>

                <div class="app-form-actions">
                    <Link :href="route('findings.show', finding.id)" class="app-action-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="app-action-primary">
                        {{ form.processing ? 'Saving...' : 'Save finding' }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
