<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import EditUserModal from '@/Components/EditUserModal.vue';
import CreateUserModal from '@/Components/CreateUserModal.vue';

const props = defineProps({
    lawyers: Array,
    filters: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isPartner = computed(() => currentUser.value?.role === 'Partner');
const canManageLawyers = computed(() => {
    const role = currentUser.value?.role;
    return role === 'Partner' || role === 'Admin';
});

// Search & Status Filter State
const search = ref(props.filters.search || '');
const showInactive = ref(true);
const togglingUserId = ref(null);

watch(search, (value) => {
    router.get(route('lawyers'), { search: value }, {
        preserveState: true,
        replace: true,
    });
});

const isUserActive = (lawyer) => lawyer.is_active !== false && lawyer.is_active !== 0;

const visibleLawyers = computed(() => {
    if (showInactive.value) return props.lawyers;
    return props.lawyers.filter((l) => isUserActive(l));
});

const roleSections = computed(() => [
    {
        title: 'Partners & Leadership',
        headerClass: 'text-purple-700 bg-purple-50 border-purple-200',
        badgeClass: 'bg-purple-100 text-purple-800 border-purple-200',
        members: visibleLawyers.value.filter((l) => l.role === 'Partner' || l.role === 'Admin'),
    },
    {
        title: 'Associates',
        headerClass: 'text-blue-700 bg-blue-50 border-blue-200',
        badgeClass: 'bg-blue-100 text-blue-800 border-blue-200',
        members: visibleLawyers.value.filter((l) => l.role === 'Associate'),
    },
    {
        title: 'Paralegals',
        headerClass: 'text-emerald-700 bg-emerald-50 border-emerald-200',
        badgeClass: 'bg-emerald-100 text-emerald-800 border-emerald-200',
        members: visibleLawyers.value.filter((l) => l.role === 'Paralegal'),
    },
].filter((section) => section.members.length > 0));

const inactiveCount = computed(() => props.lawyers.filter((l) => !isUserActive(l)).length);

// Modal State
const isModalOpen = ref(false);
const isCreateModalOpen = ref(false);
const selectedLawyer = ref(null);

const openEditModal = (lawyer) => {
    selectedLawyer.value = lawyer;
    isModalOpen.value = true;
};

const toggleUserStatus = (lawyer) => {
    const active = isUserActive(lawyer);
    const action = active ? 'deactivate' : 'reactivate';
    const warning = active
        ? `Deactivate ${lawyer.name}'s account? They will immediately lose login access, while all historical time entries, cases, and invoices will remain intact.`
        : `Reactivate ${lawyer.name}'s account and restore login access?`;

    if (!confirm(warning)) return;

    togglingUserId.value = lawyer.id;
    router.patch(
        route('lawyers.toggle-active', lawyer.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                togglingUserId.value = null;
            },
        }
    );
};

const getInitials = (name) => {
    if (!name) return 'LC';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};
</script>

<template>
    <Head title="Lawyers Directory" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Firm Lawyers Directory
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Manage partners, associates, paralegals, hourly rates, and account access.
                    </p>
                </div>

                <!-- Search Input, Inactive Toggle & Create Button -->
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                    <label
                        v-if="inactiveCount > 0"
                        class="inline-flex items-center gap-2 text-xs font-medium text-slate-600 bg-slate-100 px-3 py-2 rounded-lg border border-slate-200 cursor-pointer select-none"
                    >
                        <input
                            type="checkbox"
                            v-model="showInactive"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <span>Show Inactive ({{ inactiveCount }})</span>
                    </label>

                    <input
                        type="text"
                        v-model="search"
                        placeholder="Search lawyers by name, email..."
                        class="w-full sm:w-64 rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                    />

                    <button
                        v-if="canManageLawyers"
                        @click="isCreateModalOpen = true"
                        class="inline-flex items-center justify-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-xs font-semibold uppercase tracking-wider rounded-lg shadow-sm transition shrink-0"
                    >
                        + Add Lawyer
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">
                <!-- Flash Error Feedback -->
                <div
                    v-if="$page.props.flash?.error"
                    class="rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700"
                >
                    {{ $page.props.flash.error }}
                </div>

                <div v-if="roleSections.length > 0">
                    <div
                        v-for="section in roleSections"
                        :key="section.title"
                        class="mb-10"
                    >
                        <div class="border-b border-gray-200 pb-3 mb-6">
                            <h3
                                class="font-bold tracking-wide uppercase text-xs inline-block px-3 py-1 rounded-md border"
                                :class="section.headerClass"
                            >
                                {{ section.title }}
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div
                                v-for="lawyer in section.members"
                                :key="lawyer.id"
                                class="rounded-xl shadow-sm border overflow-hidden hover:shadow-md transition flex flex-col justify-between"
                                :class="isUserActive(lawyer)
                                    ? 'bg-white border-gray-200'
                                    : 'bg-slate-50/80 border-slate-200 opacity-80'"
                            >
                                <div>
                                    <div class="p-6 pb-4 flex items-start space-x-4">
                                        <div class="shrink-0">
                                            <Link :href="route('dashboard', { user_id: lawyer.id })" class="block group">
                                                <div
                                                    v-if="lawyer.profile_photo_url"
                                                    class="h-16 w-16 rounded-full overflow-hidden border-2 border-gray-100 shadow-inner group-hover:opacity-90 transition"
                                                >
                                                    <img :src="lawyer.profile_photo_url" :alt="lawyer.name" class="h-full w-full object-cover" />
                                                </div>
                                                <div
                                                    v-else
                                                    class="h-16 w-16 rounded-full text-white font-bold flex items-center justify-center text-lg shadow-inner transition"
                                                    :class="isUserActive(lawyer)
                                                        ? 'bg-slate-800 group-hover:bg-slate-700'
                                                        : 'bg-slate-400 group-hover:bg-slate-500'"
                                                >
                                                    {{ getInitials(lawyer.name) }}
                                                </div>
                                            </Link>
                                        </div>

                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between gap-2">
                                                <Link
                                                    :href="route('dashboard', { user_id: lawyer.id })"
                                                    class="text-lg font-bold text-gray-900 truncate hover:text-blue-600 transition"
                                                >
                                                    {{ lawyer.name }}
                                                </Link>
                                            </div>
                                            <p class="text-sm text-gray-500 truncate">{{ lawyer.email }}</p>
                                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                                    :class="section.badgeClass"
                                                >
                                                    {{ lawyer.role }}
                                                </span>
                                                <span
                                                    v-if="!isUserActive(lawyer)"
                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200"
                                                >
                                                    Inactive
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-6 py-3 bg-slate-50 border-t border-b border-gray-100 text-sm space-y-1">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-500">Account Status:</span>
                                            <span
                                                v-if="isUserActive(lawyer)"
                                                class="inline-flex items-center gap-1.5 font-semibold text-emerald-700 text-xs"
                                            >
                                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1.5 font-semibold text-rose-700 text-xs"
                                            >
                                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                                Inactive (Access Denied)
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Footer Actions -->
                                <div class="px-6 py-3 bg-white flex flex-wrap items-center justify-between gap-2">
                                    <!-- Partner-only Activate / Deactivate Toggle (never on self) -->
                                    <div>
                                        <button
                                            v-if="isPartner && currentUser?.id !== lawyer.id"
                                            type="button"
                                            @click="toggleUserStatus(lawyer)"
                                            :disabled="togglingUserId === lawyer.id"
                                            class="px-2.5 py-1.5 text-xs font-semibold rounded-md border transition disabled:opacity-50"
                                            :class="isUserActive(lawyer)
                                                ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-600 hover:text-white'
                                                : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-600 hover:text-white'"
                                        >
                                            {{
                                                togglingUserId === lawyer.id
                                                    ? 'Updating...'
                                                    : isUserActive(lawyer)
                                                        ? 'Deactivate'
                                                        : 'Reactivate'
                                            }}
                                        </button>
                                    </div>

                                    <!-- Rates & Edit Buttons -->
                                    <div class="flex items-center gap-2 ml-auto">
                                        <Link
                                            v-if="isPartner && route().has('users.rates.index')"
                                            :href="route('users.rates.index', lawyer.id)"
                                            class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-md border border-indigo-200 transition"
                                        >
                                            Rates
                                        </Link>

                                        <button
                                            v-if="isPartner"
                                            type="button"
                                            @click="openEditModal(lawyer)"
                                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-md border border-slate-200 transition flex items-center space-x-1"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            <span>Edit Profile</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No lawyers found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your search query or toggling inactive accounts.</p>
                </div>
            </div>
        </div>

        <!-- Edit Modal Component -->
        <EditUserModal
            :show="isModalOpen"
            :user="selectedLawyer"
            @close="isModalOpen = false"
        />

        <CreateUserModal
            :show="isCreateModalOpen"
            @close="isCreateModalOpen = false"
        />
    </AuthenticatedLayout>
</template>