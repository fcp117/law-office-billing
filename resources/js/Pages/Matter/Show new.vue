<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

// Import our new Modals
import TaskModal from '@/Components/TaskModal.vue';
import EventModal from '@/Components/EventModal.vue';

const props = defineProps({
    matter: Object
});

// --- Invoicing & Payments Logic ---
const isPaymentModalOpen = ref(false);
const selectedInvoice = ref(null);

const form = useForm({
    amount: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'Bank Transfer',
    reference_number: '',
});

const openPaymentModal = (invoice) => {
    selectedInvoice.value = invoice;
    form.amount = invoice.total_amount;
    isPaymentModalOpen.value = true;
};

const closePaymentModal = () => {
    isPaymentModalOpen.value = false;
    selectedInvoice.value = null;
    form.reset();
    form.clearErrors();
};

const submitPayment = () => {
    form.post(route('payments.store', selectedInvoice.value.id), {
        preserveScroll: true,
        onSuccess: () => closePaymentModal(),
    });
};

// --- Tasks Logic ---
const isTaskModalOpen = ref(false);
const selectedTask = ref(null);

const openTaskModal = (task = null) => {
    selectedTask.value = task; // null for create, object for edit
    isTaskModalOpen.value = true;
};

const deleteTask = (task) => {
    if (confirm(`Delete task "${task.title}"?`)) {
        router.delete(route('tasks.destroy', task.id), { preserveScroll: true });
    }
};

// --- Events Logic ---
const isEventModalOpen = ref(false);
const selectedEvent = ref(null);

const openEventModal = (event = null) => {
    selectedEvent.value = event;
    isEventModalOpen.value = true;
};

const deleteEvent = (event) => {
    if (confirm(`Delete event "${event.title}"?`)) {
        router.delete(route('events.destroy', event.id), { preserveScroll: true });
    }
};

</script>

<template>
    <Head :title="matter.name" />

    <AuthenticatedLayout>
        
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2 text-sm">
                    <Link :href="route('dashboard')" class="text-gray-500 hover:text-blue-600 transition font-medium">Dashboard</Link>
                    <span class="text-gray-400">/</span>
                    <Link :href="route('clients.show', matter.client_id)" class="text-gray-500 hover:text-blue-600 transition font-medium">Client Details</Link>
                    <span class="text-gray-400">/</span>
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">{{ matter.name }}</h2>
                </div>
                
                <div class="space-x-3">
                    <span class="px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full border border-green-200">
                        {{ matter.status }}
                    </span>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                        Edit Matter
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-3 mb-4">Case Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Client Name</p>
                            <Link :href="route('clients.show', matter.client_id)" class="text-blue-600 hover:underline font-medium text-lg">
                                {{ matter.client.name }}
                            </Link>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-semibold">Court / Authority</p>
                            <p class="text-gray-800 font-medium">{{ matter.authority || 'Not specified' }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-lg font-bold">Pending Tasks</h2>
                            <button @click="openTaskModal()" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100 font-semibold border border-blue-200">
                                + Add Task
                            </button>
                        </div>
                        
                        <div class="max-h-[28rem] overflow-y-auto pr-2">
                            <ul class="space-y-4" v-if="matter.tasks && matter.tasks.length > 0">
                                <li v-for="task in matter.tasks" :key="task.id" class="flex items-start space-x-3 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <input type="checkbox" class="mt-1 rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                    <div class="w-full">
                                        <div class="flex justify-between items-start">
                                            <span class="font-semibold text-gray-800 leading-tight">{{ task.title }}</span>
                                            <span class="text-xs font-medium text-yellow-700 bg-yellow-100 px-2 py-0.5 rounded border border-yellow-200 shrink-0 ml-2">
                                                {{ new Date(task.deadline).toLocaleDateString() }}
                                            </span>
                                        </div>
                                        <div v-if="task.description" class="text-sm text-gray-600 mt-1">{{ task.description }}</div>
                                        
                                        <div class="flex space-x-3 mt-2 text-xs">
                                            <button @click="openTaskModal(task)" class="text-blue-600 hover:underline font-medium">Edit</button>
                                            <button @click="deleteTask(task)" class="text-red-500 hover:underline font-medium">Delete</button>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-gray-500 text-sm italic text-center py-4 bg-gray-50 rounded border border-dashed border-gray-200">No pending tasks for this case.</p>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-lg font-bold">Upcoming Events</h2>
                            <button @click="openEventModal()" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100 font-semibold border border-blue-200">
                                + Add Event
                            </button>
                        </div>

                        <div class="max-h-[28rem] overflow-y-auto pr-2">
                            <ul class="space-y-4" v-if="matter.events && matter.events.length > 0">
                                <li v-for="event in matter.events" :key="event.id" class="bg-red-50 p-3 rounded-lg border border-red-100">
                                    <div class="flex justify-between items-start">
                                        <span class="font-bold text-gray-900 leading-tight">{{ event.title }}</span>
                                        <span class="text-xs font-bold text-red-700 bg-red-200 px-2 py-0.5 rounded shrink-0 ml-2">
                                            {{ new Date(event.scheduled_at).toLocaleDateString() }}
                                        </span>
                                    </div>
                                    <div v-if="event.description" class="text-sm text-gray-700 mt-1">{{ event.description }}</div>
                                    <div class="text-xs text-red-600 font-medium mt-1">
                                        Time: {{ new Date(event.scheduled_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                                    </div>
                                    
                                    <div class="flex space-x-3 mt-2 text-xs">
                                        <button @click="openEventModal(event)" class="text-blue-600 hover:underline font-medium">Edit</button>
                                        <button @click="deleteEvent(event)" class="text-red-500 hover:underline font-medium">Delete</button>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-gray-500 text-sm italic text-center py-4 bg-gray-50 rounded border border-dashed border-gray-200">No events scheduled.</p>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 h-fit">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-lg font-bold">Financials</h2>
                            <button class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded hover:bg-gray-200 font-semibold border border-gray-200">
                                + New Invoice
                            </button>
                        </div>
                        
                        <div class="max-h-[28rem] overflow-y-auto pr-2">
                            <ul class="space-y-3" v-if="matter.invoices && matter.invoices.length > 0">
                                <li v-for="invoice in matter.invoices" :key="invoice.id" class="p-3 border rounded-lg hover:shadow-sm transition" :class="invoice.status === 'Paid' ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-white'">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-gray-800">{{ invoice.invoice_number }}</span>
                                        <span class="font-bold" :class="invoice.status === 'Paid' ? 'text-green-700' : 'text-gray-900'">
                                            Php {{ parseFloat(invoice.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                        </span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center text-sm mb-2">
                                        <span class="text-gray-500">Issued: {{ new Date(invoice.issue_date).toLocaleDateString() }}</span>
                                        <span class="font-semibold text-xs px-2 py-0.5 rounded-full" :class="invoice.status === 'Paid' ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-700'">
                                            {{ invoice.status }}
                                        </span>
                                    </div>

                                    <div v-if="invoice.status !== 'Paid'" class="mt-3 pt-3 border-t border-gray-100 flex justify-end">
                                        <button @click="openPaymentModal(invoice)" class="text-xs bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded transition font-medium">
                                            Record Payment
                                        </button>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-gray-500 text-sm italic text-center py-4 bg-gray-50 rounded border border-dashed border-gray-200">No invoices found for this case.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
        <div v-if="isPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm transition-opacity"></div>

        <TaskModal 
            :show="isTaskModalOpen" 
            :matter-id="matter.id"
            :task="selectedTask"
            @close="isTaskModalOpen = false" 
        />

        <EventModal 
            :show="isEventModalOpen" 
            :matter-id="matter.id"
            :event="selectedEvent"
            @close="isEventModalOpen = false" 
        />

    </AuthenticatedLayout>
</template>