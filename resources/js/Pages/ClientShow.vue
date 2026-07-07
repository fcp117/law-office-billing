<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EditClientModal from '@/Components/EditClientModal.vue';
import CreateMatterModal from '@/Components/CreateMatterModal.vue';
import { ref } from 'vue';

const props = defineProps({
    client: Object,
    matters: Array
});

// We can reuse the exact same modal we built for the ClientList!
const isEditModalOpen = ref(false);
const isCreateMatterModalOpen = ref(false);
</script>

<template>
    <Head :title="client?.name || 'Client Details'" />

    <AuthenticatedLayout>
        
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <Link :href="route('dashboard')" class="text-gray-500 hover:text-blue-600 transition font-medium">
                        Dashboard
                    </Link>
                    <span class="text-gray-400">/</span>
                    <Link :href="route('client-list')" class="text-gray-500 hover:text-blue-600 transition font-medium">
                        Clients
                    </Link>
                    <span class="text-gray-400">/</span>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ client?.name }}
                    </h2>
                    <!-- <Link :href="route('client-list')" class="text-sm text-gray-500 hover:text-blue-600 transition">
                        &larr; Back to Clients List
                    </Link> -->
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <div class="md:col-span-1 space-y-6">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div class="flex justify-between items-center border-b pb-3 mb-4">
                                <h3 class="text-lg font-bold text-gray-800">Client Details</h3>
                                <button @click="isEditModalOpen = true" class="text-sm text-green-600 hover:underline">
                                    Edit Info
                                </button>
                            </div>
                            
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Primary Email</p>
                                    <p class="text-gray-800">{{ client?.email }}</p>
                                </div>
                                
                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Address</p>
                                    <p class="text-gray-800">{{ client?.address || 'Not provided' }}</p>
                                </div>

                                <div class="border-t pt-4 mt-4">
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Contact Person</p>
                                    <p class="text-gray-800">{{ client?.contact_person || 'N/A' }}</p>
                                    <p class="text-gray-600 text-sm">{{ client?.contact_person_email }}</p>
                                </div>

                                <div class="border-t pt-4 mt-4">
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Retainer Amount</p>
                                    <p class="text-gray-800 font-medium text-green-600">
                                        Php {{ parseFloat(client?.retainer_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs text-gray-500 uppercase font-semibold">Partner-in-Charge</p>
                                    <p class="text-gray-800">{{ client?.partner_in_charge || 'Unassigned' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            
                            <div class="flex justify-between items-center border-b pb-3 mb-4">
                                <h3 class="text-lg font-bold text-gray-800">Case Matters</h3>
                                <button @click="isCreateMatterModalOpen = true" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm font-semibold transition shadow-sm">
                                    + Add Matter
                                </button>
                            </div>

                            <div v-if="matters?.length > 0">
                                <ul class="divide-y divide-gray-100">
                                    <li v-for="matter in matters" :key="matter.id" class="py-4 hover:bg-gray-50 transition px-2 rounded -mx-2">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <Link :href="route('matters.show', matter.id)" class="text-lg font-bold text-blue-600 hover:text-blue-800 hover:underline block">
                                                    {{ matter.name }}
                                                </Link>
                                                <div class="text-sm text-gray-500 mt-1 flex space-x-4">
                                                    <span>Status: <span class="font-medium text-gray-700">{{ matter.status }}</span></span>
                                                    <span>Authority: <span class="font-medium text-gray-700">{{ matter.authority || 'N/A' }}</span></span>
                                                </div>
                                            </div>
                                        <!--<div>
                                                <Link href="#" class="text-sm text-gray-500 hover:text-gray-800">
                                                    View Details &rarr;
                                                </Link>
                                            </div> -->
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            
                            <div v-else class="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                <p class="text-gray-500 mb-2">No matters have been created for this client yet.</p>
                                <p class="text-sm text-gray-400">Click the "+ Add Matter" button to get started.</p>
                            </div>

                        </div>
                    </div>

                </div>
            </div> 
        </div>

        <EditClientModal 
            :show="isEditModalOpen" 
            :client="client" 
            @close="isEditModalOpen = false" 
        />

        <CreateMatterModal 
            :show="isCreateMatterModalOpen" 
            :client-id="client?.id"
            @close="isCreateMatterModalOpen = false" 
        />

    </AuthenticatedLayout>
</template>