<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import DashboardMatterModal from '@/Components/DashboardMatterModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    matters: Array
});

// Reactive state for the search input
const searchQuery = ref('');

const isMatterModalOpen = ref(false);

// Computed property to filter matters based on the search query
const filteredMatters = computed(() => {
    if (!searchQuery.value) {
        return props.matters;
    }
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    return props.matters.filter(matter => 
        matter.name.toLowerCase().includes(lowerCaseQuery)
    );
});
</script>

<template>
    <Head title="OCBOCC Case Information Management System" />

    <AuthenticatedLayout>

        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Matters</h2>
                    <Link :href="route('dashboard')" class="text-sm text-gray-500 hover:text-blue-600 transition">
                         Back to Dashboard
                    </Link>
                </div>
                <button @click="isMatterModalOpen = true" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                                    + Add Matter
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Search Filter Bar -->
                <div class="mb-6">
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        placeholder="Search matters by name..." 
                        class="w-full sm:max-w-md border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    />
                </div>
                
                <!-- Stacked Card Layout -->
                <div class="flex flex-col space-y-4">
                    
                    <div 
                        v-for="matter in filteredMatters" 
                        :key="matter.id" 
                        class="bg-white shadow-sm border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200"
                    >
                        <!-- Live Link opening the matters.show route -->
                        <Link 
                            :href="route('matters.show', matter.id)" 
                            class="text-xl font-semibold text-blue-600 hover:text-blue-800 hover:underline mb-4 inline-block"
                        >
                            {{ matter.name }}
                        </Link>
                        
                        <!-- Card Details arranged horizontally on larger screens -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-sm text-slate-600">
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Client</span> 
                                {{ matter.client.name }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Practice Area</span> 
                                {{ matter.practice_area }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Type</span> 
                                {{ matter.type }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Description</span> 
                                <span class="truncate block" :title="matter.description">
                                    {{ matter.description }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State when no results match -->
                    <div v-if="filteredMatters.length === 0" class="bg-white text-center py-10 rounded-lg border border-slate-200 text-slate-500">
                        No matters found matching "{{ searchQuery }}".
                    </div>

                </div>
            </div>
        </div>

        <DashboardMatterModal 
            :show="isMatterModalOpen" 
            :clients="clients"
            @close="isMatterModalOpen = false" 
        />

    </AuthenticatedLayout>
</template>