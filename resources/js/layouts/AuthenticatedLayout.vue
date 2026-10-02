<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'

const page = usePage()
const validationErrors = computed(() => Object.entries(page.props.errors ?? {}).flatMap(([field, value]) => {
    const messages = Array.isArray(value) ? value : [value]

    return messages.map(message => ({
        field: field.replaceAll('.', ' ').replaceAll('_', ' '),
        message: String(message),
    }))
}))
const validationSummary = ref(null)

watch(validationErrors, async (errors) => {
    if (errors.length) {
        await nextTick()
        validationSummary.value?.focus()
    }
})

const user = computed(() => page.props.auth?.user)

const permissions = computed(() => user.value?.permissions ?? [])
const notificationSummary = ref(page.props.notifications ?? { unread_count: 0, recent: [] })
const unreadNotificationCount = computed(() => notificationSummary.value.unread_count ?? 0)
const recentNotifications = computed(() => notificationSummary.value.recent ?? [])
const mobileNavOpen = ref(false)
const sidebarProfileOpen = ref(false)
const topbarProfileOpen = ref(false)
const notificationMenuOpen = ref(false)
const notificationToast = ref(null)
const searchOpen = ref(false)
const searchQuery = ref('')
const selectedSearchIndex = ref(0)
const searchInput = ref(null)
const pageUrl = computed(() => page.url)
let notificationPollInterval
let notificationToastTimeout

const userInitials = computed(() => {
    return (user.value?.name ?? 'IS')
        .split(/\s+/)
        .slice(0, 2)
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase()
})

const can = (permission) => {
    return permissions.value.includes(permission)
}

const formatNotificationTime = (value) => new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: user.value?.preferences?.timezone ?? 'UTC',
}).format(new Date(value))

const navigationItems = computed(() => {
    const items = [
        {
            label: 'Dashboard',
            detail: 'Workspace overview',
            href: route('dashboard'),
            keywords: 'home overview',
        },
    ]

    if (can('audit.view')) {
        items.push({
            label: 'Reports & insights',
            detail: 'Portfolio exposure and workflow health',
            href: route('reports.index'),
            keywords: 'reports insights risk distribution overdue',
        }, {
            label: 'Audit register',
            detail: 'Browse audit engagements',
            href: route('audits.index'),
            keywords: 'audits register engagements',
        }, {
            label: 'Findings register',
            detail: 'Track department and audit findings',
            href: route('findings.index'),
            keywords: 'findings remediation closure risks',
        })
    }

    if (can('audit-log.view')) {
        items.push({
            label: 'Change history',
            detail: 'Review recorded changes',
            href: route('audit-logs.index'),
            keywords: 'audit logs journal governance events',
        })
    }

    if (can('audit.create')) {
        items.push({
            label: 'Create audit',
            detail: 'Start a new engagement',
            href: route('audits.create'),
            keywords: 'new audit engagement',
        })
    }

    if (can('user.manage')) {
        items.push({
            label: 'People & access',
            detail: 'Manage application users',
            href: route('users.index'),
            keywords: 'users people roles department',
        }, {
            label: 'Roles & permissions',
            detail: 'Review and manage access grants',
            href: route('roles.index'),
            keywords: 'roles permissions access control',
        }, {
            label: 'Add user',
            detail: 'Provision an account',
            href: route('users.create'),
            keywords: 'new user invite account',
        })
    }

    items.push({
        label: 'My profile',
        detail: 'Personal details and preferences',
        href: route('profile.edit'),
        keywords: 'profile preferences timezone alerts',
    }, {
        label: 'Security settings',
        detail: 'Two-factor authentication',
        href: route('two-factor.settings'),
        keywords: 'security two factor 2fa',
    }, {
        label: 'Change password',
        detail: 'Update your account password',
        href: route('password.change'),
        keywords: 'password account security',
    })

    return items
})

const loadedRecords = computed(() => {
    const records = []
    const audits = page.props.audits ?? []
    const audit = page.props.audit
    const findings = [
        ...(page.props.finding ? [page.props.finding] : []),
        ...(page.props.audit?.findings ?? []),
    ]
    const users = page.props.users ?? []

    for (const item of can('audit.view') ? audits : []) {
        records.push({
            label: item.title,
            detail: `${item.reference} · ${item.department?.name ?? 'Audit'}`,
            href: route('audits.show', { audit: item.id }),
            keywords: `${item.reference} ${item.title} ${item.department?.name ?? ''} ${item.status ?? ''}`,
        })
    }

    if (audit && can('audit.view')) {
        records.push({
            label: audit.title,
            detail: `${audit.reference} · Audit`,
            href: route('audits.show', { audit: audit.id }),
            keywords: `${audit.reference} ${audit.title} ${audit.department?.name ?? ''} ${audit.status ?? ''}`,
        })
    }

    for (const item of can('audit.view') ? findings : []) {
        records.push({
            label: item.title,
            detail: `${item.reference} · Finding`,
            href: route('findings.show', { finding: item.id }),
            keywords: `${item.reference} ${item.title} ${item.process ?? ''} ${item.status ?? ''}`,
        })
    }

    for (const item of can('user.manage') ? users : []) {
        records.push({
            label: item.name,
            detail: `${item.email} · User`,
            href: route('users.index'),
            keywords: `${item.name} ${item.email} ${item.role?.name ?? ''} ${item.department?.name ?? ''}`,
        })
    }

    return records
})

const searchResults = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase()
    const items = [
        ...navigationItems.value.map(item => ({ ...item, type: 'Page' })),
        ...(query ? loadedRecords.value.map(item => ({ ...item, type: 'Record' })) : []),
    ]

    if (!query) {
        return items.slice(0, 6)
    }

    return items.filter(item => {
        return `${item.label} ${item.detail} ${item.keywords}`
            .toLocaleLowerCase()
            .includes(query)
    }).slice(0, 10)
})

const openSearch = async () => {
    searchOpen.value = true
    sidebarProfileOpen.value = false
    topbarProfileOpen.value = false
    await nextTick()
    searchInput.value?.focus()
}

const closeSearch = () => {
    searchOpen.value = false
    searchQuery.value = ''
    selectedSearchIndex.value = 0
}

const handleGlobalKeydown = (event) => {
    const target = event.target
    const isTyping = target instanceof HTMLElement
        && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName))

    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        searchOpen.value ? closeSearch() : openSearch()
    }

    if (event.key === '/' && !isTyping && !searchOpen.value) {
        event.preventDefault()
        openSearch()
    }

    if (searchOpen.value && searchResults.value.length) {
        if (event.key === 'ArrowDown') {
            event.preventDefault()
            selectedSearchIndex.value = (selectedSearchIndex.value + 1) % searchResults.value.length
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault()
            selectedSearchIndex.value = (selectedSearchIndex.value - 1 + searchResults.value.length) % searchResults.value.length
        }

        if (event.key === 'Enter') {
            event.preventDefault()
            const selectedResult = searchResults.value[selectedSearchIndex.value]

            if (selectedResult) {
                closeSearch()
                router.visit(selectedResult.href)
            }
        }
    }

    if (event.key === 'Escape') {
        closeSearch()
        sidebarProfileOpen.value = false
        topbarProfileOpen.value = false
        notificationMenuOpen.value = false
        mobileNavOpen.value = false
    }
}

const handleOutsidePointer = (event) => {
    if (!event.target.closest('[data-profile-menu]') && !event.target.closest('[data-notification-menu]')) {
        sidebarProfileOpen.value = false
        topbarProfileOpen.value = false
        notificationMenuOpen.value = false
    }
}

const pollNotifications = async () => {
    try {
        const response = await fetch(route('notifications.summary'), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            cache: 'no-store',
        })

        if (!response.ok) {
            return
        }

        const nextSummary = await response.json()
        const previousIds = new Set(recentNotifications.value.map(item => item.id))
        const arrivals = (nextSummary.recent ?? []).filter(item => !previousIds.has(item.id))

        notificationSummary.value = nextSummary

        if (arrivals.length && user.value?.preferences?.live_notification_alerts !== false) {
            notificationToast.value = arrivals[0]
            window.clearTimeout(notificationToastTimeout)
            notificationToastTimeout = window.setTimeout(() => {
                notificationToast.value = null
            }, 8000)
        }
    } catch {
        // Keep the app shell usable while the notification endpoint is unavailable.
    }
}

const handlePageShow = (event) => {
    if (event.persisted) {
        window.location.reload()
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown)
    window.addEventListener('pageshow', handlePageShow)
    document.addEventListener('pointerdown', handleOutsidePointer)
    notificationPollInterval = window.setInterval(pollNotifications, 30_000)
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown)
    window.removeEventListener('pageshow', handlePageShow)
    document.removeEventListener('pointerdown', handleOutsidePointer)
    window.clearInterval(notificationPollInterval)
    window.clearTimeout(notificationToastTimeout)
})

watch(pageUrl, () => {
    closeSearch()
    sidebarProfileOpen.value = false
    topbarProfileOpen.value = false
    notificationMenuOpen.value = false
    mobileNavOpen.value = false
})

watch(() => page.props.notifications, (value) => {
    if (value) {
        notificationSummary.value = value
    }
}, { deep: true })

watch(searchQuery, () => {
    selectedSearchIndex.value = 0
})
</script>

<template>
    <div class="app-shell min-h-screen">
        <Head />

        <button v-if="mobileNavOpen" type="button" aria-label="Close navigation menu"
            class="app-nav-backdrop fixed inset-0 z-30 lg:hidden" @click="mobileNavOpen = false"></button>

        <aside id="app-navigation" class="app-sidebar fixed inset-y-0 left-0 z-40 flex w-[min(84vw,280px)] -translate-x-full flex-col text-white transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:w-[264px] lg:shrink-0 lg:translate-x-0"
            :class="{ 'is-open': mobileNavOpen }">
            <Link :href="route('dashboard')" aria-label="IS Audit dashboard" class="app-brand flex items-center gap-3 border-b px-6 py-6" @click="mobileNavOpen = false">
                <span class="app-brand-mark grid h-11 w-11 shrink-0 place-items-center text-sm font-bold">IA</span>
                <span class="flex min-w-0 flex-col">
                    <span class="text-sm font-semibold tracking-wide">IS AUDIT</span>
                    <span class="mt-1 truncate text-[10px] font-medium tracking-[0.13em] text-white/55">ASSURANCE WORKSPACE</span>
                </span>
            </Link>

            <nav aria-label="Main navigation" class="flex-1 space-y-8 overflow-y-auto px-4 py-7">
                <div>
                    <p class="app-nav-label mb-2 px-3 text-[10px] font-semibold tracking-[0.16em]">WORKSPACE</p>
                    <Link :href="route('dashboard')" class="app-nav-link group" :class="{ 'is-active': route().current('dashboard') }" @click="mobileNavOpen = false">
                        <span class="app-nav-mark">01</span>
                        <span>Dashboard</span>
                    </Link>
                </div>

                <div v-if="can('audit.view') || can('audit.create')">
                    <p class="app-nav-label mb-2 px-3 text-[10px] font-semibold tracking-[0.16em]">ASSURANCE</p>
                    <Link v-if="can('audit.view')" :href="route('audits.index')" class="app-nav-link group" :class="{ 'is-active': route().current('audits.*') }" @click="mobileNavOpen = false">
                        <span class="app-nav-mark">02</span>
                        <span>Audits</span>
                    </Link>
                    <Link v-if="can('audit.view')" :href="route('findings.index')" class="app-nav-link group" :class="{ 'is-active': route().current('findings.index') }" @click="mobileNavOpen = false">
                        <span class="app-nav-mark">03</span>
                        <span>Findings</span>
                    </Link>
                    <Link v-if="can('audit.create')" :href="route('audits.create')" class="app-nav-sub-link" @click="mobileNavOpen = false">
                        <span aria-hidden="true">+</span> Create audit
                    </Link>
                </div>

                <div v-if="can('audit.view')">
                    <p class="app-nav-label mb-2 px-3 text-[10px] font-semibold tracking-[0.16em]">REPORTS &amp; INSIGHTS</p>
                    <Link :href="route('reports.index')" class="app-nav-link group" :class="{ 'is-active': route().current('reports.*') }" @click="mobileNavOpen = false">
                        <span class="app-nav-mark">04</span>
                        <span>Portfolio insights</span>
                    </Link>
                </div>

                <div v-if="can('audit-log.view')">
                    <p class="app-nav-label mb-2 px-3 text-[10px] font-semibold tracking-[0.16em]">GOVERNANCE</p>
                    <Link :href="route('audit-logs.index')" class="app-nav-link group" :class="{ 'is-active': route().current('audit-logs.*') }" @click="mobileNavOpen = false">
                        <span class="app-nav-mark">05</span>
                        <span>Change history</span>
                    </Link>
                </div>

                <div v-if="can('user.manage')">
                    <p class="app-nav-label mb-2 px-3 text-[10px] font-semibold tracking-[0.16em]">ADMINISTRATION</p>
                    <Link :href="route('users.index')" class="app-nav-link group" :class="{ 'is-active': route().current('users.*') }" @click="mobileNavOpen = false">
                        <span class="app-nav-mark">03</span>
                        <span>People & access</span>
                    </Link>
                    <Link :href="route('roles.index')" class="app-nav-link group" :class="{ 'is-active': route().current('roles.*') }" @click="mobileNavOpen = false">
                        <span class="app-nav-mark">04</span>
                        <span>Roles &amp; permissions</span>
                    </Link>
                </div>

                <div>
                    <p class="app-nav-label mb-2 px-3 text-[10px] font-semibold tracking-[0.16em]">ACCOUNT</p>
                    <Link :href="route('profile.edit')" class="app-nav-link group" :class="{ 'is-active': route().current('profile.*') }" @click="mobileNavOpen = false">
                        <span class="app-nav-mark">06</span>
                        <span>My profile</span>
                    </Link>
                    <Link :href="route('two-factor.settings')" class="app-nav-link group" :class="{ 'is-active': route().current('two-factor.settings') }" @click="mobileNavOpen = false">
                        <span class="app-nav-mark">07</span>
                        <span>Security settings</span>
                    </Link>
                </div>
            </nav>

            <div class="app-sidebar-profile border-t px-4 py-4" data-profile-menu>
                <button type="button" class="app-profile-trigger flex w-full items-center gap-3 rounded p-2 text-left transition"
                    :aria-expanded="sidebarProfileOpen" aria-haspopup="menu" @click="sidebarProfileOpen = !sidebarProfileOpen; topbarProfileOpen = false; searchOpen = false">
                    <span class="app-avatar grid h-9 w-9 shrink-0 place-items-center text-xs font-semibold">{{ userInitials }}</span>
                    <span class="flex min-w-0 flex-1 flex-col">
                        <span class="truncate text-sm font-medium text-white">{{ user?.name }}</span>
                        <span class="mt-0.5 truncate text-xs text-white/50">{{ user?.role ?? 'Team member' }}</span>
                    </span>
                    <span class="app-profile-chevron" :class="{ 'is-open': sidebarProfileOpen }" aria-hidden="true">⌄</span>
                </button>

                <div v-if="sidebarProfileOpen" class="app-profile-menu" role="menu">
                    <div class="app-profile-details">
                        <p class="truncate text-sm font-semibold text-[#17231e]">{{ user?.name }}</p>
                        <p class="mt-1 break-all text-xs text-[#64716a]">{{ user?.email }}</p>
                        <p v-if="user?.department" class="mt-2 text-xs text-[#64716a]">{{ user.department }}</p>
                    </div>
                    <Link :href="route('profile.edit')" role="menuitem" class="app-profile-menu-link" @click="sidebarProfileOpen = false">My profile &amp; preferences</Link>
                    <Link :href="route('password.change')" role="menuitem" class="app-profile-menu-link" @click="sidebarProfileOpen = false">Change password</Link>
                    <Link :href="route('two-factor.settings')" role="menuitem" class="app-profile-menu-link" @click="sidebarProfileOpen = false">Security settings</Link>
                    <Link :href="route('logout')" method="post" as="button" role="menuitem" class="app-profile-menu-link app-profile-signout" @click="sidebarProfileOpen = false">Sign out</Link>
                </div>
            </div>
        </aside>

        <div class="app-main-column flex min-h-screen min-w-0 flex-1 flex-col">
            <header class="app-topbar sticky top-0 z-20 flex min-h-[76px] items-center justify-between gap-4 border-b px-4 sm:px-6 lg:px-10">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" :aria-label="mobileNavOpen ? 'Close navigation menu' : 'Open navigation menu'" aria-controls="app-navigation"
                        :aria-expanded="mobileNavOpen" class="app-menu-button grid h-10 w-10 shrink-0 place-items-center rounded lg:hidden"
                        @click="mobileNavOpen = !mobileNavOpen">
                        <span class="app-menu-icon" :class="{ 'is-open': mobileNavOpen }" aria-hidden="true"></span>
                    </button>
                    <div class="min-w-0">
                        <p class="hidden text-[10px] font-semibold tracking-[0.16em] text-[#738078] sm:block">IS AUDIT / OPERATIONS</p>
                        <h1 class="truncate text-base font-semibold text-[#17231e] sm:mt-1 sm:text-lg">
                            <slot name="header" />
                        </h1>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2 sm:gap-4">
                    <button type="button" class="app-search-trigger inline-flex h-10 items-center gap-2 rounded px-3 text-sm"
                        aria-label="Search pages and loaded records" @click="openSearch">
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                            <circle cx="8.75" cy="8.75" r="5.75" stroke="currentColor" stroke-width="1.5" />
                            <path d="m13 13 4 4" stroke="currentColor" stroke-linecap="round" stroke-width="1.5" />
                        </svg>
                        <span class="hidden md:inline">Search</span>
                        <kbd class="app-search-shortcut hidden lg:inline">Ctrl K</kbd>
                    </button>

                    <div class="relative" data-notification-menu>
                        <button type="button" class="app-topbar-icon-button relative inline-flex h-10 w-10 items-center justify-center rounded-full"
                            :aria-expanded="notificationMenuOpen" aria-haspopup="menu"
                            @click="notificationMenuOpen = !notificationMenuOpen; topbarProfileOpen = false; sidebarProfileOpen = false; searchOpen = false">
                            <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-4 w-4">
                                <path d="M10 2.75a4.25 4.25 0 0 1 4.25 4.25v1.7c0 .98.35 1.93.98 2.67l.97 1.08H3.8l.97-1.08a4.14 4.14 0 0 0 .98-2.67V7A4.25 4.25 0 0 1 10 2.75ZM8.2 14.75h3.6a2 2 0 0 1-3.6 0Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span v-if="unreadNotificationCount" class="absolute -right-1.5 -top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-[#e9a678] px-1 text-[10px] font-bold text-[#17372e]">
                                {{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}
                            </span>
                        </button>

                        <div v-if="notificationMenuOpen" class="app-profile-menu app-profile-menu-top app-notification-panel" role="menu">
                            <div class="app-profile-details border-b border-gray-200 pb-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#64716a]">Notifications</p>
                                <p class="mt-1 text-sm text-[#17231e]">
                                    {{ unreadNotificationCount ? `${unreadNotificationCount} unread updates` : 'All caught up' }}
                                </p>
                            </div>
                            <div v-if="recentNotifications.length" class="mt-3 space-y-2">
                                <Link v-for="item in recentNotifications.slice(0,5)" :key="item.id" :href="route('notifications.open', item.id)" method="post" as="button" class="block w-full rounded-md border border-gray-200 bg-gray-50 p-3 text-left transition hover:border-[#315d47] hover:bg-white" @click="notificationMenuOpen = false">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-[#315d47]">{{ item.data?.title ?? 'Update' }}</p>
                                    <p class="mt-1 text-sm text-gray-700">{{ item.data?.message ?? 'New workflow update.' }}</p>
                                    <p class="mt-2 text-[11px] text-gray-500">{{ formatNotificationTime(item.created_at) }}</p>
                                </Link>
                            </div>
                            <p v-else class="mt-3 text-sm text-gray-500">No recent notifications.</p>
                            <Link :href="route('notifications.index')" class="mt-4 inline-flex w-full items-center justify-center rounded-md bg-[#315d47] px-3 py-2 text-sm font-medium text-white transition hover:bg-[#204b3d]" @click="notificationMenuOpen = false">
                                Open notification center
                            </Link>
                        </div>
                    </div>

                    <div class="relative" data-profile-menu>
                        <button type="button" class="app-topbar-user flex items-center gap-2 rounded p-1.5 sm:gap-3"
                            :aria-expanded="topbarProfileOpen" aria-haspopup="menu" aria-label="Open profile menu"
                            @click="topbarProfileOpen = !topbarProfileOpen; sidebarProfileOpen = false; searchOpen = false">
                            <span class="hidden text-right sm:block">
                                <span class="block max-w-36 truncate text-xs font-semibold text-[#263830]">{{ user?.name }}</span>
                                <span class="mt-0.5 block text-[10px] text-[#77837b]">Account</span>
                            </span>
                            <span class="app-avatar grid h-9 w-9 place-items-center text-xs font-semibold">{{ userInitials }}</span>
                        </button>

                        <div v-if="topbarProfileOpen" class="app-profile-menu app-profile-menu-top" role="menu">
                            <div class="app-profile-details">
                                <p class="truncate text-sm font-semibold text-[#17231e]">{{ user?.name }}</p>
                                <p class="mt-1 break-all text-xs text-[#64716a]">{{ user?.email }}</p>
                                <p v-if="user?.department" class="mt-2 text-xs text-[#64716a]">{{ user.department }}</p>
                            </div>
                            <Link :href="route('profile.edit')" role="menuitem" class="app-profile-menu-link" @click="topbarProfileOpen = false">My profile &amp; preferences</Link>
                            <Link :href="route('password.change')" role="menuitem" class="app-profile-menu-link" @click="topbarProfileOpen = false">Change password</Link>
                            <Link :href="route('two-factor.settings')" role="menuitem" class="app-profile-menu-link" @click="topbarProfileOpen = false">Security settings</Link>
                            <Link :href="route('logout')" method="post" as="button" role="menuitem" class="app-profile-menu-link app-profile-signout" @click="topbarProfileOpen = false">Sign out</Link>
                        </div>
                    </div>
                </div>
            </header>

            <main class="app-main min-w-0 flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-10 lg:py-10 xl:px-12">
                <div v-if="page.props.flash?.success" class="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status" aria-live="polite" aria-atomic="true">
                    {{ page.props.flash.success }}
                </div>

                <div v-if="page.props.flash?.error" class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert" aria-live="assertive" aria-atomic="true">
                    {{ page.props.flash.error }}
                </div>

                <section v-if="validationErrors.length" ref="validationSummary" class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-4 text-red-900" role="alert" aria-live="assertive" aria-atomic="true" tabindex="-1">
                    <h2 class="text-sm font-semibold">Please review the following details</h2>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                        <li v-for="(error, index) in validationErrors" :key="`${error.field}-${index}`">
                            <span class="capitalize">{{ error.field }}:</span> {{ error.message }}
                        </li>
                    </ul>
                </section>

                <slot />
            </main>
        </div>

        <Teleport to="body">
            <div v-if="notificationToast" class="app-live-toast" role="status" aria-live="polite">
                <Link :href="route('notifications.open', notificationToast.id)" method="post" as="button" class="min-w-0 flex-1 text-left" @click="notificationToast = null">
                    <span class="block text-[10px] font-semibold uppercase tracking-[0.12em] text-[#315d47]">New notification</span>
                    <span class="mt-1 block truncate text-sm font-semibold text-[#17231e]">{{ notificationToast.data?.title ?? 'Workflow update' }}</span>
                    <span class="mt-1 block text-xs text-[#64716a]">{{ notificationToast.data?.message ?? 'There is a new update.' }}</span>
                </Link>
                <button type="button" aria-label="Dismiss notification" class="app-live-toast-close" @click="notificationToast = null">×</button>
            </div>

            <div v-if="searchOpen" class="app-search-overlay" @click.self="closeSearch">
                <section class="app-search-dialog" role="dialog" aria-modal="true" aria-labelledby="app-search-title">
                    <div class="app-search-heading">
                        <div>
                            <p class="app-search-eyebrow">QUICK NAVIGATION</p>
                            <h2 id="app-search-title" class="mt-1 font-display text-xl text-[#17231e]">Find a page or record</h2>
                        </div>
                        <button type="button" aria-label="Close search" class="app-search-close" @click="closeSearch">Esc</button>
                    </div>

                    <div class="app-search-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 20 20" fill="none" class="h-5 w-5 shrink-0 text-[#668071]">
                            <circle cx="8.75" cy="8.75" r="5.75" stroke="currentColor" stroke-width="1.5" />
                            <path d="m13 13 4 4" stroke="currentColor" stroke-linecap="round" stroke-width="1.5" />
                        </svg>
                        <input ref="searchInput" v-model="searchQuery" type="search" autocomplete="off"
                            placeholder="Search pages, audits, findings, people..." aria-label="Search pages and loaded records" />
                    </div>

                    <p class="app-search-scope">Pages and actions are permission-aware. Records shown are from the current page.</p>

                    <div class="app-search-results" role="listbox" aria-label="Search results">
                        <p v-if="!searchResults.length" class="app-search-empty">No matching pages or loaded records.</p>
                        <Link v-for="(item, index) in searchResults" :key="`${item.type}-${item.label}-${index}`"
                            :href="item.href" class="app-search-result" :class="{ 'is-selected': index === selectedSearchIndex }"
                            role="option" :aria-selected="index === selectedSearchIndex"
                            @mouseenter="selectedSearchIndex = index" @click="closeSearch">
                            <span class="app-search-result-icon" aria-hidden="true">{{ item.type === 'Page' ? '↗' : '⌕' }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-[#263830]">{{ item.label }}</span>
                                <span class="mt-0.5 block truncate text-xs text-[#718078]">{{ item.detail }}</span>
                            </span>
                            <span class="app-search-result-type">{{ item.type }}</span>
                        </Link>
                    </div>

                    <div class="app-search-footer">
                        <span><kbd>↑</kbd> <kbd>↓</kbd> to browse</span>
                        <span><kbd>Esc</kbd> to close</span>
                    </div>
                </section>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.app-shell {
    display: flex;
    background: #f4f6f1;
    color: #17231e;
    font-family: 'Instrument Sans', 'Aptos', ui-sans-serif, system-ui, sans-serif;
}

.app-sidebar {
    background: linear-gradient(165deg, #204b3d 0%, #16372e 58%, #102a24 100%);
    box-shadow: 8px 0 30px rgb(16 42 36 / 8%);
}

.app-sidebar.is-open {
    transform: translateX(0);
    translate: 0 0;
}

.app-brand {
    border-color: rgb(255 255 255 / 11%);
    color: inherit;
    text-decoration: none;
    transition: background-color 140ms ease;
}

.app-brand:hover {
    background: rgb(255 255 255 / 4%);
}

.app-brand-mark {
    border: 1px solid rgb(255 255 255 / 25%);
    border-radius: 5px;
    background: #e9a678;
    color: #17372e;
}

.app-nav-label {
    color: rgb(234 239 229 / 48%);
}

.app-nav-link {
    display: flex;
    min-height: 42px;
    align-items: center;
    gap: 12px;
    border-radius: 5px;
    padding: 9px 12px;
    color: rgb(255 255 255 / 72%);
    font-size: 13px;
    font-weight: 500;
    transition: color 140ms ease, background-color 140ms ease;
}

.app-nav-link:hover {
    background: rgb(255 255 255 / 7%);
    color: #fff;
}

.app-nav-link.is-active {
    background: rgb(255 255 255 / 12%);
    color: #fff;
    box-shadow: inset 2px 0 #e9a678;
}

.app-nav-sub-link {
    display: flex;
    align-items: center;
    gap: 9px;
    margin: 4px 0 0 34px;
    padding: 6px 8px;
    color: rgb(255 255 255 / 58%);
    font-size: 11px;
    transition: color 140ms ease;
}

.app-nav-sub-link:hover {
    color: #fff;
}

.app-nav-sub-link span {
    color: #e9a678;
    font-size: 15px;
}

.app-nav-mark {
    width: 22px;
    color: #c9dfb7;
    font-size: 9px;
    font-variant-numeric: tabular-nums;
    opacity: 0.7;
}

.app-sidebar-profile {
    border-color: rgb(255 255 255 / 11%);
    position: relative;
}

.app-profile-trigger {
    border: 1px solid transparent;
    cursor: pointer;
}

.app-profile-trigger:hover,
.app-profile-trigger[aria-expanded='true'] {
    border-color: rgb(255 255 255 / 12%);
    background: rgb(255 255 255 / 6%);
}

.app-profile-chevron {
    color: rgb(255 255 255 / 54%);
    transition: transform 140ms ease;
}

.app-profile-chevron.is-open {
    transform: rotate(180deg);
}

.app-profile-menu {
    position: absolute;
    bottom: calc(100% + 10px);
    left: 12px;
    z-index: 60;
    width: min(260px, calc(100vw - 32px));
    overflow: hidden;
    border: 1px solid #dce3db;
    border-radius: 6px;
    background: #fffefb;
    box-shadow: 0 14px 38px rgb(16 42 36 / 18%);
}

.app-profile-menu-top {
    top: calc(100% + 10px);
    right: 0;
    bottom: auto;
    left: auto;
}

.app-profile-details {
    border-bottom: 1px solid #e5eae3;
    padding: 15px 16px;
}

.app-profile-menu-link {
    display: block;
    width: 100%;
    padding: 11px 16px;
    color: #34483e;
    font-size: 13px;
    text-align: left;
    transition: background-color 120ms ease, color 120ms ease;
}

.app-profile-menu-link:hover {
    background: #f3f6f0;
    color: #173d2e;
}

.app-profile-signout {
    border-top: 1px solid #e5eae3;
    color: #9b4d3c;
}

.app-avatar {
    border: 1px solid rgb(49 93 71 / 14%);
    border-radius: 50%;
    background: #e6eee5;
    color: #204b3d;
}

.app-logout {
    color: rgb(255 255 255 / 66%);
}

.app-logout:hover {
    background: rgb(255 255 255 / 8%);
    color: #fff;
}

.app-topbar {
    border-color: #dce3db;
    background: rgb(255 254 251 / 92%);
    backdrop-filter: blur(12px);
}

.app-search-trigger {
    border: 1px solid #dce3db;
    background: #fffefb;
    color: #52665a;
    cursor: pointer;
    transition: border-color 140ms ease, background-color 140ms ease;
}

.app-search-trigger:hover {
    border-color: #a7b8aa;
    background: #f8faf6;
}

.app-search-shortcut,
.app-search-footer kbd {
    border: 1px solid #dce3db;
    border-radius: 3px;
    background: #f4f6f1;
    padding: 2px 5px;
    color: #718078;
    font-size: 10px;
    font-family: inherit;
}

.app-topbar-user {
    border: 1px solid transparent;
    cursor: pointer;
    transition: border-color 140ms ease, background-color 140ms ease;
}

.app-topbar-user:hover,
.app-topbar-user[aria-expanded='true'] {
    border-color: #dce3db;
    background: #f8faf6;
}

.app-topbar-icon-button {
    border: 1px solid #dce3db;
    background: #fffefb;
    color: #315d47;
    cursor: pointer;
    transition: background-color 140ms ease, border-color 140ms ease;
}

.app-topbar-icon-button:hover,
.app-topbar-icon-button[aria-expanded='true'] {
    border-color: #a7b8aa;
    background: #f1f5ee;
}

.app-notification-panel {
    width: min(360px, calc(100vw - 32px));
    max-height: min(72vh, 520px);
    overflow-y: auto;
    padding: 0 12px 12px;
}

.app-notification-panel .app-profile-details {
    margin: 0 -12px;
}

.app-search-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    overflow-y: auto;
    background: rgb(16 42 36 / 48%);
    padding: max(8vh, 24px) 16px 24px;
    backdrop-filter: blur(3px);
}

.app-live-toast {
    position: fixed;
    right: max(16px, env(safe-area-inset-right));
    bottom: max(16px, env(safe-area-inset-bottom));
    z-index: 120;
    display: flex;
    width: min(380px, calc(100vw - 32px));
    align-items: flex-start;
    gap: 12px;
    border: 1px solid #dce3db;
    border-left: 3px solid #d88b5d;
    border-radius: 5px;
    background: #fffefb;
    padding: 15px;
    box-shadow: 0 14px 38px rgb(16 42 36 / 18%);
    animation: app-toast-arrive 180ms ease-out;
}

.app-live-toast-close {
    display: grid;
    width: 28px;
    height: 28px;
    flex: 0 0 auto;
    place-items: center;
    color: #64716a;
    font-size: 18px;
}

@keyframes app-toast-arrive {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.app-search-dialog {
    width: min(100%, 620px);
    overflow: hidden;
    border: 1px solid #dce3db;
    border-radius: 8px;
    background: #fffefb;
    box-shadow: 0 24px 70px rgb(16 42 36 / 26%);
    animation: app-search-arrive 140ms ease-out;
}

.app-search-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 20px 22px 15px;
}

.app-search-eyebrow {
    color: #718078;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.16em;
}

.app-search-close {
    border: 1px solid #dce3db;
    border-radius: 4px;
    background: #f4f6f1;
    padding: 4px 7px;
    color: #64716a;
    font-size: 10px;
    cursor: pointer;
}

.app-search-input-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0 22px;
    border-bottom: 2px solid #315d47;
    padding: 11px 1px;
}

.app-search-input-wrap input {
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    color: #17231e;
    font-size: 16px;
}

.app-search-input-wrap input::placeholder {
    color: #9aa69d;
}

.app-search-scope {
    padding: 10px 22px 2px;
    color: #89958d;
    font-size: 10px;
}

.app-search-results {
    max-height: min(55vh, 430px);
    overflow-y: auto;
    padding: 8px 10px 12px;
}

.app-search-result {
    display: flex;
    min-height: 58px;
    align-items: center;
    gap: 12px;
    border-radius: 5px;
    padding: 8px 12px;
    text-decoration: none;
}

.app-search-result:hover,
.app-search-result:focus-visible,
.app-search-result.is-selected {
    outline: none;
    background: #edf2e7;
}

.app-search-result-icon {
    display: grid;
    width: 30px;
    height: 30px;
    flex: 0 0 auto;
    place-items: center;
    border: 1px solid #dce3db;
    border-radius: 4px;
    background: #fffefb;
    color: #315d47;
    font-size: 14px;
}

.app-search-result-type {
    color: #89958d;
    font-size: 10px;
}

.app-search-empty {
    padding: 22px 12px;
    color: #718078;
    font-size: 13px;
    text-align: center;
}

.app-search-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    border-top: 1px solid #e5eae3;
    padding: 11px 22px;
    color: #89958d;
    font-size: 10px;
}

@keyframes app-search-arrive {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}

.app-menu-button {
    border: 1px solid #dce3db;
    background: #fffefb;
    color: #204b3d;
}

.app-menu-icon,
.app-menu-icon::before,
.app-menu-icon::after {
    display: block;
    width: 16px;
    height: 1.5px;
    background: currentColor;
    transition: transform 160ms ease;
}

.app-menu-icon {
    position: relative;
}

.app-menu-icon::before,
.app-menu-icon::after {
    position: absolute;
    left: 0;
    content: '';
}

.app-menu-icon::before {
    transform: translateY(-5px);
}

.app-menu-icon::after {
    transform: translateY(5px);
}

.app-menu-icon.is-open {
    background: transparent;
}

.app-menu-icon.is-open::before {
    transform: rotate(45deg);
}

.app-menu-icon.is-open::after {
    transform: rotate(-45deg);
}

.app-nav-backdrop {
    background: rgb(16 42 36 / 45%);
}

@media (min-width: 1024px) {
    .app-main-column {
        width: calc(100% - 264px);
    }
}
</style>