<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import EditUserModal from '@/Components/EditUserModal.vue';

const props = defineProps({
    lawyers: Array,
    filters: Object,
});

// Search State with debounce
const search = ref(props.filters.search || '');

watch(search, (value) => {
    router.get(route('lawyers'), { search: value }, {
        preserveState: true,
        replace: true,
    });
});

// Computed properties to group lawyers by role
const partners = computed(() => props.lawyers.filter(l => l.role === 'Partner' || l.role === 'Admin'));
const associates = computed(() => props.lawyers.filter(l => l.role === 'Associate'));
const paralegals = computed(() => props.lawyers.filter(l => l.role === 'Paralegal'));

// Modal State
const isModalOpen = ref(false);
const selectedLawyer = ref(null);

const openEditModal = (lawyer) => {
    selectedLawyer.value = lawyer;
    isModalOpen.value = true;
};

// Generates initials avatar placeholder if no photo exists
const getInitials = (name) => {
    if (!name) return 'LC';
    const parts = name.split(' ');
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
                    <p class="text-sm text-gray-500 mt-1">Manage partners, associates, paralegals, hourly rates, and profiles.</p>
                </div>
                
                <!-- Search Input Bar -->
                <div class="w-full sm:w-72">
                    <input 
                        type="text" 
                        v-model="search" 
                        placeholder="Search lawyers by name, email..." 
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200"
                    >
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">
                
                <div v-if="lawyers.length > 0">
                    
                    <!-- PARTNERS SECTION -->
                    <div v-if="partners.length > 0" class="mb-10">
                        <div class="border-b border-gray-200 pb-3 mb-6">
                            <h3 class="text-lg font-bold text-gray-900 tracking-wide uppercase text-xs text-purple-700 bg-purple-50 inline-block px-3 py-1 rounded-md border border-purple-200">
                                Partners & Leadership
                            </h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="lawyer in partners" :key="lawyer.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition flex flex-col justify-between">
                                <div>
                                    <div class="p-6 pb-4 flex items-start space-x-4">
                                        <div class="shrink-0">
                                            <Link :href="route('dashboard', { user_id: lawyer.id })" class="block group">
                                                <div v-if="lawyer.profile_photo_url" class="h-16 w-16 rounded-full overflow-hidden border-2 border-gray-100 shadow-inner group-hover:opacity-90 transition">
                                                    <img :src="lawyer.profile_photo_url" :alt="lawyer.name" class="h-full w-full object-cover">
                                                </div>
                                                <div v-else class="h-16 w-16 rounded-full bg-slate-800 text-white font-bold flex items-center justify-center text-lg shadow-inner group-hover:bg-slate-700 transition">
                                                    {{ getInitials(lawyer.name) }}
                                                </div>
                                            </Link>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <Link :href="route('dashboard', { user_id: lawyer.id })" class="text-lg font-bold text-gray-900 truncate hover:text-blue-600 transition">
                                                    {{ lawyer.name }}
                                                </Link>
                                            </div>
                                            <p class="text-sm text-gray-500 truncate">{{ lawyer.email }}</p>
                                            <div class="mt-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                                    {{ lawyer.role }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-6 py-3 bg-slate-50 border-t border-b border-gray-100 text-sm space-y-1">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Standard Hourly Rate:</span>
                                            <span class="font-semibold text-gray-800">
                                                {{ lawyer.standard_hourly_rate ? `Php ${parseFloat(lawyer.standard_hourly_rate).toLocaleString('en-US', { minimumFractionDigits: 2 })}` : 'Not Set' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Account Status:</span>
                                            <span class="font-medium text-green-600">Active</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="px-6 py-3 bg-white flex justify-end">
                                    <button @click="openEditModal(lawyer)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-md border border-slate-200 transition flex items-center space-x-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        <span>Edit Profile</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ASSOCIATES SECTION -->
                    <div v-if="associates.length > 0" class="mb-10">
                        <div class="border-b border-gray-200 pb-3 mb-6">
                            <h3 class="text-lg font-bold text-gray-900 tracking-wide uppercase text-xs text-blue-700 bg-blue-50 inline-block px-3 py-1 rounded-md border border-blue-200">
                                Associates
                            </h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="lawyer in associates" :key="lawyer.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition flex flex-col justify-between">
                                <div>
                                    <div class="p-6 pb-4 flex items-start space-x-4">
                                        <div class="shrink-0">
                                            <Link :href="route('dashboard', { user_id: lawyer.id })" class="block group">
                                                <div v-if="lawyer.profile_photo_url" class="h-16 w-16 rounded-full overflow-hidden border-2 border-gray-100 shadow-inner group-hover:opacity-90 transition">
                                                    <img :src="lawyer.profile_photo_url" :alt="lawyer.name" class="h-full w-full object-cover">
                                                </div>
                                                <div v-else class="h-16 w-16 rounded-full bg-slate-800 text-white font-bold flex items-center justify-center text-lg shadow-inner group-hover:bg-slate-700 transition">
                                                    {{ getInitials(lawyer.name) }}
                                                </div>
                                            </Link>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <Link :href="route('dashboard', { user_id: lawyer.id })" class="text-lg font-bold text-gray-900 truncate hover:text-blue-600 transition">
                                                    {{ lawyer.name }}
                                                </Link>
                                            </div>
                                            <p class="text-sm text-gray-500 truncate">{{ lawyer.email }}</p>
                                            <div class="mt-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                    {{ lawyer.role }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-6 py-3 bg-slate-50 border-t border-b border-gray-100 text-sm space-y-1">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Standard Hourly Rate:</span>
                                            <span class="font-semibold text-gray-800">
                                                {{ lawyer.standard_hourly_rate ? `Php ${parseFloat(lawyer.standard_hourly_rate).toLocaleString('en-US', { minimumFractionDigits: 2 })}` : 'Not Set' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Account Status:</span>
                                            <span class="font-medium text-green-600">Active</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="px-6 py-3 bg-white flex justify-end">
                                    <button @click="openEditModal(lawyer)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-md border border-slate-200 transition flex items-center space-x-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        <span>Edit Profile</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PARALEGALS SECTION -->
                    <div v-if="paralegals.length > 0" class="mb-10">
                        <div class="border-b border-gray-200 pb-3 mb-6">
                            <h3 class="text-lg font-bold text-gray-900 tracking-wide uppercase text-xs text-emerald-700 bg-emerald-50 inline-block px-3 py-1 rounded-md border border-emerald-200">
                                Paralegals
                            </h3>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="lawyer in paralegals" :key="lawyer.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition flex flex-col justify-between">
                                <div>
                                    <div class="p-6 pb-4 flex items-start space-x-4">
                                        <div class="shrink-0">
                                            <Link :href="route('dashboard', { user_id: lawyer.id })" class="block group">
                                                <div v-if="lawyer.profile_photo_url" class="h-16 w-16 rounded-full overflow-hidden border-2 border-gray-100 shadow-inner group-hover:opacity-90 transition">
                                                    <img :src="lawyer.profile_photo_url" :alt="lawyer.name" class="h-full w-full object-cover">
                                                </div>
                                                <div v-else class="h-16 w-16 rounded-full bg-slate-800 text-white font-bold flex items-center justify-center text-lg shadow-inner group-hover:bg-slate-700 transition">
                                                    {{ getInitials(lawyer.name) }}
                                                </div>
                                            </Link>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between">
                                                <Link :href="route('dashboard', { user_id: lawyer.id })" class="text-lg font-bold text-gray-900 truncate hover:text-blue-600 transition">
                                                    {{ lawyer.name }}
                                                </Link>
                                            </div>
                                            <p class="text-sm text-gray-500 truncate">{{ lawyer.email }}</p>
                                            <div class="mt-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    {{ lawyer.role }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="px-6 py-3 bg-slate-50 border-t border-b border-gray-100 text-sm space-y-1">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Standard Hourly Rate:</span>
                                            <span class="font-semibold text-gray-800">
                                                {{ lawyer.standard_hourly_rate ? `Php ${parseFloat(lawyer.standard_hourly_rate).toLocaleString('en-US', { minimumFractionDigits: 2 })}` : 'Not Set' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Account Status:</span>
                                            <span class="font-medium text-green-600">Active</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="px-6 py-3 bg-white flex justify-end">
                                    <button @click="openEditModal(lawyer)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-md border border-slate-200 transition flex items-center space-x-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        <span>Edit Profile</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Empty State -->
                <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <h3 class="mt-2 text-sm font-semibold text-gray-900">No lawyers found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try adjusting your search query to find matching lawyers.</p>
                </div>

            </div>
        </div>

        <!-- Edit Modal Component -->
        <EditUserModal 
            :show="isModalOpen" 
            :user="selectedLawyer" 
            @close="isModalOpen = false" 
        />
    </AuthenticatedLayout>
</template>