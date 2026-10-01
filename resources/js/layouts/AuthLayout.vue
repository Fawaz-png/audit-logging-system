<script setup>
import { Head, Link } from '@inertiajs/vue3'

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    eyebrow: {
        type: String,
        default: 'SECURE ACCESS',
    },
    backHref: {
        type: String,
        default: null,
    },
    backLabel: {
        type: String,
        default: 'Back',
    },
})
</script>

<template>
    <Head :title="title" />

    <div class="auth-frame min-h-screen lg:grid lg:grid-cols-[minmax(0,1.05fr)_minmax(420px,0.95fr)]">
        <aside class="auth-brand relative flex min-h-[270px] flex-col justify-between overflow-hidden px-7 py-7 text-white sm:px-10 sm:py-9 lg:min-h-screen lg:px-16 lg:py-12">
            <div class="auth-brand-grid" aria-hidden="true"></div>
            <div class="auth-brand-line" aria-hidden="true"></div>

            <div class="auth-wordmark relative z-10 flex items-center gap-3">
                <span class="auth-mark grid h-11 w-11 place-items-center text-sm font-bold">IA</span>
                <span class="flex flex-col">
                    <span class="text-sm font-semibold tracking-wide">IS AUDIT</span>
                    <span class="mt-0.5 text-[10px] font-medium tracking-[0.16em] text-white/60">ASSURANCE WORKSPACE</span>
                </span>
            </div>

            <div class="auth-brand-copy relative z-10 my-10 max-w-xl lg:my-0">
                <p class="mb-5 text-[11px] font-semibold tracking-[0.2em] text-[#e9a678]">
                    CONTROL / TRUST / FOLLOW-THROUGH
                </p>
                <h1 class="max-w-lg font-serif text-4xl leading-[1.08] sm:text-5xl">
                    Clarity for<br />
                    <span class="text-[#c9dfb7]">better decisions.</span>
                </h1>
                <p class="mt-5 max-w-sm text-sm leading-6 text-white/70">
                    A considered space for the work that strengthens an organization.
                </p>
            </div>

            <div class="relative z-10 hidden items-center gap-3 text-[10px] font-medium tracking-[0.16em] text-white/50 lg:flex">
                <span class="auth-rule h-px w-10"></span>
                <span>INTERNAL ASSURANCE</span>
                <span class="ml-auto">SECURE ACCESS</span>
            </div>
        </aside>

        <main class="auth-main flex min-h-[calc(100svh-270px)] items-center justify-center px-6 py-10 sm:px-10 lg:min-h-screen lg:px-14 lg:py-16">
            <div class="auth-content w-full max-w-[430px]">
                <Link v-if="backHref" :href="backHref" class="auth-back-link">
                    <span aria-hidden="true">←</span>
                    {{ backLabel }}
                </Link>

                <div class="mb-10">
                    <p class="auth-eyebrow mb-5 flex items-center gap-2 text-[11px] font-semibold tracking-[0.16em]">
                        <span class="auth-status h-2 w-2 rounded-full"></span>
                        {{ eyebrow }}
                    </p>
                    <h2 class="font-serif text-3xl leading-tight text-[#17231e] sm:text-4xl">
                        {{ title }}
                    </h2>
                    <p class="mt-3 text-sm leading-6 text-[#64716a]">
                        {{ description }}
                    </p>
                </div>

                <div class="auth-form">
                    <slot />
                </div>

                <footer class="mt-12 flex items-center justify-between border-t border-[#dce3db] pt-5 text-[10px] font-medium tracking-[0.12em] text-[#7b877f]">
                    <span>IS AUDIT</span>
                    <span>PRIVATE WORKSPACE</span>
                </footer>
            </div>
        </main>
    </div>
</template>

<style scoped>
.auth-frame {
    background: #f4f6f1;
    color: #17231e;
    font-family: var(--font-sans, 'Instrument Sans', ui-sans-serif, sans-serif);
}

.auth-brand {
    background: linear-gradient(145deg, #204b3d 0%, #16372e 58%, #102a24 100%);
    isolation: isolate;
}

.auth-brand-grid {
    position: absolute;
    inset: 0;
    z-index: -1;
    background-image:
        linear-gradient(rgb(255 255 255 / 4%) 1px, transparent 1px),
        linear-gradient(90deg, rgb(255 255 255 / 4%) 1px, transparent 1px);
    background-size: 44px 44px;
    mask-image: linear-gradient(135deg, black, transparent 78%);
}

.auth-brand-line {
    position: absolute;
    top: 21%;
    right: -18%;
    width: 58%;
    aspect-ratio: 1;
    z-index: -1;
    border: 1px solid rgb(231 239 221 / 13%);
    transform: rotate(38deg);
}

.auth-brand-line::before,
.auth-brand-line::after {
    position: absolute;
    content: '';
    border: 1px solid rgb(231 239 221 / 10%);
}

.auth-brand-line::before {
    inset: 12%;
}

.auth-brand-line::after {
    inset: 25%;
}

.auth-mark {
    border: 1px solid rgb(255 255 255 / 28%);
    border-radius: 5px;
    background: #e9a678;
    color: #17372e;
}

.auth-rule {
    background: #e9a678;
}

.auth-main {
    background:
        radial-gradient(ellipse at 90% 8%, rgb(201 223 183 / 25%), transparent 32%),
        #f4f6f1;
}

.auth-eyebrow {
    color: #596d60;
}

.auth-status {
    background: #d17b4c;
    box-shadow: 0 0 0 4px rgb(209 123 76 / 14%);
}

.auth-back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 28px;
    color: #315d47;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

.auth-back-link:hover {
    color: #173d2e;
    text-decoration: underline;
    text-underline-offset: 3px;
}

.auth-content :deep(form) {
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
}

.auth-content :deep(label) {
    display: block;
    margin-bottom: 0.4rem;
    color: #34483e;
    font-size: 0.82rem;
    font-weight: 600;
}

.auth-content :deep(label.auth-remember) {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    margin-bottom: 0;
}

.auth-content :deep(input:not([type='checkbox'])),
.auth-content :deep(select),
.auth-content :deep(textarea) {
    width: 100%;
    min-height: 44px;
    border: 1px solid #cbd5cc;
    border-radius: 5px;
    background: #fff;
    padding: 0.625rem 0.75rem;
    color: #17231e;
    font: inherit;
    font-size: 0.875rem;
    line-height: 1.4;
    transition: border-color 140ms ease, box-shadow 140ms ease, background-color 140ms ease;
}

.auth-content :deep(textarea) {
    min-height: auto;
}

.auth-content :deep(input:disabled) {
    background: #e9eee8;
    color: #64716a;
}

.auth-content :deep(input:focus),
.auth-content :deep(select:focus),
.auth-content :deep(textarea:focus) {
    border-color: #3d7057;
    outline: none;
    box-shadow: 0 0 0 3px rgb(61 112 87 / 16%);
}

.auth-content :deep(input:hover:not(:focus):not(:disabled)),
.auth-content :deep(select:hover:not(:focus):not(:disabled)),
.auth-content :deep(textarea:hover:not(:focus):not(:disabled)) {
    border-color: #aab9ad;
}

.auth-content :deep(input[aria-invalid='true']),
.auth-content :deep(select[aria-invalid='true']),
.auth-content :deep(textarea[aria-invalid='true']),
.auth-content :deep(input.border-red-400),
.auth-content :deep(select.border-red-400),
.auth-content :deep(textarea.border-red-400) {
    border-color: #c46a50;
}

.auth-content :deep(input[aria-invalid='true']:focus),
.auth-content :deep(select[aria-invalid='true']:focus),
.auth-content :deep(textarea[aria-invalid='true']:focus),
.auth-content :deep(input:has(+ p.text-red-600):focus),
.auth-content :deep(select:has(+ p.text-red-600):focus),
.auth-content :deep(textarea:has(+ p.text-red-600):focus) {
    border-color: #b65e42;
    box-shadow: 0 0 0 3px rgb(182 94 66 / 15%);
}

.auth-content :deep(input:has(+ p.text-red-600)),
.auth-content :deep(select:has(+ p.text-red-600)),
.auth-content :deep(textarea:has(+ p.text-red-600)) {
    border-color: #c46a50;
}

.auth-content :deep(input[type='checkbox']) {
    accent-color: #2a5a45;
}

.auth-content :deep(button[type='submit']) {
    display: flex;
    min-height: 48px;
    width: 100%;
    align-items: center;
    justify-content: center;
    border: 1px solid #183d30;
    border-radius: 5px;
    background: #183d30;
    padding: 0.75rem 1rem;
    color: #fff;
    font-size: 0.9rem;
    font-weight: 600;
    transition: background-color 140ms ease, transform 140ms ease;
}

.auth-content :deep(button[type='submit']:not(:disabled)) {
    cursor: pointer;
}

.auth-content :deep(button[type='submit']:hover:not(:disabled)) {
    background: #24553f;
    transform: translateY(-1px);
}

.auth-content :deep(button[type='submit']:disabled) {
    cursor: wait;
    opacity: 0.6;
}

.auth-content :deep(a) {
    color: #315d47;
    text-decoration: none;
}

.auth-content :deep(a:hover) {
    color: #173d2e;
    text-decoration: underline;
    text-underline-offset: 3px;
}

@media (max-width: 1023px) {
    .auth-brand-line {
        top: -25%;
        right: -6%;
        width: 42%;
    }
}

@media (max-width: 639px) {
    .auth-brand-copy {
        margin-top: 2.5rem;
        margin-bottom: 0;
    }

    .auth-brand-copy p:last-child {
        display: none;
    }

    .auth-main {
        min-height: calc(100svh - 270px);
    }
}
</style>