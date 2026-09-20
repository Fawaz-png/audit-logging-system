<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>

    <Head title="Dashboard" />

    <div class="min-h-screen bg-gray-100 p-8">
        <div class="mx-auto max-w-4xl">
            <h1 class="text-3xl font-semibold">
                Dashboard
            </h1>

            <p class="mt-2">
                Welcome, {{ page.props.auth.user.name }}.
            </p>

            <Link :href="route('audits.create')" as="button"
                class="inline-block mt-6 rounded bg-black px-4 py-2 text-white">
                Create Audit
            </Link>

            <Link :href="route('logout')" method="post" as="button" class="mt-6 rounded bg-black px-4 py-2 text-white">
                Logout
            </Link>
        </div>
    </div>
</template>