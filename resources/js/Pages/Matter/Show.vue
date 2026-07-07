<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

// Import our Modals
import TaskModal from '@/Components/TaskModal.vue';
import EventModal from '@/Components/EventModal.vue';

const props = defineProps({
    matter: Object
});

// ==========================================
// 1. INVOICING & PAYMENTS LOGIC
// ==========================================
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

// ==========================================
// 2. TASKS LOGIC
// ==========================================
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

// ==========================================
// 3. EVENTS LOGIC
// ==========================================
const isEventModalOpen = ref(false);
const selectedEvent = ref(null);

const openEventModal = (event = null) => {
    selectedEvent.value = event; // null for create, object for edit
    isEventModalOpen.value = true;
};

const deleteEvent = (event) => {
    if (confirm(`Delete event "${event.title}"?`)) {
        router.delete(route('events.destroy', event.id), { preserveScroll: true });
    }
};
</script>

<template>
    <!-- Dynamic page title based on the case name -->
    <Head :title="matter.name" />

    <AuthenticatedLayout>
        
        <!-- The Header Slot (Matches the Dashboard style) -->
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Matter Details
                </h2>
                <!-- Optional: A back button to return to the dashboard -->
                <Link :href="route('dashboard')" class="text-sm text-blue-600 hover:underline">
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <!-- The Main Content Wrapper -->
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Header Section -->
                <div class="bg-slate-800 text-white p-6 rounded-lg shadow">
                    <div class="flex justify-between items-start">
                        <div class="bg-gray-800 text-white p-6 rounded-lg shadow">
                            <h1 class="text-3xl font-bold">{{ matter.name }}</h1>
                            <p class="mt-2 text-gray-300">Client: {{ matter.client.name }} | Authority: {{ matter.authority }}</p>
                        </div>
                        <span class="px-3 py-1 bg-green-500 text-white text-sm font-bold rounded-full">
                            {{ matter.status }}
                        </span>
                    </div>

                    <!-- Handling Lawyers now inside the Summary Card -->
                    <div class="border-t border-slate-700 pt-4">
                        <h3 class="text-sm font-semibold text-gray-400 uppercase mb-2">Handling Lawyers</h3>
                        <div class="flex flex-wrap gap-4">
                            <div v-for="user in matter.users" :key="user.id" class="text-sm">
                                <span class="font-bold">{{ user.name }}</span> 
                                <span class="text-slate-400"> ({{ user.role }})</span>
                            </div>
                        </div>
                    </div>
                </div> <!--End of Header Section-->
            </div>
        </div>
        <!-- Content Stack -->
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Tasks Section -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-xl font-bold mb-4 border-b pb-2">Tasks</h2>
                            <button @click="openTaskModal()" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100 font-semibold border border-blue-200">
                                    + Add Task
                            </button>
                        </div>
                        <table class="w-full text-left" v-if="matter.tasks && matter.tasks.length > 0">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-sm">
                                    <th class="p-3">Title</th>
                                    <th class="p-3">Deadline</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="task in matter.tasks" :key="task.id" class="border-b">
                                    <td class="p-3 font-medium">{{ task.title }}</td>
                                    <td class="p-3 text-slate-600">{{ new Date(task.deadline).toLocaleDateString() }}</td>
                                    <td class="p-3">
                                        <span class="px-2 py-1 text-xs rounded-full" 
                                            :class="task.status === 'Completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                            {{ task.status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex space-x-3 mt-2 text-xs">
                                            <button @click="openTaskModal(task)" class="text-green-500 hover:bg-green-600 font-medium text-sm py-1 px-3 rounded">Edit</button>
                                            <button @click="deleteTask(task)" class="text-red-500 hover:bg-red-600 font-medium text-sm py-1 px-3 rounded">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-slate-500 italic">No tasks assigned to this matter.</p>
                    </div>

                    <!-- Events Section -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center mb-4 border-b pb-2">
                            <h2 class="text-xl font-bold mb-4 border-b pb-2">Events</h2>
                            <button @click="openEventModal()" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100 font-semibold border border-blue-200">
                                    + Add Event
                            </button>
                        </div>
                        
                        <table class="w-full text-left" v-if="matter.events && matter.events.length > 0">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-sm">
                                    <th class="p-3">Title</th>
                                    <th class="p-3">Scheduled For</th>
                                    <th class="p-3">Location</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="event in matter.events" :key="event.id" class="border-b">
                                    <td class="p-3 font-medium">{{ event.title }}</td>
                                    <td class="p-3 text-slate-600">{{ new Date(event.scheduled_at).toLocaleString() }}</td>
                                    <td class="p-3 text-slate-600">{{ event.location || 'N/A' }}</td>
                                    <td>
                                        <div class="flex space-x-3 mt-2 text-xs">
                                            <button @click="openEventModal(event)" class="text-green-500 text-sm hover:bg-green-600 font-medium py-1 px-3 rounded">Edit</button>
                                            <button @click="deleteEvent(event)" class="text-red-500 text-sm hover:bg-red-600 font-medium py-1 px-3 rounded">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-slate-500 italic">No events scheduled.</p>
                    </div>
            </div>
        </div>  
    </div>  
                <div class="lg:col-span-2 space-y-6 px-12">
                    <!-- Time Entries Section -->
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="flex justify-between items-center border-b pb-2 mb-4">
                            <h2 class="text-xl font-bold">Time Entries (Running Billable)</h2>
                            <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700 transition">
                                Generate Invoice
                            </button>
                        </div>
                        <table class="w-full text-left" v-if="matter.time_entries && matter.time_entries.length > 0">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-sm">
                                    <th class="p-3">Lawyer</th>
                                    <th class="p-3">Date</th>
                                    <th class="p-3">Description</th>
                                    <th class="p-3">Hours</th>
                                    <th class="p-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in matter.time_entries" :key="entry.id" class="border-b">
                                    <td class="p-3 font-medium">{{ entry.user?.name || 'Unknown User' }}</td> 
                                    <td class="p-3 text-slate-600">{{ new Date(entry.start_time).toLocaleDateString() }}</td>
                                    <td class="p-3 text-slate-600">{{ entry.description }}</td>
                                    <td class="p-3 text-slate-600">{{ entry.hours }}</td>
                                    <td class="p-3 text-slate-600">Php {{ parseFloat(entry.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-slate-500 italic">No time logged for this matter yet.</p>
                    </div>

                    <!-- Invoices Section -->
                    <div class="bg-white p-6 rounded-lg shadow mt-6">
                        <h2 class="text-xl font-bold mb-4 border-b pb-2">Invoices & Payments</h2>
                        
                        <table class="w-full text-left" v-if="matter.invoices && matter.invoices.length > 0">
                            <thead>
                                <tr class="bg-slate-50 text-slate-600 text-sm">
                                    <th class="p-3">Invoice #</th>
                                    <th class="p-3">Issue Date</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Total Amount</th>
                                    <th class="p-3">Total Payments</th>
                                    <th class="p-3">Balance Due</th>
                                    <th class="p-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="invoice in matter.invoices" :key="invoice.id" class="border-b">
                                    <td class="p-3 font-medium text-slate-800">{{ invoice.invoice_number }}</td>
                                    <td class="p-3 text-slate-600">{{ new Date(invoice.issue_date).toLocaleDateString() }}</td>
                                    <td class="p-3">
                                        <!-- Dynamic Status Badges -->
                                        <span class="px-2 py-1 text-xs font-bold rounded-full" 
                                            :class="{
                                                'bg-green-100 text-green-800': invoice.status === 'Paid',
                                                'bg-blue-100 text-blue-800': invoice.status === 'Sent',
                                                'bg-slate-100 text-slate-800': invoice.status === 'Draft'
                                            }">
                                            {{ invoice.status }}
                                        </span>
                                    </td>
                                    <td class="p-3 font-medium text-slate-800">
                                        Php {{ parseFloat(invoice.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    </td>
                                    <td class="p-3 font-medium text-slate-800">
                                        Php {{ parseFloat(invoice.total_payments).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    </td>
                                    <td class="p-3 font-medium text-slate-800">
                                        Php {{ parseFloat(invoice.balance_due).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                    </td>
                                    <td class="p-3 text-right">
                                        <!-- Only show the Record Payment button if the invoice isn't paid yet -->
                                        <button v-if="invoice.status !== 'Paid'" 
                                                @click="openPaymentModal(invoice)"
                                                class="text-sm bg-blue-50 text-blue-600 px-3 py-1 rounded border border-blue-200 hover:bg-blue-600 hover:text-white font-semibold transition">
                                            Record Payment
                                        </button>
                                        <!-- Show a view button if it is paid -->
                                        <button v-else 
                                                class="text-sm text-slate-500 hover:text-slate-800 underline transition">
                                            View Receipt
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-else class="text-slate-500 italic">No invoices have been generated for this matter yet.</p>
                    </div>
                </div>
    </AuthenticatedLayout>
    
    <div v-if="isPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900 bg-opacity-50 backdrop-blur-sm transition-opacity">
    
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
            
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">
                    Payment for {{ selectedInvoice.invoice_number }}
                </h3>
                <button @click="closePaymentModal" class="text-slate-400 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form @submit.prevent="submitPayment" class="p-6">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Amount Received (Php)</label>
                    <input type="number" step="0.01" v-model="form.amount" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    <div v-if="form.errors.amount" class="text-red-500 text-xs mt-1">{{ form.errors.amount }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Date</label>
                    <input type="date" v-model="form.payment_date" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" required>
                    <div v-if="form.errors.payment_date" class="text-red-500 text-xs mt-1">{{ form.errors.payment_date }}</div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Payment Method</label>
                    <select v-model="form.payment_method" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option>Bank Transfer</option>
                        <option>Check</option>
                        <option>Cash</option>
                        <option>Gcash / E-Wallet</option>
                    </select>
                    <div v-if="form.errors.payment_method" class="text-red-500 text-xs mt-1">{{ form.errors.payment_method }}</div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Reference / Check Number</label>
                    <input type="text" v-model="form.reference_number" placeholder="Optional" class="w-full rounded-md border-slate-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    <div v-if="form.errors.reference_number" class="text-red-500 text-xs mt-1">{{ form.errors.reference_number }}</div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="closePaymentModal" class="px-4 py-2 bg-slate-100 text-slate-700 font-medium rounded-md hover:bg-slate-200 transition">
                        Cancel
                    </button>
                    <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 transition disabled:opacity-50 flex items-center">
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>Save Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

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

</template>