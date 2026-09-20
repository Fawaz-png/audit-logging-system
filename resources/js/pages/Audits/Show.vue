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
        <div class="mx-auto max-w-4xl">
            <Link :href="route('dashboard')" class="text-sm text-gray-600 hover:text-black">
                ← Dashboard
            </Link>

            <div class="mt-6 rounded-lg bg-white p-8 shadow">
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

                <div class="mt-8 space-y-6">
                    <section>
                        <h2 class="font-semibold">
                            Objective
                        </h2>

                        <p class="mt-1 text-gray-700">
                            {{ audit.objective }}
                        </p>
                    </section>

                    <section>
                        <h2 class="font-semibold">
                            Scope
                        </h2>

                        <p class="mt-1 text-gray-700">
                            {{ audit.scope }}
                        </p>
                    </section>

                    <div class="grid gap-6 md:grid-cols-3">
                        <div>
                            <h2 class="font-semibold">Start date</h2>
                            <p class="mt-1 text-gray-700">
                                {{ audit.start_date }}
                            </p>
                        </div>

                        <div>
                            <h2 class="font-semibold">End date</h2>
                            <p class="mt-1 text-gray-700">
                                {{ audit.end_date ?? 'Not set' }}
                            </p>
                        </div>

                        <div>
                            <h2 class="font-semibold">Created by</h2>
                            <p class="mt-1 text-gray-700">
                                {{ audit.creator?.name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>