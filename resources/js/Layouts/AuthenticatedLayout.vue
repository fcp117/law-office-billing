<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const navigation = [
    { label: 'Dashboard', route: 'dashboard', active: 'dashboard', icon: 'dashboard' },
    { label: 'Matters', route: 'matters', active: 'matters*', icon: 'matters' },
    { label: 'Clients', route: 'clients', active: 'clients*', icon: 'clients' },
    { label: 'Lawyers', route: 'lawyers', active: 'lawyers*', icon: 'lawyers' },
    { label: 'Invoices', route: 'invoices', active: 'invoices*', icon: 'invoices' },
];
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Mobile top bar -->
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 lg:hidden">
            <Link :href="route('dashboard')" class="flex items-center gap-3">
                <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
                <span class="font-semibold text-gray-800">OCBOCC CIMS</span>
            </Link>

            <button
                type="button"
                @click="showingNavigationDropdown = !showingNavigationDropdown"
                class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500"
                :aria-expanded="showingNavigationDropdown"
                aria-label="Toggle navigation"
            >
                <svg v-if="!showingNavigationDropdown" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg v-else class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </header>

        <div class="flex min-h-[calc(100vh-4rem)] lg:min-h-screen">
            <!-- Desktop sidebar -->
            <aside class="hidden w-64 shrink-0 flex-col border-r border-gray-200 bg-white lg:flex">
                <div class="flex h-20 items-center gap-3 border-b border-gray-100 px-5">
                    <Link :href="route('dashboard')" class="flex items-center gap-3">
                        <ApplicationLogo class="block h-10 w-auto fill-current text-gray-800" />
                        <span class="font-semibold leading-tight text-gray-800">OCBOCC<br><span class="text-xs font-medium text-gray-500">Case Management</span></span>
                    </Link>
                </div>

                <div class="border-t border-gray-100 p-3">
                    <div class="mb-2 px-3">
                        <div class="truncate text-sm font-semibold text-gray-800">
                            {{ $page.props.auth.user?.name || 'Guest' }}
                        </div>
                        <div class="truncate text-xs text-gray-500">
                            {{ $page.props.auth.user?.email || '' }}
                        </div>
                    </div>
                    <Link :href="route('profile.edit')" class="block rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                        Profile
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="block w-full rounded-lg px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                    >
                        Log Out
                    </Link>
                </div>

                <nav class="flex-1 space-y-1 px-3 py-5" aria-label="Main navigation">
                    <template v-for="item in navigation" :key="item.label">
                    <Link
                        v-if="route().has(item.route)"
                        :href="route(item.route)"
                        class="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition"
                        :class="route().current(item.active)
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <svg v-if="item.icon === 'dashboard'" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <rect x="3" y="3" width="7" height="7" rx="1" stroke-width="1.8" />
                            <rect x="14" y="3" width="7" height="7" rx="1" stroke-width="1.8" />
                            <rect x="3" y="14" width="7" height="7" rx="1" stroke-width="1.8" />
                            <rect x="14" y="14" width="7" height="7" rx="1" stroke-width="1.8" />
                        </svg>
                        <svg v-else-if="item.icon === 'matters'" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A1.5 1.5 0 0 1 4.5 6H10l2 2h7.5A1.5 1.5 0 0 1 21 9.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 18.5z" />
                        </svg>
                        <svg v-else-if="item.icon === 'clients'" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="9" cy="8" r="3" stroke-width="1.8" />
                            <path stroke-linecap="round" stroke-width="1.8" d="M3 19v-1a6 6 0 0 1 12 0v1M16 5.5a3 3 0 0 1 0 5.8M18 14a4 4 0 0 1 3 3.9V19" />
                        </svg>
                        <svg v-else-if="item.icon === 'lawyers'" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v18M5 6h14M7 6l-4 7h8L7 6zm10 0-4 7h8l-4-7zM7 21h10" />
                        </svg>
                        <svg v-else class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 3h9l4 4v14H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zM14 3v5h5M8 13h8M8 17h8" />
                        </svg>
                        <span>{{ item.label }}</span>
                    </Link>
                    </template>
                </nav>

                <div class="border-t border-gray-100 p-3">
                    <div class="mb-2 px-3">
                        <div class="truncate text-sm font-semibold text-gray-800">
                            {{ $page.props.auth.user?.name || 'Guest' }}
                        </div>
                        <div class="truncate text-xs text-gray-500">
                            {{ $page.props.auth.user?.email || '' }}
                        </div>
                    </div>
                    <Link :href="route('profile.edit')" class="block rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                        Profile
                    </Link>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="block w-full rounded-lg px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                    >
                        Log Out
                    </Link>
                </div>
            </aside>

            <!-- Mobile navigation panel -->
            <div
                v-if="showingNavigationDropdown"
                class="fixed inset-0 z-40 lg:hidden"
            >
                <button
                    type="button"
                    class="absolute inset-0 h-full w-full bg-gray-900/40"
                    aria-label="Close navigation"
                    @click="showingNavigationDropdown = false"
                ></button>
                <aside class="relative flex h-full w-72 max-w-[85vw] flex-col bg-white shadow-xl">
                    <div class="flex h-16 items-center justify-between border-b border-gray-100 px-4">
                        <span class="font-semibold text-gray-800">OCBOCC CIMS</span>
                        <button @click="showingNavigationDropdown = false" class="rounded-md p-2 text-gray-500 hover:bg-gray-100" aria-label="Close navigation">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6l12 12M18 6L6 18" />
                            </svg>
                        </button>
                    </div>

                    <nav class="flex-1 space-y-1 px-3 py-4" aria-label="Mobile navigation">
                        <template v-for="item in navigation" :key="item.label">
                        <Link
                            v-if="route().has(item.route)"
                            :href="route(item.route)"
                            @click="showingNavigationDropdown = false"
                            class="block rounded-lg px-3 py-2.5 text-sm font-medium"
                            :class="route().current(item.active)
                                ? 'bg-blue-50 text-blue-700'
                                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                        >
                            {{ item.label }}
                        </Link>
                        </template>
                    </nav>

                    <div class="border-t border-gray-100 p-3">
                        <div class="mb-2 px-3">
                            <div class="truncate text-sm font-semibold text-gray-800">
                                {{ $page.props.auth.user?.name || 'Guest' }}
                            </div>
                            <div class="truncate text-xs text-gray-500">
                                {{ $page.props.auth.user?.email || '' }}
                            </div>
                        </div>
                        <Link :href="route('profile.edit')" @click="showingNavigationDropdown = false" class="block rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                            Profile
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="block w-full rounded-lg px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-50"
                        >
                            Log Out
                        </Link>
                    </div>
                </aside>
            </div>

            <!-- Main content area -->
            <div class="min-w-0 flex-1">
                <!-- Page heading -->
                <header v-if="$slots.header" class="border-b border-gray-200 bg-white shadow-sm">
                    <div class="px-4 py-5 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>

                <!-- Page content -->
                <main class="min-w-0">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
