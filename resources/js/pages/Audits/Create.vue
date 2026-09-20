<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    reference: '',
    title: '',
    objective: '',
    scope: '',
    start_date: '',
    end_date: '',
});

const submit = () => {
    form.post(route('audits.store'));
};
</script>

<template>

    <Head title="Create Audit" />

    <div class="min-h-screen bg-gray-100 p-8">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6">
                <Link :href="route('dashboard')" class="text-sm text-gray-600 hover:text-black">
                    ← Dashboard
                </Link>

                <h1 class="mt-3 text-3xl font-semibold">
                    Create Audit
                </h1>
            </div>

            <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-8 shadow">
                <div>
                    <label for="reference" class="block mb-1 font-medium">
                        Reference
                    </label>

                    <input id="reference" v-model="form.reference" type="text" placeholder="AUD-2026-001"
                        class="w-full rounded border px-3 py-2" />

                    <p v-if="form.errors.reference" class="mt-1 text-sm text-red-600">
                        {{ form.errors.reference }}
                    </p>
                </div>

                <div>
                    <label for="title" class="block mb-1 font-medium">
                        Title
                    </label>

                    <input id="title" v-model="form.title" type="text" class="w-full rounded border px-3 py-2" />

                    <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                        {{ form.errors.title }}
                    </p>
                </div>

                <div>
                    <label for="objective" class="block mb-1 font-medium">
                        Objective
                    </label>

                    <textarea id="objective" v-model="form.objective" rows="4"
                        class="w-full rounded border px-3 py-2"></textarea>

                    <p v-if="form.errors.objective" class="mt-1 text-sm text-red-600">
                        {{ form.errors.objective }}
                    </p>
                </div>

                <div>
                    <label for="scope" class="block mb-1 font-medium">
                        Scope
                    </label>

                    <textarea id="scope" v-model="form.scope" rows="4"
                        class="w-full rounded border px-3 py-2"></textarea>

                    <p v-if="form.errors.scope" class="mt-1 text-sm text-red-600">
                        {{ form.errors.scope }}
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label for="start_date" class="block mb-1 font-medium">
                            Start date
                        </label>

                        <input id="start_date" v-model="form.start_date" type="date"
                            class="w-full rounded border px-3 py-2" />

                        <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                            {{ form.errors.start_date }}
                        </p>
                    </div>

                    <div>
                        <label for="end_date" class="block mb-1 font-medium">
                            End date
                        </label>

                        <input id="end_date" v-model="form.end_date" type="date"
                            class="w-full rounded border px-3 py-2" />

                        <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                            {{ form.errors.end_date }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('dashboard')" class="rounded border px-4 py-2">
                        Cancel
                    </Link>

                    <button type="submit" :disabled="form.processing"
                        class="rounded bg-black px-5 py-2 text-white disabled:opacity-50">
                        {{ form.processing ? 'Creating...' : 'Create Audit' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>