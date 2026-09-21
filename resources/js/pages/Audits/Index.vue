<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    audits: {
        type: Array,
        required: true,
    },
});
</script>

<template>

    <Head title="Audits" />

    <div class="min-h-screen bg-gray-100 p-8">
        <div class="mx-auto max-w-6xl">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <Link :href="route('dashboard')" class="text-sm text-gray-600 hover:text-black">
                        ← Dashboard
                    </Link>

                    <h1 class="mt-3 text-3xl font-semibold">
                        Audits
                    </h1>
                </div>

                <Link :href="route('audits.create')" class="rounded bg-black px-5 py-2 text-white">
                    Create Audit
                </Link>
            </div>

            <div class="rounded-lg bg-white shadow">
                <div v-if="audits.length === 0" class="p-8 text-center text-gray-500">
                    No audits have been created yet.
                </div>

                <div v-else class="divide-y">
                    <div v-for="audit in audits" :key="audit.id" class="flex items-center justify-between gap-6 p-6">
                        <div>
                            <p class="text-sm text-gray-500">
                                {{ audit.reference }}
                            </p>

                            <h2 class="mt-1 text-lg font-semibold">
                                {{ audit.title }}
                            </h2>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ audit.department?.name }}
                            </p>
                        </div>

                        <Link :href="route('audits.show', { audit: audit.id })"
                            class="rounded border px-4 py-2 text-sm">
                            View
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>