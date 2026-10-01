<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '../../layouts/AuthenticatedLayout.vue'
import { formatDateTime } from '../../utils/formatDate.js'

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
})

const page = usePage()
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <template #header>
            Notifications
        </template>

        <div class="mx-auto max-w-4xl space-y-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="app-section-kicker">WORKFLOW UPDATES</p>
                    <h2 class="app-page-title mt-1 text-xl font-semibold text-gray-900">Notifications</h2>
                </div>
                <span class="text-sm text-gray-500">{{ page.props.notifications?.unread_count ?? 0 }} unread</span>
            </div>

            <div v-if="notifications.length" class="divide-y divide-[#dce3db] border-y border-[#dce3db]">
                <Link v-for="notification in notifications" :key="notification.id"
                    :href="route('notifications.open', notification.id)" method="post" as="button"
                    class="flex w-full items-start gap-4 py-4 text-left transition hover:bg-white/70">
                    <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full"
                        :class="notification.read_at ? 'bg-gray-300' : 'bg-[#315d47]'"></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-900">{{ notification.data.title }}</span>
                            <span class="text-xs text-gray-500">{{ formatDateTime(notification.created_at) }}</span>
                        </span>
                        <span class="mt-1 block text-sm text-gray-600">{{ notification.data.message }}</span>
                        <span class="mt-2 block text-xs font-medium text-[#315d47]">{{ notification.data.finding_reference }} · Open finding</span>
                    </span>
                </Link>
            </div>
            <p v-else class="border-y border-[#dce3db] py-8 text-sm text-gray-500">You have no notifications.</p>
        </div>
    </AuthenticatedLayout>
</template>
