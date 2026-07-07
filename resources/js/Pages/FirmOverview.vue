<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    kpis: Object,
    events: Array,
    tasks: Array,
    matters: Array
});
</script>

<template>
    <Head title="Firm Overview" />

    <AuthenticatedLayout>
        
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Firm-wide Overview
            </h2>
        </template>

        <div class="py-12">

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-green-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Total Amount YTD</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.total_this_year.toLocaleString() }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-green-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Billed Amount YTD</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.billed_this_year.toLocaleString() }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-blue-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Unbilled Amount YTD</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.unbilled_this_year.toLocaleString() }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-blue-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Total Paid YTD</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.paid_this_year.toLocaleString() }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-green-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Total Amount MTD</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.total_this_month.toLocaleString() }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-green-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Billed Amount MTD</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.billed_this_month.toLocaleString() }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-blue-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Unbilled Amount MTD</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.unbilled_this_month.toLocaleString() }}</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow border-b-4 border-blue-500">
                        <h3 class="text-gray-500 text-sm font-semibold">Total Paid MTD</h3>
                        <p class="text-3xl font-bold text-gray-900">Php {{ kpis.paid_this_month.toLocaleString() }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2 space-y-6">
                        
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h2 class="text-lg font-bold mb-4 border-b pb-2">Upcoming Events</h2>
                            <ul class="space-y-3" v-if="events.length > 0">
                                <li v-for="event in events" :key="event.id" class="flex justify-between items-start">
                                    <div>
                                        <span class="font-semibold text-gray-800">{{ event.title }}</span>
                                        <div class="text-sm text-gray-500">{{ event.matter.name }}</div>
                                    </div>
                                    <div class="text-xs font-medium text-red-600 bg-red-50 px-2 py-1 rounded">
                                        {{ new Date(event.scheduled_at).toLocaleString() }}
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-gray-500 text-sm italic">No upcoming events scheduled.</p>
                        </div>

                        <div class="bg-white p-6 rounded-lg shadow">
                            <h2 class="text-lg font-bold mb-4 border-b pb-2">Pending Tasks</h2>
                            <ul class="space-y-3" v-if="tasks.length > 0">
                                <li v-for="task in tasks" :key="task.id" class="flex justify-between items-start">
                                    <div class="flex items-center space-x-3">
                                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                        <div>
                                            <span class="font-semibold text-gray-800">{{ task.title }}</span>
                                            <div class="text-sm text-gray-500">{{ task.matter.name }}</div>
                                        </div>
                                    </div>
                                    <div class="text-xs font-medium text-yellow-600 bg-yellow-50 px-2 py-1 rounded">
                                        Due: {{ new Date(task.deadline).toLocaleDateString() }}
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="text-gray-500 text-sm italic">All caught up! No pending tasks.</p>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow h-fit">
                        <h2 class="text-lg font-bold mb-4 border-b pb-2">My Active Matters</h2>
                        <ul class="space-y-2">
                            <li v-for="matter in matters" :key="matter.id">
                                <Link :href="`/matters/${matter.id}`" class="block p-3 rounded hover:bg-gray-50 transition border border-transparent hover:border-gray-200">
                                    <div class="font-semibold text-blue-600">{{ matter.name }}</div>
                                    <div class="text-xs text-gray-500 mt-1 flex justify-between">
                                        <span>{{ matter.authority }} {{ matter.id }}</span>
                                        <span class="text-green-600">{{ matter.status }}</span>
                                    </div>
                                </Link>
                            </li>
                        </ul>
                    </div>
                    
                </div>
            </div>
        </div>
        
    </AuthenticatedLayout>
</template>