<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreateClientModal from '@/Components/CreateClientModal.vue';
import EditClientModal from '@/Components/EditClientModal.vue';

const props = defineProps({
    clients: Array
});

// Search filter state
const searchQuery = ref('');

// Computed property to filter clients based on the search query
const filteredClients = computed(() => {
    if (!searchQuery.value) {
        return props.clients;
    }
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    return props.clients.filter(client => 
        client.name.toLowerCase().includes(lowerCaseQuery)
    );
});

// Modal state
const isCreateModalOpen = ref(false);
const editingClient = ref(null);

const openEditModal = (client) => {
    editingClient.value = client;
};

const deleteClient = (client) => {
    if (confirm(`Are you sure you want to permanently delete ${client.name}?`)) {
        router.delete(route('clients.destroy', client.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Clients" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Clients</h2>
                    <Link :href="route('dashboard')" class="text-sm text-gray-500 hover:text-blue-600 transition">
                         Back to Dashboard
                    </Link>
                </div>
                <button @click="isCreateModalOpen = true" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                    + Create New Client
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Search Filter Bar -->
                <div class="mb-6">
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        placeholder="Search clients by name..." 
                        class="w-full sm:max-w-md border-slate-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                    />
                </div>

                <!-- Stacked Card Layout -->
                <div class="flex flex-col space-y-4">
                    <div 
                        v-for="client in filteredClients" 
                        :key="client.id" 
                        class="bg-white shadow-sm border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow duration-200"
                    >
                        <div class="flex justify-between items-start mb-4 border-b border-slate-100 pb-4">
                            <!-- Live Link opening the clients.show route -->
                            <Link 
                                :href="route('clients.show', client.id)" 
                                class="text-xl font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                            >
                                {{ client.name }}
                            </Link>
                            
                            <!-- Action Buttons -->
                            <div class="flex space-x-4 text-sm font-medium">
                                <button @click="openEditModal(client)" class="text-green-600 hover:text-green-800 hover:underline">
                                    Edit
                                </button>
                                <button @click="deleteClient(client)" class="text-red-600 hover:text-red-800 hover:underline">
                                    Delete
                                </button>
                            </div>
                        </div>
                        
                        <!-- Card Details -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 text-sm text-slate-600">
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Email</span> 
                                {{ client.email || '—' }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Contact Person</span> 
                                {{ client.contact_person || '—' }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Contact Person E-mail</span> 
                                {{ client.contact_person_email || '—' }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Address</span> 
                                {{ client.address || '—' }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Partner-in-Charge</span> 
                                {{ client.partner_in_charge || '—' }}
                            </div>
                            <div>
                                <span class="font-medium text-slate-900 block mb-1">Retainer Amount</span> 
                                Php {{ parseFloat(client.retainer_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredClients.length === 0" class="bg-white text-center py-10 rounded-lg border border-slate-200 text-slate-500">
                        No clients found matching "{{ searchQuery }}".
                    </div>
                </div>
                
            </div> 
        </div>
    
        <CreateClientModal 
            :show="isCreateModalOpen" 
            @close="isCreateModalOpen = false" 
        />

        <EditClientModal 
            :show="editingClient !== null" 
            :client="editingClient" 
            @close="editingClient = null" 
        />
    </AuthenticatedLayout>
</template>