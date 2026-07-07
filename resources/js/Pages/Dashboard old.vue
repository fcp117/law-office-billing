<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
// Import our new Dashboard Modals
import DashboardTaskModal from '@/Components/DashboardTaskModal.vue';
import DashboardEventModal from '@/Components/DashboardEventModal.vue';

defineProps({
    kpis: Object,
    events: Array,
    tasks: Array,
    matters: Array
});

// --- Tasks Logic ---
const isTaskModalOpen = ref(false);
const selectedTask = ref(null);

const openTaskModal = (task = null) => {
    selectedTask.value = task;
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
    <Head title="OCBOCC Case Information Management System" />

    <AuthenticatedLayout>
        
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    My Workspace
                </h2>
                
                <Link :href="route('client-list')"
                        as="button"
                        type="button"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                        Manage Clients
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <div v-if="$page.props.auth.user.role === 'Partner'" 
                    class="bg-blue-50 border border-blue-200 rounded-lg p-6 shadow-sm flex flex-col sm:flex-row justify-between items-center">
                    
                    <div class="mb-4 sm:mb-0">
                        <h3 class="text-lg font-bold text-blue-900">Firm-wide Financials</h3>
                        <p class="text-sm text-blue-700 mt-1">View total revenue, collections, and aging accounts receivable.</p>
                    </div>
                    
                    <Link :href="route('dashboard.overview')" 
                            class="inline-flex items-center px-4 py-2 bg-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-800 focus:bg-blue-800 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow">
                        View Firm Overview
                    </Link>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-gray-500 text-sm font-semibold">Billable Hours (This Month)</h3>
                        <p class="text-3xl font-bold text-gray-900">{{ kpis.hours }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-green-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Billed Amount</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.billed.toLocaleString() }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-blue-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Unbilled Amount</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.unbilled.toLocaleString() }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2 space-y-6">
                        
                        <div class="bg-white p-6 rounded-lg shadow">
                            <div class="flex justify-between items-center mb-4 border-b pb-2">    
                                <h2 class="text-lg font-bold mb-4 border-b pb-2">Upcoming Events</h2>
                                <button @click="openEventModal()" class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded hover:bg-blue-100 font-semibold border border-blue-200">
                                    + Add Event
                                </button>
                            </div>


                            <div class="max-h-80 overflow-y-auto pr-2">
                                <ul class="space-y-3" v-if="events.length > 0">
                                    <li v-for="event in events" :key="event.id" class="flex justify-between items-start">
                                        <div>
                                            <span class="font-semibold text-gray-800">{{ event.title }}</span>
                                            <Link :href="`/matters/${event.matter.id}`" class="block p-3 rounded hover:bg-gray-50 transition border border-transparent hover:border-gray-200">
                                                <div class="text-sm text-gray-500">{{ event.matter.name }}</div>
                                            </Link>
                                        </div>
                                        <div class="text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded">
                                            {{ new Date(event.scheduled_at).toLocaleString() }}
                                        </div>
                                        <div class="flex space-x-3 mt-2 text-xs">
                                            <button @click="openEventModal(event)" class="text-blue-600 hover:underline font-medium">Edit</button>
                                            <button @click="deleteEvent(event)" class="text-red-500 hover:underline font-medium">Delete</button>
                                        </div>
                                    </li>
                                </ul>
                                <p v-else class="text-gray-500 text-sm italic">No upcoming events scheduled.</p>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-lg shadow">
                            <h2 class="text-lg font-bold mb-4 border-b pb-2">Pending Tasks</h2>
                            <div class="max-h-80 overflow-y-auto pr-2">
                                <ul class="space-y-3" v-if="tasks.length > 0">
                                    <li v-for="task in tasks" :key="task.id" class="flex justify-between items-start">
                                        <div class="flex items-center space-x-3">
                                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                            <div>
                                                <span class="font-semibold text-gray-800">{{ task.title }}</span>
                                                <Link :href="`/matters/${task.matter.id}`" class="block p-3 rounded hover:bg-gray-50 transition border border-transparent hover:border-gray-200">
                                                <div class="text-sm text-gray-500">{{ task.matter.name }}</div>
                                                </Link>
                                            </div>
                                        </div>
                                        <div class="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-1 rounded">
                                            Due: {{ new Date(task.deadline).toLocaleDateString() }}
                                        </div>
                                        <div class="flex space-x-3 mt-2 text-xs">
                                                <button @click="openTaskModal(task)" class="text-blue-600 hover:underline font-medium">Edit</button>
                                                <button @click="deleteTask(task)" class="text-red-500 hover:underline font-medium">Delete</button>
                                        </div>
                                    </li>
                                </ul>
                                <p v-else class="text-gray-500 text-sm italic">All caught up! No pending tasks.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow h-fit">
                        <h2 class="text-lg font-bold mb-4 border-b pb-2">My Active Matters</h2>
                        <div class="max-h-[46rem] overflow-y-auto pr-2">
                            <ul class="space-y-2">
                                <li v-for="matter in matters" :key="matter.id">
                                    <Link :href="`/matters/${matter.id}`" class="block p-3 rounded hover:bg-gray-50 transition border border-transparent hover:border-gray-200">
                                        <div class="font-semibold text-blue-600">{{ matter.name }}</div>
                                        <div class="text-xs text-gray-500 mt-1 flex justify-between">
                                            <span>{{ matter.authority }}</span>
                                            <span class="text-green-600">{{ matter.status }}</span>
                                        </div>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>

        <DashboardTaskModal 
            :show="isTaskModalOpen" 
            :matters="matters"
            :task="selectedTask"
            @close="isTaskModalOpen = false" 
        />

        <DashboardEventModal 
            :show="isEventModalOpen" 
            :matters="matters"
            :event="selectedEvent"
            @close="isEventModalOpen = false" 
        />
        
    </AuthenticatedLayout>
</template>