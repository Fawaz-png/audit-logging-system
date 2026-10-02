<script setup>
import { computed, nextTick, ref, watch } from 'vue'
import { Head, useForm, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue'
import { formatDate, formatDateTime } from '../../utils/formatDate.js'

const props = defineProps({
    finding: {
        type: Object,
        required: true,
    },
    departments: {
        type: Array,
        default: () => [],
    },
    users: {
        type: Array,
        default: () => [],
    },
    canRequestClosure: {
        type: Boolean,
        default: false,
    },
    canReviewClosure: {
        type: Boolean,
        default: false,
    },
    canComment: {
        type: Boolean,
        default: false,
    },
})

const page = usePage()

const permissions = computed(() => page.props.auth?.user?.permissions ?? [])

const can = (permission) => permissions.value.includes(permission)

const canUpdateFinding = computed(() => can('finding.update'))
const canDeleteFinding = computed(() => can('finding.delete'))

const canRespond = computed(() => can('finding.respond'))
const canProposeAction = computed(() => can('finding.remediate'))
const canApproveAction = computed(() => can('finding.update'))
const canRemediate = computed(() => can('finding.remediate'))
const resolutionEvidences = computed(() => {
    return (props.finding.evidences ?? []).filter(evidence => evidence.type === 'RESOLUTION')
})

const deleteForm = useForm({})
const remediationCompletionForm = useForm({})
const confirmation = ref(null)
const confirmationDialog = ref(null)
const confirmationButton = ref(null)
const confirmationTrigger = ref(null)
const confirmationForm = computed(() => confirmation.value?.type === 'delete'
    ? deleteForm
    : remediationCompletionForm)

const responseForm = useForm({
    response: '',
})

const commentForm = useForm({
    body: '',
})

const actionForm = useForm({
    responsible_department_id: '',
    responsible_user_id: '',
    action: '',
    proposed_due_date: '',
})

const approvalForm = useForm({
    agreed_due_date: '',
    comment: '',
})

const rejectionForm = useForm({
    reason: '',
})

const evidenceForm = useForm({
    notes: '',
    description: '',
    file: null,
})
const resolutionEvidenceInput = ref(null)

const closureReviewForm = useForm({ notes: '' })
const closureRejectionForm = useForm({ notes: '' })

const actionError = ref('')
const rejectionOpen = ref(false)
const approvalOpen = ref(false)
const closureRejectionOpen = ref(false)

watch(confirmation, async (value) => {
    if (value) {
        confirmationTrigger.value = document.activeElement
        await nextTick()
        confirmationDialog.value?.showModal()
        confirmationButton.value?.focus()

        return
    }

    if (confirmationDialog.value?.open) {
        confirmationDialog.value.close()
    }
})

const activeAction = computed(() => {
    return props.finding.actions?.find(action => {
        return !['COMPLETED', 'REJECTED'].includes(action.status)
    }) ?? null
})

const selectedDepartmentUsers = computed(() => {
    if (!actionForm.responsible_department_id) {
        return []
    }

    return props.users.filter(
        user => String(user.department_id) === String(actionForm.responsible_department_id)
    )
})

const hasResolutionEvidence = computed(() => {
    return resolutionEvidences.value.length > 0
})

const statusLabel = computed(() => {
    return props.finding.status
        ?.replaceAll('_', ' ')
        ?.toLowerCase()
        ?.replace(/\b\w/g, char => char.toUpperCase())
})

const statusClass = computed(() => {
    const classes = {
        OPEN: 'bg-slate-50 text-slate-700 ring-slate-200',
        RESPONSE_RECEIVED: 'bg-blue-50 text-blue-700 ring-blue-200',
        ACTION_PENDING_APPROVAL: 'bg-amber-50 text-amber-700 ring-amber-200',
        REMEDIATION_IN_PROGRESS: 'bg-orange-50 text-orange-700 ring-orange-200',
        READY_FOR_VERIFICATION: 'bg-purple-50 text-purple-700 ring-purple-200',
        CLOSURE_REQUESTED: 'bg-amber-50 text-amber-800 ring-amber-200',
        CLOSURE_REJECTED: 'bg-red-50 text-red-700 ring-red-200',
        VERIFIED: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        CLOSED: 'bg-gray-50 text-gray-700 ring-gray-200',
    }

    return classes[props.finding.status] ?? 'bg-gray-50 text-gray-700 ring-gray-200'
})

const workflowStepIndex = computed(() => {
    const status = props.finding.status

    const map = {
        OPEN: 0,
        RESPONSE_RECEIVED: 1,
        ACTION_PENDING_APPROVAL: 2,
        REMEDIATION_IN_PROGRESS: 3,
        READY_FOR_VERIFICATION: 4,
        CLOSURE_REQUESTED: 4,
        CLOSURE_REJECTED: 3,
        VERIFIED: 5,
        CLOSED: 5,
    }

    return map[status] ?? 0
})

const riskClass = computed(() => {
    const classes = {
        LOW: 'bg-slate-50 text-slate-700 ring-slate-200',
        MEDIUM: 'bg-amber-50 text-amber-700 ring-amber-200',
        HIGH: 'bg-orange-50 text-orange-700 ring-orange-200',
        CRITICAL: 'bg-red-50 text-red-700 ring-red-200',
    }

    return classes[props.finding.risk_rating] ?? 'bg-gray-50 text-gray-700 ring-gray-200'
})

const deleteFinding = () => {
    confirmation.value = {
        type: 'delete',
        title: 'Delete this finding?',
        message: 'This removes the finding from the active register. This action cannot be undone.',
        confirmLabel: 'Delete finding',
    }
}

const submitResponse = () => {
    responseForm.post(
        route('findings.respond', props.finding.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                responseForm.reset()
            },
        }
    )
}

const submitComment = () => {
    commentForm.post(
        route('findings.comments.store', props.finding.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                commentForm.reset()
            },
        }
    )
}

const submitAction = () => {
    actionError.value = ''

    actionForm.post(
        route('findings.actions.store', props.finding.id),
        {
            preserveScroll: true,
            onError: (errors) => {
                actionError.value = errors.responsible_user_id
                    ?? errors.responsible_department_id
                    ?? errors.action
                    ?? errors.proposed_due_date
                    ?? 'Please correct the highlighted fields.'
            },
            onSuccess: () => {
                actionForm.reset()
            },
        }
    )
}

const approveAction = (action) => {
    approvalForm.agreed_due_date = action.proposed_due_date ?? ''

    approvalOpen.value = true
}

const submitApproval = () => {
    if (!activeAction.value) {
        return
    }

    approvalForm.post(
        route('finding-actions.approve', activeAction.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                approvalOpen.value = false
                approvalForm.reset()
            },
        }
    )
}

const rejectAction = () => {
    if (!activeAction.value) {
        return
    }

    rejectionOpen.value = true
}

const submitRejection = () => {
    if (!activeAction.value) {
        return
    }

    rejectionForm.post(
        route('finding-actions.reject', activeAction.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                rejectionOpen.value = false
                rejectionForm.reset()
            },
        }
    )
}

const completeRemediation = () => {
    if (!activeAction.value) {
        return
    }

    confirmation.value = {
        type: 'complete-remediation',
        actionId: activeAction.value.id,
        title: 'Complete this remediation?',
        message: 'The action will be marked complete and submitted for auditor verification.',
        confirmLabel: 'Mark complete',
    }
}

const dismissConfirmation = () => {
    confirmation.value = null
    nextTick(() => confirmationTrigger.value?.focus())
}

const submitConfirmation = () => {
    if (!confirmation.value || confirmationForm.value.processing) {
        return
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            confirmation.value = null
        },
    }

    if (confirmation.value.type === 'delete') {
        deleteForm.delete(route('findings.destroy', props.finding.id), options)

        return
    }

    remediationCompletionForm.post(
        route('finding-actions.complete', confirmation.value.actionId),
        options
    )
}

const handleConfirmationClose = () => {
    if (confirmation.value) {
        confirmation.value = null
        nextTick(() => confirmationTrigger.value?.focus())
    }
}

const submitEvidence = () => {
    evidenceForm.post(
        route('findings.closure-requests.store', props.finding.id),
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => evidenceForm.reset('notes', 'description', 'file'),
        }
    )
}

const approveClosure = () => {
    closureReviewForm.post(route('findings.closure-requests.approve', props.finding.id), {
        preserveScroll: true,
        onSuccess: () => closureReviewForm.reset(),
    })
}

const rejectClosure = () => {
    closureRejectionForm.post(route('findings.closure-requests.reject', props.finding.id), {
        preserveScroll: true,
        onSuccess: () => {
            rejectionOpen.value = false
            closureRejectionForm.reset()
        },
    })
}

const formatEvidenceSize = (size) => {
    if (!size) {
        return 'Unknown size'
    }

    return size < 1024 * 1024
        ? `${Math.ceil(size / 1024)} KB`
        : `${(size / (1024 * 1024)).toFixed(1)} MB`
}

const selectResolutionEvidence = (event) => {
    evidenceForm.file = event.target.files?.[0] ?? null
    evidenceForm.clearErrors('file')
}

const clearResolutionEvidence = () => {
    evidenceForm.file = null
    evidenceForm.clearErrors('file')

    if (resolutionEvidenceInput.value) {
        resolutionEvidenceInput.value.value = ''
    }
}

</script>

<template>

    <Head :title="finding.title" />

    <AuthenticatedLayout>
        <template #header>
            Finding Details
        </template>

        <div class="max-w-6xl space-y-5">

            <!-- Header -->
            <div
                class="flex flex-col gap-4 rounded-lg border bg-white p-5 shadow-sm sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        {{ finding.audit.reference }}
                    </p>

                    <h1 class="mt-1 text-xl font-semibold text-gray-900 sm:text-2xl">
                        {{ finding.title }}
                    </h1>

                    <p class="mt-1 text-sm text-gray-600">
                        {{ finding.reference }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold ring-1 ring-inset" :class="statusClass">
                        {{ statusLabel }}
                    </span>

                    <Link :href="route('audits.show', finding.audit.id)"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Back to Audit
                    </Link>

                    <Link v-if="canUpdateFinding" :href="route('findings.edit', finding.id)"
                        class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Edit Finding
                    </Link>

                    <button v-if="canDeleteFinding" type="button" @click="deleteFinding"
                        :disabled="deleteForm.processing"
                        class="rounded-md border border-red-600 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:opacity-50">
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete Finding' }}
                    </button>
                </div>
            </div>

            <!-- Summary -->
            <section class="rounded-lg border bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">
                    Finding Summary
                </h2>

                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Process
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ finding.process }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Risk Rating
                        </p>

                        <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                            :class="riskClass">
                            {{ finding.risk_rating }}
                        </span>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Status
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ statusLabel }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                            Audit
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ finding.audit.reference }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Finding Details -->
            <section class="rounded-lg border bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">
                    Finding Details
                </h2>

                <div class="space-y-5">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Description / Condition
                        </h3>

                        <p class="mt-2 whitespace-pre-line text-gray-700">
                            {{ finding.description }}
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Criteria
                        </h3>

                        <p class="mt-2 whitespace-pre-line text-gray-700">
                            {{ finding.criteria }}
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Effect / Implication
                        </h3>

                        <p class="mt-2 whitespace-pre-line text-gray-700">
                            {{ finding.effect }}
                        </p>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                            Recommendation
                        </h3>

                        <p class="mt-2 whitespace-pre-line text-gray-700">
                            {{ finding.recommendation }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Workflow -->
            <section class="rounded-lg border bg-white p-5 shadow-sm">
                <div class="mb-6">
                    <h2 class="text-base font-semibold text-gray-900">
                        Finding Workflow
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Track the response, agreed corrective action, SLA remediation, and closure approval.
                    </p>
                </div>

                <!-- Workflow Progress -->
                <div class="mb-7 overflow-x-auto">
                    <div class="flex min-w-[720px] items-center">
                        <div v-for="(stage, index) in ['Open', 'Response', 'Action', 'Remediation', 'Closure review', 'Closed']" :key="stage" class="flex flex-1 items-center gap-2">
                            <span class="grid h-7 w-7 place-items-center rounded-full text-xs font-semibold"
                                :class="workflowStepIndex >= index ? 'bg-[#315d47] text-white' : 'bg-[#e6eee5] text-[#315d47]'">
                                {{ index + 1 }}
                            </span>
                            <span class="text-xs font-medium text-gray-700">
                                {{ stage }}
                            </span>
                            <div v-if="index < 5" class="mx-3 h-px flex-1 bg-gray-200"></div>
                        </div>
                    </div>
                </div>

                <!-- Auditee Response -->
                <div class="border-t border-gray-100 pt-6">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Auditee Response
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            The auditee records how they intend to address the finding.
                        </p>
                    </div>

                    <div v-if="finding.auditee_response" class="rounded-md border border-gray-200 bg-gray-50 p-4">
                        <p class="whitespace-pre-line text-sm leading-6 text-gray-700">
                            {{ finding.auditee_response }}
                        </p>

                        <p class="mt-3 text-xs text-gray-500">
                            {{ finding.response_by?.name ?? 'Auditee' }}
                            <span v-if="finding.response_at">
                                · {{ formatDateTime(finding.response_at) }}
                            </span>
                        </p>
                    </div>

                    <form v-if="canRespond && finding.status === 'OPEN'" class="mt-4" @submit.prevent="submitResponse">
                        <textarea v-model="responseForm.response" rows="5" maxlength="10000"
                            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                            placeholder="Describe the auditee's response and proposed approach to addressing this finding..."></textarea>

                        <p v-if="responseForm.errors.response" class="mt-1 text-xs text-red-600">
                            {{ responseForm.errors.response }}
                        </p>

                        <div class="mt-3 flex justify-end">
                            <button type="submit" :disabled="responseForm.processing"
                                class="rounded-md bg-[#315d47] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#204b3d] disabled:opacity-50">
                                {{ responseForm.processing ? 'Submitting...' : 'Submit Response' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Corrective Action -->
                <div class="mt-7 border-t border-gray-100 pt-6">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Corrective Action
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            The auditee proposes the action, responsible person and target date. The lead auditor
                            reviews the
                            proposal.
                        </p>
                    </div>

                    <article v-if="activeAction" class="rounded-md border border-gray-200 bg-gray-50 p-4">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500">
                                    Corrective Action
                                </p>

                                <h4 class="mt-1 font-semibold text-gray-900">
                                    {{ activeAction.action }}
                                </h4>
                            </div>

                            <span
                                class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200">
                                {{ activeAction.status.replaceAll('_', ' ') }}
                            </span>
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <p class="text-xs text-gray-500">
                                    Responsible Department
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-900">
                                    {{ activeAction.responsible_department?.name ?? '—' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">
                                    Responsible Person
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-900">
                                    {{ activeAction.responsible_user?.name ?? '—' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-gray-500">
                                    Proposed Target Date
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-900">
                                    {{ activeAction.proposed_due_date
                                        ? formatDate(activeAction.proposed_due_date)
                                        : '—'
                                    }}
                                </p>
                            </div>

                            <div v-if="activeAction.agreed_due_date">
                                <p class="text-xs text-gray-500">
                                    Agreed Target Date
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-900">
                                    {{ formatDate(activeAction.agreed_due_date) }}
                                </p>
                            </div>

                            <div v-if="activeAction.approver" class="md:col-span-2">
                                <p class="text-xs text-gray-500">
                                    Approved By
                                </p>

                                <p class="mt-1 text-sm font-medium text-gray-900">
                                    {{ activeAction.approver.name }}
                                </p>
                            </div>
                        </div>

                        <!-- Lead auditor approval -->
                        <div v-if="activeAction.status === 'PENDING_APPROVAL' && canApproveAction"
                            class="mt-5 border-t border-gray-200 pt-4">
                            <p class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">
                                Lead Auditor Review
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="approveAction(activeAction)"
                                    class="rounded-md bg-[#315d47] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#204b3d]">
                                    Approve Action
                                </button>

                                <button type="button" @click="rejectAction"
                                    class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50">
                                    Reject Action
                                </button>
                            </div>
                        </div>

                        <!-- Remediation -->
                        <div v-if="canRemediate && activeAction.status === 'IN_PROGRESS'"
                            class="mt-5 border-t border-gray-200 pt-4">
                            <p class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500">
                                Remediation
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <button v-if="activeAction.status === 'IN_PROGRESS'" type="button"
                                    @click="completeRemediation"
                                    class="rounded-md bg-[#315d47] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#204b3d]">
                                    Mark Remediation Complete
                                </button>
                            </div>
                        </div>
                    </article>

                    <form v-if="canProposeAction && finding.status === 'RESPONSE_RECEIVED' && !activeAction"
                        class="space-y-4 rounded-md border border-gray-200 bg-gray-50 p-4"
                        @submit.prevent="submitAction">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Responsible Department
                            </label>

                            <select v-model="actionForm.responsible_department_id"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]">
                                <option value="">
                                    Select department
                                </option>

                                <option v-for="department in departments" :key="department.id" :value="department.id">
                                    {{ department.name }}
                                    <span v-if="department.code">
                                        ({{ department.code }})
                                    </span>
                                </option>
                            </select>

                            <p v-if="actionForm.errors.responsible_department_id" class="mt-1 text-xs text-red-600">
                                {{ actionForm.errors.responsible_department_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Responsible Person
                            </label>

                            <select v-model="actionForm.responsible_user_id"
                                :disabled="!actionForm.responsible_department_id"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47] disabled:bg-gray-100">
                                <option value="">
                                    Select responsible person
                                </option>

                                <option v-for="user in selectedDepartmentUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>

                            <p v-if="actionForm.errors.responsible_user_id" class="mt-1 text-xs text-red-600">
                                {{ actionForm.errors.responsible_user_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Corrective Action
                            </label>

                            <textarea v-model="actionForm.action" rows="4" maxlength="10000"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                                placeholder="Describe the corrective action the auditee proposes to take..."></textarea>

                            <p v-if="actionForm.errors.action" class="mt-1 text-xs text-red-600">
                                {{ actionForm.errors.action }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Proposed Target Date
                            </label>

                            <input v-model="actionForm.proposed_due_date" type="date"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]" />

                            <p v-if="actionForm.errors.proposed_due_date" class="mt-1 text-xs text-red-600">
                                {{ actionForm.errors.proposed_due_date }}
                            </p>
                        </div>

                        <p v-if="actionError" class="text-sm text-red-600">
                            {{ actionError }}
                        </p>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="actionForm.processing"
                                class="rounded-md bg-[#315d47] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#204b3d] disabled:opacity-50">
                                {{ actionForm.processing ? 'Submitting...' : 'Submit Corrective Action' }}
                            </button>
                        </div>
                    </form>

                    <p v-else-if="finding.status === 'RESPONSE_RECEIVED' && !activeAction"
                        class="text-sm text-gray-500">
                        Waiting for the auditee to propose a corrective action.
                    </p>
                </div>

                <!-- Evidence -->
                <div class="mt-7 border-t border-gray-100 pt-6">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Evidence
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Supporting records are stored privately and available to users who can view this finding.
                        </p>
                    </div>

                    <div v-if="finding.evidences?.length" class="space-y-3">
                        <article v-for="evidence in finding.evidences" :key="evidence.id"
                            class="flex flex-col gap-3 rounded-md border border-gray-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    {{ evidence.type === 'RESOLUTION' ? 'Resolution evidence' : 'Finding evidence' }}
                                </p>
                                <p class="mt-1 truncate text-sm font-medium text-gray-900">
                                    {{ evidence.original_name }}
                                </p>
                                <p v-if="evidence.description" class="mt-1 whitespace-pre-line text-sm text-gray-600">
                                    {{ evidence.description }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ evidence.uploader?.name ?? 'User' }}
                                    · {{ formatDateTime(evidence.created_at) }}
                                    · {{ formatEvidenceSize(evidence.size) }}
                                </p>
                            </div>

                            <a :href="route('findings.evidences.download', [finding.id, evidence.id])"
                                :download="evidence.original_name"
                                class="inline-flex shrink-0 items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                Download
                            </a>
                        </article>
                    </div>

                    <p v-else class="text-sm text-gray-500">
                        No evidence has been uploaded.
                    </p>

                    <form v-if="canRequestClosure && ['READY_FOR_VERIFICATION', 'CLOSURE_REJECTED'].includes(finding.status)"
                        class="mt-5 space-y-4 rounded-md border border-gray-200 bg-gray-50 p-4"
                        enctype="multipart/form-data" @submit.prevent="submitEvidence">
                        <h4 class="text-sm font-semibold text-gray-900">Request finding closure</h4>
                        <p class="text-sm text-gray-600">Describe the completed remediation and attach its supporting evidence for auditor review.</p>

                        <div>
                            <label for="closure-notes" class="block text-sm font-medium text-gray-700">Remediation summary</label>
                            <textarea id="closure-notes" v-model="evidenceForm.notes" required rows="3" maxlength="10000"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"></textarea>
                            <p v-if="evidenceForm.errors.notes" class="mt-1 text-xs text-red-600">{{ evidenceForm.errors.notes }}</p>
                        </div>

                        <div>
                            <label for="resolution-file" class="block text-sm font-medium text-gray-700">Resolution evidence</label>
                            <input id="resolution-file" ref="resolutionEvidenceInput" type="file" required
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt"
                                class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-white file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 file:ring-1 file:ring-gray-300"
                                @change="selectResolutionEvidence" />
                            <p class="mt-1 text-xs text-gray-500">PDF, image, office document, CSV, or text file, up to 10 MB.</p>
                            <div v-if="evidenceForm.file" class="mt-2 flex flex-wrap items-center justify-between gap-2 rounded border border-gray-200 bg-white px-3 py-2">
                                <p class="min-w-0 break-all text-xs text-gray-700">
                                    {{ evidenceForm.file.name }} · {{ formatEvidenceSize(evidenceForm.file.size) }}
                                </p>
                                <button type="button" :disabled="evidenceForm.processing" class="text-xs font-semibold text-[#315d47] underline underline-offset-2 disabled:opacity-50" @click="clearResolutionEvidence">
                                    Remove file
                                </button>
                            </div>
                            <p v-if="evidenceForm.errors.file" class="mt-1 text-xs text-red-600">{{ evidenceForm.errors.file }}</p>
                            <div v-if="evidenceForm.progress" class="mt-3 space-y-1" role="status" aria-live="polite">
                                <div class="flex justify-between text-xs text-gray-600">
                                    <span>Uploading resolution evidence</span>
                                    <span>{{ evidenceForm.progress.percentage }}%</span>
                                </div>
                                <progress class="h-2 w-full accent-[#315d47]" :value="evidenceForm.progress.percentage" max="100">
                                    {{ evidenceForm.progress.percentage }}%
                                </progress>
                            </div>
                        </div>

                        <div>
                            <label for="resolution-description" class="block text-sm font-medium text-gray-700">Evidence description</label>
                            <textarea id="resolution-description" v-model="evidenceForm.description" rows="2" maxlength="2000"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                                placeholder="What does this evidence demonstrate?"></textarea>
                            <p v-if="evidenceForm.errors.description" class="mt-1 text-xs text-red-600">{{ evidenceForm.errors.description }}</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="evidenceForm.processing || !evidenceForm.file"
                                class="rounded-md bg-[#315d47] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#204b3d] disabled:opacity-50">
                                {{ evidenceForm.processing ? 'Submitting...' : 'Submit closure request' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Closure request review -->
                <div class="mt-7 border-t border-gray-100 pt-6">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-900">
                            Closure approval
                        </h3>
                    </div>

                    <div v-if="finding.status === 'CLOSURE_REQUESTED'" class="rounded-md border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-medium text-amber-900">Closure request awaiting auditor review</p>
                        <p class="mt-1 text-sm text-amber-800">
                            Submitted by {{ finding.closure_requester?.name ?? 'Auditee' }}
                            <span v-if="finding.closure_requested_at"> · {{ formatDateTime(finding.closure_requested_at) }}</span>
                        </p>
                        <p v-if="finding.closure_request_notes" class="mt-3 whitespace-pre-line text-sm text-amber-900">
                            {{ finding.closure_request_notes }}
                        </p>

                        <div v-if="canReviewClosure" class="mt-4 flex flex-wrap gap-2">
                            <button type="button" :disabled="closureReviewForm.processing" @click="approveClosure"
                                class="rounded-md bg-[#315d47] px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                {{ closureReviewForm.processing ? 'Approving...' : 'Approve and close finding' }}
                            </button>
                            <button type="button" @click="closureRejectionOpen = true"
                                class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                                Reject closure request
                            </button>
                        </div>
                    </div>

                    <div v-if="finding.status === 'CLOSURE_REJECTED'" class="rounded-md border border-red-200 bg-red-50 p-4">
                        <p class="text-sm font-medium text-red-900">Closure request was rejected</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-red-800">{{ finding.closure_rejection_reason }}</p>
                    </div>

                    <div v-if="finding.status === 'CLOSED'" class="rounded-md border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-sm font-semibold text-emerald-900">Finding closed</p>
                        <p class="mt-1 text-sm text-emerald-800">
                            Approved by {{ finding.closure_reviewer?.name ?? finding.closer?.name ?? 'Lead auditor' }}
                            <span v-if="finding.closed_at"> · {{ formatDateTime(finding.closed_at) }}</span>
                        </p>
                        <p v-if="finding.closure_review_notes" class="mt-2 whitespace-pre-line text-sm text-emerald-800">
                            {{ finding.closure_review_notes }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- Discussion -->
            <section class="rounded-lg border bg-white p-5 shadow-sm">
                <div class="mb-5">
                    <h2 class="text-base font-semibold text-gray-900">
                        Discussion
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Use comments to clarify the finding and agree the corrective action.
                    </p>
                </div>

                <div class="space-y-4">
                    <article v-for="comment in finding.comments" :key="comment.id"
                        class="rounded-md border border-gray-200 bg-gray-50 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ comment.user?.name ?? 'User' }}
                                </p>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ formatDateTime(comment.created_at) }}
                                </p>
                            </div>
                        </div>

                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">
                            {{ comment.body }}
                        </p>
                    </article>

                    <p v-if="!finding.comments?.length" class="text-sm text-gray-500">
                        No comments have been added yet.
                    </p>
                </div>

                <form v-if="canComment && finding.status !== 'CLOSED'" class="mt-5 border-t border-gray-100 pt-5"
                    @submit.prevent="submitComment">
                    <textarea v-model="commentForm.body" rows="3" maxlength="10000"
                        class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                        placeholder="Add a comment..."></textarea>

                    <p v-if="commentForm.errors.body" class="mt-1 text-xs text-red-600">
                        {{ commentForm.errors.body }}
                    </p>

                    <div class="mt-3 flex justify-end">
                        <button type="submit" :disabled="commentForm.processing"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 disabled:opacity-50">
                            {{ commentForm.processing ? 'Posting...' : 'Add Comment' }}
                        </button>
                    </div>
                </form>
            </section>

            <!-- Record Information -->
            <section class="rounded-lg border bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">
                    Record Information
                </h2>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <p class="text-sm text-gray-500">
                            Created By
                        </p>

                        <p class="mt-1 font-medium">
                            {{ finding.creator?.name ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">
                            Created At
                        </p>

                        <p class="mt-1 font-medium">
                            {{ formatDateTime(finding.created_at) }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">
                            Last Updated
                        </p>

                        <p class="mt-1 font-medium">
                            {{ formatDateTime(finding.updated_at) }}
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <Teleport to="body">
            <dialog ref="confirmationDialog" aria-labelledby="finding-confirmation-title" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-lg border border-gray-200 bg-white p-6 text-left shadow-xl backdrop:bg-[#102a24]/45" @close="handleConfirmationClose">
                <div v-if="confirmation">
                    <h2 id="finding-confirmation-title" class="text-lg font-semibold text-gray-900">
                        {{ confirmation.title }}
                    </h2>
                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        {{ confirmation.message }}
                    </p>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" :disabled="confirmationForm.processing" @click="dismissConfirmation"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 disabled:opacity-50">
                            Cancel
                        </button>
                        <button ref="confirmationButton" type="button" :disabled="confirmationForm.processing" @click="submitConfirmation"
                            class="rounded-md border border-red-700 bg-red-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                            {{ confirmationForm.processing ? 'Working...' : confirmation.confirmLabel }}
                        </button>
                    </div>
                </div>
            </dialog>
        </Teleport>

        <!-- Approval Modal -->
        <Teleport to="body">
            <div v-if="approvalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#102a24]/45 p-4">
                <div class="w-full max-w-lg rounded-lg border bg-white p-6 shadow-xl">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Approve Corrective Action
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Confirm the agreed target date and optionally record an approval comment.
                    </p>

                    <form class="mt-5 space-y-4" @submit.prevent="submitApproval">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Agreed Target Date
                            </label>

                            <input v-model="approvalForm.agreed_due_date" type="date"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]" />

                            <p v-if="approvalForm.errors.agreed_due_date" class="mt-1 text-xs text-red-600">
                                {{ approvalForm.errors.agreed_due_date }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Approval Comment
                            </label>

                            <textarea v-model="approvalForm.comment" rows="3" maxlength="10000"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                                placeholder="Optional comment..."></textarea>

                            <p v-if="approvalForm.errors.comment" class="mt-1 text-xs text-red-600">
                                {{ approvalForm.errors.comment }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="approvalOpen = false"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                                Cancel
                            </button>

                            <button type="submit" :disabled="approvalForm.processing"
                                class="rounded-md bg-[#315d47] px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                {{ approvalForm.processing ? 'Approving...' : 'Approve Action' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Rejection Modal -->
        <Teleport to="body">
            <div v-if="rejectionOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-[#102a24]/45 p-4">
                <div class="w-full max-w-lg rounded-lg border bg-white p-6 shadow-xl">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Reject Corrective Action
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Explain why the proposed action needs to be reconsidered.
                    </p>

                    <form class="mt-5 space-y-4" @submit.prevent="submitRejection">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Reason
                            </label>

                            <textarea v-model="rejectionForm.reason" rows="4" maxlength="10000"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                                placeholder="Explain what needs to be changed..."></textarea>

                            <p v-if="rejectionForm.errors.reason" class="mt-1 text-xs text-red-600">
                                {{ rejectionForm.errors.reason }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="rejectionOpen = false"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                                Cancel
                            </button>

                            <button type="submit" :disabled="rejectionForm.processing"
                                class="rounded-md border border-red-600 bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                {{ rejectionForm.processing ? 'Rejecting...' : 'Reject Action' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Closure rejection modal -->
        <Teleport to="body">
            <div v-if="closureRejectionOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-[#102a24]/45 p-4">
                <div class="w-full max-w-lg rounded-lg border bg-white p-6 shadow-xl">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Reject closure request
                    </h2>

                    <p class="mt-1 text-sm text-gray-600">
                        Explain what remediation or evidence needs further work.
                    </p>

                    <form class="mt-5 space-y-4" @submit.prevent="rejectClosure">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Rejection reason
                            </label>

                            <textarea v-model="closureRejectionForm.notes" rows="4" maxlength="10000" required
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#315d47] focus:ring-[#315d47]"
                                placeholder="Explain what the auditee should address..."></textarea>

                            <p v-if="closureRejectionForm.errors.notes" class="mt-1 text-xs text-red-600">
                                {{ closureRejectionForm.errors.notes }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" @click="closureRejectionOpen = false"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700">
                                Cancel
                            </button>

                            <button type="submit" :disabled="closureRejectionForm.processing"
                                class="rounded-md border border-red-600 bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50">
                                {{ closureRejectionForm.processing ? 'Rejecting...' : 'Reject request' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>