<script setup>
import {Head, Link} from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CreateClientModal from '@/Components/CreateClientModal.vue';
import EditClientModal from '@/Components/EditClientModal.vue';
import { ref } from 'vue'; // Import ref for your modal logic

defineProps({
    clients: Array
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <table class="w-full text-left">
                            <thead class="border-b border-gray-100">
                                <tr class="text-slate-600 text-sm">
                                    <th class="p-3">Name</th>
                                    <th class="p-3">Address</th>
                                    <th class="p-3">Email</th>
                                    <th class="p-3">Contact Person</th>
                                    <th class="p-3">E-mail</th>
                                    <th class="p-3">Retainer Amount</th>
                                    <th class="p-3">Partner-in-Charge</th>
                                    <th class="p-3 text-right">Actions</th>

                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="client in clients" :key="client.id" class="border-b">
                                    <td class="p-3 font-medium">
                                        <Link :href="route('clients.show', client.id)" class="text-blue-600 hover:text-blue-800 hover:underline font-bold">
                                            {{ client.name }}
                                        </Link>
                                    </td>
                                    <td class="p-3 text-slate-600">{{client.address}}</td>
                                    <td class="p-3 text-slate-600">{{ client.email }}</td>
                                    <td class="p-3 text-slate-600">{{ client.contact_person }}</td>
                                    <td class="p-3 text-slate-600">{{client.contact_person_email}}</td>
                                    <td class="p-3 text-slate-600">Php {{ parseFloat(client.retainer_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}}</td>
                                    <td class="p-3 text-slate-600">{{client.partner_in_charge}}</td>
                                    <td class="p-3 text-right">
                                            <button @click="openEditModal(client)" class="text-green-500 hover:underline text-sm">
                                                Edit
                                            </button>
                                            
                                            <button @click="deleteClient(client)" class="text-red-500 hover:underline text-sm">
                                                Delete
                                            </button>
                                    </td>
                                </tr>
                            
                            </tbody>
                        </table>
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